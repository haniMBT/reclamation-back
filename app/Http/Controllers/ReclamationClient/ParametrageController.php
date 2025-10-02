<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\BRecInfoGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ParametrageController extends Controller
{
    /**
     * Récupérer tous les tickets avec leurs infos générales, types et détails
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Récupérer tous les tickets avec leurs relations
            $tickets = BRecTickets::with([
                'infosGenerales',
                'types.details'
            ])->get();

            // Récupérer toutes les directions (basé sur MainController)
            $directions = Direction::groupby('DIRECTION')
                ->select('DIRECTION')
                ->get();

            // Formater les données pour une meilleure présentation
            $formattedTickets = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'libelle' => $ticket->libelle,
                    'documentAfornir' => $ticket->documentAfornir,
                    'direction' => $ticket->direction,
                    'definition' => $ticket->definition,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attribut' => $info->key_attirubut, // Note: utilise le nom du champ avec la faute de frappe
                        ];
                    }),
                    'types' => $ticket->types->map(function ($type) {
                        return [
                            'id' => $type->id,
                            'libelle' => $type->libelle,
                            'direction' => $type->direction,
                            'statut_direction' => $type->statut_direction,
                            'created_at' => $type->created_at,
                            'updated_at' => $type->updated_at,
                            'details' => $type->details->map(function ($detail) {
                                return [
                                    'id' => $detail->id,
                                    'libelle' => $detail->libelle,
                                    'direction' => $detail->direction,
                                    'statut_direction' => $detail->statut_direction,
                                    'created_at' => $detail->created_at,
                                    'updated_at' => $detail->updated_at,
                                ];
                            })
                        ];
                    })
                ];
            });

            $data = [
                'tickets' => $formattedTickets,
                'directions' => $directions
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un nouveau ticket avec ses infos générales
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'libelle' => 'required|string',
                'documentAfornir' => 'nullable|string',
                'direction' => 'required|string|exists:direction,DIRECTION',
                'definition' => 'nullable|string',
                'infos_generales' => 'nullable|array',
                'infos_generales.*.libelle' => 'required|string',
                'infos_generales.*.key_attribut' => 'required|boolean',
            ], [
                'libelle.required' => 'Le libellé est obligatoire',
                'libelle.string' => 'Le libellé doit être une chaîne de caractères',
                'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères',
                'documentAfornir.string' => 'Le document à fournir doit être une chaîne de caractères',
                'direction.required' => 'La direction est obligatoire',
                'direction.string' => 'La direction doit être une chaîne de caractères',
                'direction.exists' => 'La direction sélectionnée n\'existe pas',
                'infos_generales.array' => 'Les informations générales doivent être un tableau',
                'infos_generales.*.libelle.required' => 'Le libellé de l\'information générale est obligatoire',
                'infos_generales.*.libelle.string' => 'Le libellé de l\'information générale doit être une chaîne de caractères',
                'infos_generales.*.key_attribut.required' => 'L\'attribut clé est obligatoire',
                'infos_generales.*.key_attribut.boolean' => 'L\'attribut clé doit être un booléen',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($request) {
                // Créer le nouveau ticket
                $ticket = BRecTickets::create([
                    'libelle' => $request->libelle,
                    'documentAfornir' => $request->documentAfornir,
                    'direction' => $request->direction,
                    'definition' => $request->definition
                ]);

                // Enregistrer les infos générales si elles sont fournies
                if ($request->has('infos_generales') && is_array($request->infos_generales)) {
                    foreach ($request->infos_generales as $info) {
                        // Notez que nous utilisons key_attirubut (avec la faute de frappe) car c'est le nom du champ dans le modèle
                        BRecInfoGeneral::create([
                            'bticket_id' => $ticket->id,
                            'libelle' => $info['libelle'],
                            'key_attirubut' => $info['key_attribut']
                        ]);
                    }
                }

                // Charger la relation infosGenerales pour la retourner dans la réponse
                $ticket->load('infosGenerales');

                return response()->json([
                    'message' => 'Ticket créé avec succès',
                    'ticket' => $ticket
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'enregistrement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un ticket avec ses infos générales
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        try {
            // Vérifier que le ticket existe
            $ticket = BRecTickets::find($id);

            if (!$ticket) {
                return response()->json([
                    'error' => 'Ticket non trouvé',
                    'message' => 'Le ticket avec l\'ID ' . $id . ' n\'existe pas.'
                ], 404);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'libelle' => 'required|string',
                'documentAfornir' => 'nullable|string',
                'direction' => 'required|string|exists:direction,DIRECTION',
                'definition' => 'nullable|string',
                'infos_generales' => 'nullable|array',
                'infos_generales.*.libelle' => 'required|string',
                'infos_generales.*.key_attribut' => 'required|boolean',
            ], [
                'libelle.required' => 'Le libellé est obligatoire',
                'libelle.string' => 'Le libellé doit être une chaîne de caractères',
                'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères',
                'documentAfornir.string' => 'Le document à fournir doit être une chaîne de caractères',
                'direction.required' => 'La direction est obligatoire',
                'direction.string' => 'La direction doit être une chaîne de caractères',
                'direction.exists' => 'La direction sélectionnée n\'existe pas',
                'infos_generales.array' => 'Les informations générales doivent être un tableau',
                'infos_generales.*.libelle.required' => 'Le libellé de l\'information générale est obligatoire',
                'infos_generales.*.libelle.string' => 'Le libellé de l\'information générale doit être une chaîne de caractères',
                'infos_generales.*.key_attribut.required' => 'L\'attribut clé est obligatoire',
                'infos_generales.*.key_attribut.boolean' => 'L\'attribut clé doit être un booléen',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($request, $ticket) {
                // Mettre à jour le ticket
                $ticket->update([
                    'libelle' => $request->libelle,
                    'documentAfornir' => $request->documentAfornir,
                    'direction' => $request->direction,
                    'definition' => $request->definition
                ]);

                // Supprimer les anciennes infos générales
                $ticket->infosGenerales()->delete();

                // Réinsérer les nouvelles infos générales si elles sont fournies
                if ($request->has('infos_generales') && is_array($request->infos_generales)) {
                    foreach ($request->infos_generales as $info) {
                        // Notez que nous utilisons key_attirubut (avec la faute de frappe) car c'est le nom du champ dans le modèle
                        BRecInfoGeneral::create([
                            'bticket_id' => $ticket->id,
                            'libelle' => $info['libelle'],
                            'key_attirubut' => $info['key_attribut']
                        ]);
                    }
                }

                // Charger la relation infosGenerales pour la retourner dans la réponse
                $ticket->load('infosGenerales');

                return response()->json([
                    'message' => 'Ticket modifié avec succès',
                    'ticket' => $ticket
                ], 200);
            });

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la modification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un ticket spécifique avec ses relations
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            // Récupérer le ticket avec ses relations
            $ticket = BRecTickets::with([
                'infosGenerales',
                'types.details'
            ])->find($id);

            if (!$ticket) {
                return response()->json([
                    'error' => 'Ticket non trouvé',
                    'message' => 'Le ticket avec l\'ID ' . $id . ' n\'existe pas.'
                ], 404);
            }

            // Formater les données
            $formattedTicket = [
                'id' => $ticket->id,
                'libelle' => $ticket->libelle,
                'documentAfornir' => $ticket->documentAfornir,
                'direction' => $ticket->direction,
                'definition' => $ticket->definition,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'libelle' => $info->libelle,
                        'key_attribut' => $info->key_attirubut,
                    ];
                }),
                'types' => $ticket->types->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'libelle' => $type->libelle,
                        'direction' => $type->direction,
                        'statut_direction' => $type->statut_direction,
                        'created_at' => $type->created_at,
                        'updated_at' => $type->updated_at,
                        'details' => $type->details->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'libelle' => $detail->libelle,
                                'direction' => $detail->direction,
                                'statut_direction' => $detail->statut_direction,
                                'created_at' => $detail->created_at,
                                'updated_at' => $detail->updated_at,
                            ];
                        })
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedTicket
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération du ticket',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un ticket et ses relations
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Vérifier que le ticket existe
            $ticket = BRecTickets::find($id);

            if (!$ticket) {
                return response()->json([
                    'error' => 'Ticket non trouvé',
                    'message' => 'Le ticket avec l\'ID ' . $id . ' n\'existe pas.'
                ], 404);
            }

            DB::beginTransaction();

            // Supprimer les détails des types associés
            foreach ($ticket->types as $type) {
                $type->details()->delete();
            }

            // Supprimer les types associés
            $ticket->types()->delete();

            // Supprimer les infos générales associées
            $ticket->infosGenerales()->delete();

            // Supprimer le ticket
            $ticket->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket supprimé avec succès',
                'data' => [
                    'deleted_ticket_id' => $id,
                    'deleted_ticket_libelle' => $ticket->libelle
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
