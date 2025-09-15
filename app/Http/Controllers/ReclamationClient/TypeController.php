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
     * Enregistrer un ou plusieurs types avec leurs détails
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'id_btickes' => 'required|exists:b_rec_tickets,id',
                'types' => 'required|array|min:1',
                'types.*.libelle' => 'required|string',
                'types.*.direction' => 'nullable|string',
                'types.*.statut_direction' => 'nullable|in:consultation,traitement',
                'types.*.details' => 'nullable|array',
                'types.*.details.*.libelle' => 'required|string',
                'types.*.details.*.direction' => 'nullable|string',
                'types.*.details.*.statut_direction' => 'nullable|in:consultation,traitement',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($request) {
                $createdTypes = [];
                
                // Créer chaque type avec ses détails
                foreach ($request->types as $typeData) {
                    // Créer le nouveau type
                    $type = BRecType::create([
                        'id_btickes' => $request->id_btickes,
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

                return response()->json([
                    'message' => count($createdTypes) > 1 ? 'Types créés avec succès' : 'Type créé avec succès',
                    'types' => $createdTypes
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création des types',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un type avec ses détails
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            // Vérifier que le type existe
            $existingType = BRecType::findOrFail($id);

            // Extraire les données du premier élément du tableau types
            $typeData = $request->input('types.0', []);

            // Validation des données
            $validator = Validator::make([
                'libelle' => $typeData['libelle'] ?? null,
                'direction' => $typeData['direction'] ?? null,
                'statut_direction' => $typeData['statut_direction'] ?? null,
                'details' => $typeData['details'] ?? []
            ], [
                'libelle' => 'required|string',
                'direction' => 'nullable|string',
                'statut_direction' => 'nullable|in:consultation,traitement',
                'details' => 'nullable|array',
                'details.*.libelle' => 'required|string',
                'details.*.direction' => 'nullable|string',
                'details.*.statut_direction' => 'nullable|in:consultation,traitement',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($id, $typeData, $existingType) {
                // Supprimer tous les détails existants
                BRecDetail::where('id_btype', $id)->delete();

                // Mettre à jour le type
                $existingType->update([
                    'libelle' => $typeData['libelle'],
                    'direction' => $typeData['direction'] ?? null,
                    'statut_direction' => $typeData['statut_direction'] ?? null
                ]);

                // Réinsérer les nouveaux détails si fournis
                if (isset($typeData['details']) && is_array($typeData['details'])) {
                    foreach ($typeData['details'] as $detailData) {
                        BRecDetail::create([
                            'id_btype' => $id,
                            'libelle' => $detailData['libelle'],
                            'direction' => $detailData['direction'] ?? null,
                            'statut_direction' => $detailData['statut_direction'] ?? null
                        ]);
                    }
                }

                // Charger la relation details pour la retourner dans la réponse
                $existingType->load('details');

                return response()->json([
                    'message' => 'Type mis à jour avec succès',
                    'type' => $existingType
                ], 200);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Type non trouvé',
                'message' => 'Le type avec l\'ID ' . $id . ' n\'existe pas.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du type',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour tous les types et détails d'un ticket
     *
     * @param int $ticketId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateByTicket(int $ticketId, Request $request): JsonResponse
    {
        try {
            // Vérifier que le ticket existe
            $ticket = BRecTickets::findOrFail($ticketId);

            // Validation des données
            $validator = Validator::make($request->all(), [
                'types' => 'required|array|min:1',
                'types.*.libelle' => 'required|string',
                'types.*.direction' => 'nullable|string',
                'types.*.statut_direction' => 'nullable|in:consultation,traitement',
                'types.*.details' => 'nullable|array',
                'types.*.details.*.libelle' => 'required|string',
                'types.*.details.*.direction' => 'nullable|string',
                'types.*.details.*.statut_direction' => 'nullable|in:consultation,traitement',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($ticketId, $request, $ticket) {
                // Supprimer tous les types existants liés au ticket
                // Les détails seront supprimés automatiquement grâce aux contraintes de clé étrangère
                $existingTypes = BRecType::where('id_btickes', $ticketId)->get();
                foreach ($existingTypes as $type) {
                    // Supprimer les détails du type
                    BRecDetail::where('id_btype', $type->id)->delete();
                }
                // Supprimer les types
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
}