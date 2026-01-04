<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\TRecCommissionRecours;
use App\Models\ReclamationClient\TRecTicketDirection;

class DashboardController extends Controller
{
    /**
     * API unique pour le tableau de bord (Timeline)
     * - Retourne la liste des tickets (items) avec les dates clés
     * - Retourne la liste des tickets de base (base_tickets)
     *
     * Filtres supportés: date_from, date_to, bticket_id
     */
    public function timelineData(Request $request): JsonResponse
    {
        try {
            $privilege = Auth::user()->scopePrivileges('liste_des_reclamations');

            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $bticketId = $request->get('bticket_id');
            $bticketIds = $request->get('bticket_ids');
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

            $statuses = $request->get('statuses');
            if (is_string($statuses)) {
                $statuses = array_filter(array_map('trim', explode(',', $statuses)));
            }
            if (!is_array($statuses)) {
                $statuses = [];
            }

            $items = collect();

            if (!empty($privilege)) {
                $query = TRecTicket::with(['baseTicket','user']);

                // Visibilité commission de recours
                $isCommissionMember = TRecCommissionRecours::where('user_id', Auth::id())->exists();

                // Portée par rôle
                $tticket_ids = collect();
                if ($privilege->role === 'employe_Répondeur') {
                    $tticket_ids = TRecTicketDirection::where('direction', Auth::user()->direction)
                        ->pluck('tticket_id');
                }

                $query->where(function ($q) use ($privilege, $tticket_ids, $isCommissionMember) {
                    if ($privilege->role === 'employe_Répondeur') {
                        $q->whereIn('id', $tticket_ids)
                          ->orWhere('user_id', Auth::id());
                    } else {
                        $q->where('user_id', Auth::id());
                    }
                    if ($isCommissionMember) {
                        $q->orWhereIn('status', ['Recours', 'Recours clôturé']);
                    }
                });

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

                // Exclusion des statuts clôturés si la source est 'timeline'
                // car la timeline ne doit afficher que les tickets actifs ou en cours
                if ($request->get('source') === 'timeline') {
                    $query->whereNotIn('status', ['clôturé', 'Recours clôturé']);
                }

                $tickets = $query->orderBy('created_at', 'desc')->get();

                // Précharger les orientations de directions pour tous les tickets en une seule requête
                $ticketIds = $tickets->pluck('id')->all();
                $directionsByTicket = collect();
                if (!empty($ticketIds)) {
                    $directionsByTicket = TRecTicketDirection::whereIn('tticket_id', $ticketIds)
                        ->get()
                        ->groupBy('tticket_id');
                }

                // Charger la composition de la commission de recours (président et membres)
                $commissionMembers = TRecCommissionRecours::select('user_id','nom','prenom','direction','role')->get();
                $commissionPresident = $commissionMembers->first(function ($m) {
                    return strtolower(trim((string)$m->role)) === 'président';
                });

                // Mapping pour la timeline
                $items = $tickets->map(function ($ticket) use ($directionsByTicket, $commissionMembers, $commissionPresident) {
                    // Règle métier obligatoire: si date_validation est NULL -> ne pas afficher la réclamation
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

            return response()->json([
                'success' => true,
                'message' => 'Données du dashboard récupérées avec succès',
                'data' => [
                    'items' => $items,
                    'base_tickets' => $baseTickets,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}