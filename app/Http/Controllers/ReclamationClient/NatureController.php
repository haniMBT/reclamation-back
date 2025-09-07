<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\Nature;
use App\Models\ReclamationClient\SousNature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NatureController extends Controller
{
    /**
     * Afficher toutes les natures avec leurs sous-natures (avec pagination)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');

            $query = Nature::with('sousNatures');

            // Ajouter la recherche si un terme est fourni
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('NATLIB', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('sousNatures', function($subQuery) use ($search) {
                          $subQuery->where('SOUSLIB', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            $natures = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $natures->items(),
                'pagination' => [
                    'current_page' => $natures->currentPage(),
                    'last_page' => $natures->lastPage(),
                    'per_page' => $natures->perPage(),
                    'total' => $natures->total(),
                    'from' => $natures->firstItem(),
                    'to' => $natures->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des natures',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle nature avec ses sous-natures
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'NATLIB' => 'required|string|max:255',
            'ORDRE' => 'nullable|integer',
            'sous_natures' => 'array',
            'sous_natures.*.SOUSLIB' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Créer la nature
            $nature = Nature::create([
                'NATLIB' => $request->NATLIB,
                'ORDRE' => $request->ORDRE
            ]);

            // Créer les sous-natures si elles existent
            if ($request->has('sous_natures') && is_array($request->sous_natures)) {
                foreach ($request->sous_natures as $sousNatureData) {
                    SousNature::create([
                        'SOUSLIB' => $sousNatureData['SOUSLIB'],
                        'NATID' => $nature->NATID
                    ]);
                }
            }

            // Recharger la nature avec ses sous-natures
            $nature->load('sousNatures');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nature créée avec succès',
                'data' => $nature
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la nature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une nature et ses sous-natures
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'NATLIB' => 'required|string|max:255',
            'ORDRE' => 'nullable|integer',
            'sous_natures' => 'array',
            'sous_natures.*.SOUSLIB' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $nature = Nature::findOrFail($id);

            // Mettre à jour la nature
            $nature->update([
                'NATLIB' => $request->NATLIB,
                'ORDRE' => $request->ORDRE
            ]);

            // Gérer les sous-natures
            if ($request->has('sous_natures') && is_array($request->sous_natures)) {
                $existingSousNatureIds = [];

                foreach ($request->sous_natures as $sousNatureData) {
                    if (isset($sousNatureData['SOUSID']) && $sousNatureData['SOUSID']) {
                        // Mettre à jour une sous-nature existante
                        $sousNature = SousNature::where('SOUSID', $sousNatureData['SOUSID'])
                            ->where('NATID', $nature->NATID)
                            ->first();

                        if ($sousNature) {
                            $sousNature->update(['SOUSLIB' => $sousNatureData['SOUSLIB']]);
                            $existingSousNatureIds[] = $sousNature->SOUSID;
                        }
                    } else {
                        // Créer une nouvelle sous-nature
                        $newSousNature = SousNature::create([
                            'SOUSLIB' => $sousNatureData['SOUSLIB'],
                            'NATID' => $nature->NATID
                        ]);
                        $existingSousNatureIds[] = $newSousNature->SOUSID;
                    }
                }

                // Supprimer les sous-natures qui ne sont plus dans la liste
                SousNature::where('NATID', $nature->NATID)
                    ->whereNotIn('SOUSID', $existingSousNatureIds)
                    ->delete();
            } else {
                // Si aucune sous-nature n'est envoyée, supprimer toutes les sous-natures existantes
                SousNature::where('NATID', $nature->NATID)->delete();
            }

            // Recharger la nature avec ses sous-natures
            $nature->load('sousNatures');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nature mise à jour avec succès',
                'data' => $nature
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la nature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une nature et toutes ses sous-natures
     */
    public function destroy($id): JsonResponse
    {
        try {
            $nature = Nature::findOrFail($id);

            // La suppression des sous-natures est gérée automatiquement par le boot() du modèle Nature
            $nature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nature supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la nature',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}