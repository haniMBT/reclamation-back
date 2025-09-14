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
}