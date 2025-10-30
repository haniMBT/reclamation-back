<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\BRecType;
use App\Models\ReclamationClient\BRecDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class TypeController extends Controller
{
    /**
     * Méthode centralisée pour créer/modifier tous les types et détails d'un ticket
     * Remplace les anciennes méthodes store, update et updateByTicket
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function storeOrUpdateGlobal(Request $request, int $ticketId): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'types' => 'required|array',
            'types.*.libelle' => 'required|string|max:255',
            'types.*.direction' => 'nullable|array',
            'types.*.direction.*' => 'nullable|string|max:255',
            'types.*.statut_direction' => 'nullable|string|in:consultation,traitement',
            'types.*.details' => 'nullable|array',
            'types.*.details.*.libelle' => 'required|string|max:255',
            'types.*.details.*.direction' => 'nullable|array',
            'types.*.details.*.direction.*' => 'nullable|string|max:255',
            'types.*.details.*.statut_direction' => 'nullable|string|in:consultation,traitement',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier que le ticket existe
            $ticket = BRecTickets::findOrFail($ticketId);

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($request, $ticketId, $ticket) {
                // Supprimer tous les types et détails existants du ticket
                $existingTypes = BRecType::where('id_btickes', $ticketId)->get();
                foreach ($existingTypes as $type) {
                    BRecDetail::where('id_btype', $type->id)->delete();
                }
                BRecType::where('id_btickes', $ticketId)->delete();

                $createdTypes = [];
                
                // Créer chaque nouveau type avec ses détails
                foreach ($request->types as $typeData) {
                    // Créer le nouveau type
                    $type = BRecType::create([
                        'id_btickes' => $ticketId,
                        'libelle' => $typeData['libelle'],
                        'direction' => $typeData['direction'] ?? null,
                        'statut_direction' => $typeData['statut_direction'] ?? null
                    ]);

                    // Enregistrer les détails si fournis
                    if (isset($typeData['details']) && is_array($typeData['details'])) {
                        foreach ($typeData['details'] as $detailData) {
                            BRecDetail::create([
                                'id_btype' => $type->id,
                                'libelle' => $detailData['libelle'],
                                'direction' => $detailData['direction'] ?? null,
                                'statut_direction' => $detailData['statut_direction'] ?? null
                            ]);
                        }
                    }

                    // Charger la relation details pour la retourner dans la réponse
                    $type->load('details');
                    $createdTypes[] = $type;
                }

                // Recharger le ticket avec ses nouveaux types et détails
                $ticket->load(['types.details']);

                return response()->json([
                    'message' => 'Types et détails mis à jour avec succès',
                    'ticket' => $ticket,
                    'types' => $createdTypes
                ], 200);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Ticket non trouvé',
                'message' => 'Le ticket avec l\'ID ' . $ticketId . ' n\'existe pas.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour des types et détails',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alias pour la méthode storeOrUpdateGlobal
     * Maintient la compatibilité avec les anciennes routes
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function storeOrUpdateGlobalAlias(Request $request, int $ticketId): JsonResponse
    {
        return $this->storeOrUpdateGlobal($request, $ticketId);
    }
}