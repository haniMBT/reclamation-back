<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\ReclamationClient\TRecTicketDirection;
use App\Models\ReclamationClient\TRecTicket;
use App\Services\ReclamationClient\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DirectionController extends Controller
{
    /**
     * Récupérer toutes les directions
     *
     * @return JsonResponse
     */
   public function index($ticket_id): JsonResponse
    {
        try {
            $directions = Direction::select(
                    'NUMDIR as id',
                    'direction.DIRECTION as label',
                    'direction.DIRECTION as value',
                    't_rec_ticket_direction.type_orientation as type_orientation',
                    't_rec_ticket_direction.statut_direction as statut_direction'
                )
                ->orderBy('direction.DIRECTION', 'asc')->join('t_rec_ticket_direction', 'direction.DIRECTION', '=', 't_rec_ticket_direction.direction')
                ->where('t_rec_ticket_direction.tticket_id', $ticket_id)
                ->get();

             $directionsNonConcerne = Direction::select(
                    'NUMDIR as id',
                    'direction.DIRECTION as label',
                    'direction.DIRECTION as value'
                )
                ->whereNotIn('direction.DIRECTION', function ($q) use ($ticket_id) {
                    $q->select('t_rec_ticket_direction.direction')
                    ->from('t_rec_ticket_direction')
                    ->where('t_rec_ticket_direction.tticket_id', $ticket_id)
                    ->whereNotNull('t_rec_ticket_direction.direction');
                })
                ->orderBy('direction.DIRECTION', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $directions,
                'directionsNonConcerne' => $directionsNonConcerne,
                'message' => 'Directions récupérées avec succès'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des directions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des directions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer une direction par son ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $direction = Direction::select('NUMDIR as id', 'DIRECTION as label', 'DIRECTION as value')
                ->where('NUMDIR', $id)
                ->first();

            if (!$direction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Direction non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $direction,
                'message' => 'Direction récupérée avec succès'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la direction: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la direction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle direction
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate(Direction::validationRules());

            $direction = Direction::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $direction->NUMDIR,
                    'label' => $direction->DIRECTION,
                    'value' => $direction->DIRECTION
                ],
                'message' => 'Direction créée avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la direction: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la direction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une direction
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $direction = Direction::find($id);

            if (!$direction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Direction non trouvée'
                ], 404);
            }

            $validatedData = $request->validate(Direction::validationRulesForUpdate($id));

            $direction->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $direction->NUMDIR,
                    'label' => $direction->DIRECTION,
                    'value' => $direction->DIRECTION
                ],
                'message' => 'Direction mise à jour avec succès'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la direction: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la direction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une direction
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $direction = Direction::find($id);

            if (!$direction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Direction non trouvée'
                ], 404);
            }

            $direction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Direction supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la direction: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la direction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les directions liées à un ticket spécifique
     *
     * @param int $ticketId
     * @return JsonResponse
     */
    public function getDirectionsByTicket(int $ticketId): JsonResponse
    {
        try {
            $directions = TRecTicketDirection::select('direction as value', 'direction as label')
                ->where('tticket_id', $ticketId)
                ->whereNotNull('direction')
                ->distinct()
                ->orderBy('direction', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $directions,
                'message' => 'Directions du ticket récupérées avec succès'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des directions du ticket: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des directions du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter une direction au ticket (t_rec_ticket_direction)
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function storeTicketDirection(Request $request, int $ticketId): JsonResponse
    {
        try {
            // Validation des données entrantes
            $validated = $request->validate([
                'direction' => 'required|string|min:2',
                'statut_direction' => 'required|in:traitement,consultation',
            ]);

            // Récupérer la direction de l'utilisateur connecté pour source_orientation
            $user = Auth::user();
            $sourceOrientation = is_object($user) && isset($user->direction) ? $user->direction : null;

            // Création de l'orientation direction du ticket
            $created = TRecTicketDirection::create([
                'tticket_id' => $ticketId,
                'direction' => $validated['direction'],
                'statut_direction' => $validated['statut_direction'],
                'source_orientation' => $sourceOrientation,
                'type_orientation' => 'direction',
            ]);

            // Créer les notifications d'ajout de direction
            try {
                $ticket = TRecTicket::find($ticketId);
                if ($ticket) {
                    $notificationService = new NotificationService();
                    $notificationService->createDirectionAddedNotifications(
                        $ticket,
                        $validated['direction'],
                        $validated['statut_direction'],
                        Auth::id()
                    );
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de la création des notifications d'ajout de direction: " . $e->getMessage());
                // Ne pas faire échouer la requête principale si les notifications échouent
            }

            return response()->json([
                'success' => true,
                'message' => 'Direction ajoutée au ticket avec succès',
                'data' => $created,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ajout de la direction au ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la direction au ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une ou plusieurs directions du ticket (t_rec_ticket_direction)
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function deleteTicketDirections(Request $request, int $ticketId): JsonResponse
    {
        try {
            // Validation des données entrantes
            $validated = $request->validate([
                'directions' => 'required|array|min:1',
                'directions.*' => 'required|string|min:2',
            ]);

            // Récupérer les directions avant suppression pour les notifications
            $directionsToDelete = TRecTicketDirection::where('tticket_id', $ticketId)
                ->whereIn('direction', $validated['directions'])
                ->get();

            // Suppression de toutes les occurrences des directions sélectionnées pour ce ticket
            $deletedCount = TRecTicketDirection::where('tticket_id', $ticketId)
                ->whereIn('direction', $validated['directions'])
                ->delete();

            // Créer les notifications de suppression de direction
            if ($deletedCount > 0) {
                try {
                    $ticket = TRecTicket::find($ticketId);
                    if ($ticket) {
                        $notificationService = new NotificationService();
                        foreach ($directionsToDelete as $directionRecord) {
                            $notificationService->createDirectionRemovedNotifications(
                                $ticket,
                                $directionRecord->direction,
                                Auth::id()
                            );
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la création des notifications de suppression de direction: " . $e->getMessage());
                    // Ne pas faire échouer la requête principale si les notifications échouent
                }
            }

            return response()->json([
                'success' => true,
                'message' => $deletedCount . ' direction(s) supprimée(s) du ticket',
                'data' => [
                    'deleted_count' => $deletedCount,
                    'ticket_id' => $ticketId,
                    'directions' => $validated['directions'],
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des directions du ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression des directions du ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer la direction de l'utilisateur lorsqu'elle est en état "changement_accepter".
     * Cette suppression cible uniquement les enregistrements de type_orientation = 'changement_accepter'
     * pour la direction de l'utilisateur connecté, et le ticket donné.
     */
    public function deleteSelfAcceptedChangeDirection(Request $request, int $ticketId): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDirection = is_object($user) && isset($user->direction) ? $user->direction : null;

            if (!$userDirection) {
                return response()->json([
                    'success' => false,
                    'message' => "Direction de l'utilisateur introuvable",
                ], 403);
            }

            // Vérifier l'existence d'un lien de type 'changement_accepter' pour cette direction
            $query = TRecTicketDirection::where('tticket_id', $ticketId)
                ->where('direction', $userDirection)
                ->where('type_orientation', 'changement_accepter');

            $records = $query->get();
            if ($records->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Aucune direction à supprimer pour l'utilisateur dans l'état 'changement_accepter'",
                ], 404);
            }

            $deletedCount = $query->delete();

            // Notifications de suppression (facultatif, cohérent avec la suppression générique)
            try {
                $ticket = TRecTicket::find($ticketId);
                if ($ticket) {
                    $notificationService = new NotificationService();
                    $notificationService->createDirectionRemovedNotifications(
                        $ticket,
                        $userDirection,
                        Auth::id()
                    );

                    // Informer la direction pilote (type_orientation = 'ticket') du retrait de cette direction
                    $pilotRecord = TRecTicketDirection::where('tticket_id', $ticketId)
                        ->where('type_orientation', 'ticket')
                        ->first();
                    if ($pilotRecord && !empty($pilotRecord->direction)) {
                        // Envoyer une notification aux employés répondeurs de la direction pilote
                        if (method_exists($notificationService, 'createInformPilotOnSelfDirectionRemoval')) {
                            $notificationService->createInformPilotOnSelfDirectionRemoval(
                                $ticket,
                                $pilotRecord->direction,
                                $userDirection,
                                Auth::id()
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de la création des notifications de suppression (self): " . $e->getMessage());
                // Ne pas faire échouer la requête si la notification échoue
            }

            return response()->json([
                'success' => true,
                'message' => $deletedCount . " direction(s) supprimée(s) pour l'utilisateur",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'ticket_id' => $ticketId,
                    'direction' => $userDirection,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la suppression de la direction de l'utilisateur: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de la suppression de la direction",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Orientation changement: ajoute la direction si absente et enregistre le motif du changement.
     */
    public function storeOrientationChange(Request $request, int $ticketId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'direction' => 'required|string|min:2',
                'motif' => 'required|string|min:3',
            ]);

            $ticket = TRecTicket::findOrFail($ticketId);

            $directionValue = $validated['direction'];

            // Vérifier si la direction est déjà associée
            $exists = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->where('direction', $directionValue)
                ->exists();

            if (!$exists) {
                $direction = new TRecTicketDirection();
                $direction->tticket_id = $ticket->id;
                $direction->direction = $directionValue;
                $direction->statut_direction = 'traitement';
                $direction->type_orientation = 'changement';
                if (property_exists($direction, 'source_orientation')) {
                    $direction->source_orientation = Auth::user()->direction ?? null;
                }
                $direction->save();
            }else{
                $TRecTicketDirection = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->where('direction', $directionValue)
                ->first();

                $exists = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->where('direction', $directionValue)
                ->update([
                    'type_orientation' => 'changement',
                    'old_orientation' => $TRecTicketDirection->type_orientation,
                ]);
            }

            // Enregistrer le motif du changement
            $ticket->motif_changement = $validated['motif'];
            $ticket->save();

            // Notifications aux employés répondeurs de la direction pilote concernée
            try {
                $notificationService = new \App\Services\ReclamationClient\NotificationService();
                $notificationService->createPilotChangeNotifications($ticket, $directionValue, Auth::id());
            } catch (\Exception $e) {
                Log::error("Erreur lors de la création des notifications orientation_changement: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Orientation changée et motif enregistré',
                'data' => [
                    'direction_added' => !$exists,
                    'ticket_id' => $ticket->id,
                    'direction' => $directionValue,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement d\'orientation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement d\'orientation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Décision sur la demande de changement de direction pilote (accepter / refuser).
     */
    public function decideOrientationChange(Request $request, int $ticketId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'decision' => 'required|string|in:accept,refuse',
                'direction' => 'required|string|min:2',
                'motif_refus' => 'nullable|string',
            ]);

            $ticket = TRecTicket::findOrFail($ticketId);
            $userDirection = Auth::user()->direction ?? null;
            $concernedDirection = $validated['direction'];

            // Vérifier qu'il existe une demande de changement pour cette direction
            $changeRequest = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->where('direction', $concernedDirection)
                ->whereIn('type_orientation', ['changement'])
                ->first();

            if (!$changeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune demande de changement trouvée pour cette direction',
                ], 404);
            }

            // Sécurité: seule la direction concernée peut valider/refuser
            if ($userDirection !== $changeRequest->direction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé: direction non concernée',
                ], 403);
            }

            if ($validated['decision'] === 'accept') {
                // Marquer l'ancienne direction pilote
                $currentPilot = TRecTicketDirection::where('tticket_id', $ticket->id)
                    ->where('type_orientation', 'ticket')
                    ->first();

                if ($currentPilot) {
                    $currentPilot->type_orientation = 'changement_accepter';
                    $currentPilot->save();
                }

                // La direction de la demande devient pilote
                $changeRequest->type_orientation = 'ticket';
                $changeRequest->statut_direction = 'traitement';
                $changeRequest->save();

                // Mettre à jour le ticket
                $ticket->accepter_piloter = true;
                $ticket->motif_refu_changement = null;
                $ticket->direction = $concernedDirection;
                $ticket->save();

                // Notifications d’acceptation pour les employés répondeurs de la nouvelle direction pilote
                try {
                    $notificationService = new \App\Services\ReclamationClient\NotificationService();
                    $notificationService->createPilotChangeNotifications($ticket, $concernedDirection, Auth::id());
                } catch (\Exception $e) {
                    Log::error("Erreur notification acceptation changement pilote: " . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Changement de direction pilote accepté',
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'new_pilot_direction' => $concernedDirection,
                    ],
                ], 200);
            } else {
                // Refus: enregistrer motif
                if (empty($validated['motif_refus'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le motif de refus est obligatoire',
                    ], 422);
                }

                $ticket->accepter_piloter = false;
                $ticket->motif_refu_changement = $validated['motif_refus'];
                $ticket->save();

                // Notification à la direction demandeuse (source_orientation si disponible, sinon changeRequest->direction)
                // $requestingDirection = $changeRequest->source_orientation ?: $changeRequest->direction;
                 // Vérifier qu'il existe une demande de changement pour cette direction
                $requestingDirection = TRecTicketDirection::where('tticket_id', $ticket->id)
                    ->whereIn('type_orientation', ['ticket'])
                    ->first()->direction;

                // La direction de la demande devient pilote
                if ($changeRequest->source_orientation==null) {
                    $changeRequest->delete();
                }
                else {
                    $changeRequest->type_orientation = $changeRequest->old_orientation;
                    $changeRequest->old_orientation = null;
                    $changeRequest->save();
                }


                try {
                    $notificationService = new \App\Services\ReclamationClient\NotificationService();
                    if (method_exists($notificationService, 'createPilotChangeRefusalNotifications')) {
                        $notificationService->createPilotChangeRefusalNotifications(
                            $ticket,
                            $requestingDirection,
                            $userDirection ?? 'inconnu',
                            $validated['motif_refus'],
                            Auth::id()
                        );
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur notification refus changement pilote: " . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Changement de direction pilote refusé',
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'requesting_direction' => $requestingDirection,
                    ],
                ], 200);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la décision de changement d\'orientation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la décision de changement d\'orientation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
