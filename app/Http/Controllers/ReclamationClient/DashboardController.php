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

                $tickets = $query->orderBy('created_at', 'desc')->get();

                // Mapping pour la timeline
                $items = $tickets->map(function ($ticket) {
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
                        'objet' => $ticket->objet, // <- ajout pour tooltip
                        // compatibilité pour filtrage client-side éventuel
                        'type_name' => $baseTicket ? $baseTicket->libelle : null,
                        // affichage demandé: direction si existe sinon Nom Prénom
                        'owner_display' => $ownerDisplay,
                    ];
                });
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