<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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

            return response()->json([
                'success' => true,
                'data' => $directions,
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
}
