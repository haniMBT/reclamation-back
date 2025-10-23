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
            $directions = Direction::select('NUMDIR as id', 'direction.DIRECTION as label', 'direction.DIRECTION as value')
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
}
