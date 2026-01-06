<?php

namespace App\Services\ReclamationClient;

use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\TRecCommissionRecours;
use App\Models\ReclamationClient\TRecTicketDirection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Récupère les données pour le tableau de bord (Timeline et Combined)
     *
     * @param mixed $user Utilisateur authentifié
     * @param array $filters Filtres (date_from, date_to, bticket_id, statuses, source, etc.)
     * @return array
     */
    public function getTimelineData($user, array $filters): array
    {

        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $bticketId = $filters['bticket_id'] ?? null;
        $bticketIds = $filters['bticket_ids'] ?? [];
        $source = $filters['source'] ?? null;
        $statuses = $filters['statuses'] ?? [];

        $privilege = $source === 'timeline' ? $user->scopePrivileges('dasboard_détaillé') : $user->scopePrivileges('dasboard_global');

        // Normaliser bticket_ids: accepter CSV ou tableau
        if (is_string($bticketIds)) {
            $bticketIds = array_filter(array_map('intval', explode(',', $bticketIds)));
        }
        if (!is_array($bticketIds)) {
            $bticketIds = [];
        }
        // Compatibilité: si bticket_id fourni et pas de bticket_ids, l’ajouter
        if (!empty($bticketId) && empty($bticketIds)) {
            $bticketIds = [(int) $bticketId];
        }

        if (is_string($statuses)) {
            $statuses = array_filter(array_map('trim', explode(',', $statuses)));
        }
        if (!is_array($statuses)) {
            $statuses = [];
        }

        $items = collect();
        $stats = null;

        if (!empty($privilege)) {
            $query = TRecTicket::with(['baseTicket','user']);

            // Visibilité commission de recours
            $isCommissionMember = TRecCommissionRecours::where('user_id', $user->id)->exists();

            // Portée par rôle
            $tticket_ids = collect();
            if ($privilege->role === 'employe_Répondeur') {
                $tticket_ids = TRecTicketDirection::where('direction', $user->direction)
                    ->pluck('tticket_id');
            }

            // Si Admin, pas de restriction de portée (voit tout)
            if ($privilege->role !== 'Admin') {
                $query->where(function ($q) use ($privilege, $tticket_ids, $isCommissionMember, $user) {
                    if ($privilege->role === 'employe_Répondeur') {
                        $q->whereIn('id', $tticket_ids)
                          ->orWhere('user_id', $user->id);
                    } else {
                        // Demandeur standard : voit uniquement ses tickets
                        $q->where('user_id', $user->id);
                    }

                    // Membre commission : voit aussi les recours
                    if ($isCommissionMember) {
                        $q->orWhereIn('status', ['Recours', 'Recours clôturé']);
                    }
                });
            }

            $visibilite = $privilege->visibilite ?? null;
            if ($visibilite === 'L') {
                $query->whereHas('baseTicket', function ($q) use ($user) {
                    $q->where('direction', $user->direction);
                });
            }
            if ($visibilite === 'P') {
                $query->where('user_id', $user->id);
            }

            // Filtres
            if (!empty($dateFrom)) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if (!empty($dateTo)) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
            // Filtre par types de réclamation (b_rec_ticket)
            if (!empty($bticketIds)) {
                $query->whereIn('bticket_id', $bticketIds);
            } elseif (!empty($bticketId)) {
                $query->where('bticket_id', (int) $bticketId);
            }
            // Filtre par statuts
            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }

            // Règle métier: ne prendre que les tickets validés par le créateur
            $query->whereNotNull('date_validation_createur');

            // Exclusion des statuts clôturés si la source est 'timeline'
            // car la timeline ne doit afficher que les tickets actifs ou en cours
            $isCombined = $source === 'combined';
            if ($source === 'timeline') {
                $query->whereNotIn('status', ['clôturé', 'Recours clôturé']);
            }

            // Optimisation: Si c'est pour le dashboard combiné (stats globales), on n'a besoin que des stats agrégées
            if ($isCombined) {
                // Clone query for aggregation
                $statsQuery = clone $query;
                $stats = $statsQuery->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->all();

                $tickets = collect(); // On ne renvoie pas les tickets individuels pour le combined
            } else {
                $tickets = $query->orderBy('created_at', 'desc')->get();
            }

            // Précharger les orientations de directions pour tous les tickets en une seule requête
            $ticketIds = $tickets->pluck('id')->all();
            $directionsByTicket = collect();
            if (!empty($ticketIds)) {
                $directionsByTicket = TRecTicketDirection::whereIn('tticket_id', $ticketIds)
                    ->get()
                    ->groupBy('tticket_id');
            }

            // Charger la composition de la commission de recours (président et membres)
            // Uniquement nécessaire pour la Timeline détaillée
            $commissionMembers = collect();
            $commissionPresident = null;

            if (!$isCombined) {
                $commissionMembers = TRecCommissionRecours::select('user_id','nom','prenom','direction','role')->get();
                $commissionPresident = $commissionMembers->first(function ($m) {
                    return strtolower(trim((string)$m->role)) === 'président';
                });
            }

            // Mapping pour la timeline
            $items = $tickets->map(function ($ticket) use ($directionsByTicket, $commissionMembers, $commissionPresident) {
                // Règle métier obligatoire: si date_validation est NULL -> ne pas afficher la réclamation
                // (Déjà filtré par query->whereNotNull, mais double check si nécessaire, ici redondant mais sûr)
                if (empty($ticket->date_validation_createur)) {
                    return null;
                }

                $baseTicket = $ticket->baseTicket;
                $user = $ticket->user; // relation chargée
                $ownerDisplay = null;
                if ($user) {
                    // Si l’utilisateur a une direction, l’afficher, sinon Nom Prénom
                    $dir = trim((string) ($user->direction ?? ''));
                    if ($dir !== '') {
                        $ownerDisplay = $dir;
                    } else {
                        $ownerDisplay = trim(((string) ($user->Prenom ?? '')) . ' ' . ((string) ($user->Nom ?? '')));
                    }
                }

                // Acteurs par ticket
                $dirs = $directionsByTicket->get($ticket->id, collect());
                $pilotDirection = null;
                $treatmentDirections = [];
                $consultationDirections = [];

                if ($dirs && $dirs->count() > 0) {
                    $pilot = $dirs->first(function ($d) { return ($d->type_orientation ?? null) === 'ticket'; });
                    $pilotDirection = $pilot ? ($pilot->direction ?? null) : null;
                    $treatmentDirections = $dirs->filter(function ($d) { return ($d->statut_direction ?? null) === 'traitement'; })
                        ->pluck('direction')->filter()->unique()->values()->all();
                    $consultationDirections = $dirs->filter(function ($d) { return ($d->statut_direction ?? null) === 'consultation'; })
                        ->pluck('direction')->filter()->unique()->values()->all();
                }

                // Acteurs en cas de recours
                $recoursPilot = null;
                if ($commissionPresident) {
                    $presName = trim(((string)($commissionPresident->prenom ?? '')) . ' ' . ((string)($commissionPresident->nom ?? '')));
                    $presDir = trim((string)($commissionPresident->direction ?? ''));
                    $recoursPilot = $presName !== ''
                        ? ($presDir !== '' ? ($presName . ' (' . $presDir . ')') : $presName)
                        : ($presDir !== '' ? $presDir : null);
                }
                $recoursCommission = $commissionMembers->map(function ($m) {
                    $name = trim(((string)($m->prenom ?? '')) . ' ' . ((string)($m->nom ?? '')));
                    $role = trim((string)($m->role ?? ''));
                    $dir = trim((string)($m->direction ?? ''));
                    $parts = [];
                    if ($name !== '') { $parts[] = $name; }
                    if ($role !== '') { $parts[] = $role; }
                    $label = implode(' — ', $parts);
                    if ($dir !== '') { $label = $label !== '' ? ($label . ' (' . $dir . ')') : $dir; }
                    return $label;
                })->filter()->values()->all();

                // Segments prêts à afficher pour Apache ECharts (frontend ne recalcule pas)
                $now = now();
                $segments = [];
                $created = $ticket->created_at;
                $validated = $ticket->date_validation_createur;
                $enCours = $ticket->date_en_cours;
                $closed = $ticket->closed_at;
                $recours = $ticket->date_recours;
                $recoursClosed = $ticket->date_cloture_recours;

                if (!empty($created) && !empty($validated) && $validated >= $created) {
                    $segments[] = [ 'status' => 'Ouvert', 'start' => $created, 'end' => $validated ];
                }
                if (!empty($validated)) {
                    $segments[] = [ 'status' => 'En attente', 'start' => $validated, 'end' => (!empty($enCours) ? $enCours : $now) ];
                }
                if (!empty($enCours)) {
                    $segments[] = [ 'status' => 'En cours', 'start' => $enCours, 'end' => (!empty($closed) ? $closed : $now) ];
                }
                if (!empty($closed)) {
                    $segments[] = [ 'status' => 'Clôturé', 'start' => $closed, 'end' => (!empty($recours) ? $recours : $closed) ];
                }
                if (!empty($recours)) {
                    $segments[] = [ 'status' => 'Recours', 'start' => $recours, 'end' => (!empty($recoursClosed) ? $recoursClosed : $now) ];
                }
                if (!empty($recoursClosed)) {
                    $segments[] = [ 'status' => 'Recours clôturé', 'start' => $recoursClosed, 'end' => $recoursClosed ];
                }

                return [
                    'id' => $ticket->id,
                    'bticket_id' => $ticket->bticket_id,
                    'libelle' => $baseTicket ? $baseTicket->libelle : null,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                    'date_validation_createur' => $ticket->date_validation_createur,
                    'date_en_cours' => $ticket->date_en_cours,
                    'closed_at' => $ticket->closed_at,
                    'date_recours' => $ticket->date_recours,
                    'date_cloture_recours' => $ticket->date_cloture_recours,
                    'objet' => $ticket->objet, // pour tooltip
                    // compatibilité pour filtrage client-side éventuel
                    'type_name' => $baseTicket ? $baseTicket->libelle : null,
                    // affichage demandé: direction si existe sinon Nom Prénom
                    'owner_display' => $ownerDisplay,
                    // acteurs
                    'pilot_direction' => $pilotDirection,
                    'treatment_directions' => $treatmentDirections,
                    'consultation_directions' => $consultationDirections,
                    'recours_pilot' => $recoursPilot,
                    'recours_commission' => $recoursCommission,
                    // segments prêts pour ECharts
                    'segments' => $segments,
                ];
            })->filter()->values();
        }

        // Base tickets (types de réclamation)
        $baseTickets = BRecTickets::where('is_active', true)
            ->orderBy('libelle', 'asc')
            ->get(['id', 'libelle'])
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'libelle' => $t->libelle,
                ];
            });

        return [
            'items' => $items,
            'stats_precomputed' => $stats,
            'base_tickets' => $baseTickets,
        ];
    }
}
