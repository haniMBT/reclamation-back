<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\BRecDefaultDirection;
use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\BRecInfoGeneral;
use App\Models\ReclamationClient\BRecTicketFile;
use App\Models\ReclamationClient\TRecCommissionRecours;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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

            $privilege_pcr = Auth::user()->scopePrivileges('parametrage_pcr');
            $privilege = Auth::user()->scopePrivileges('parametrage');

            // Récupérer tous les tickets avec leurs relations
            $tickets = BRecTickets::with([
                'infosGenerales',
                'types.details',
                'defaultDirections',
                'filesDemandes'
            ])
            ->when(in_array($privilege->visibilite, ['P', 'L']), function ($query) {
                $query->where('direction', Auth::user()->direction);
            })
            ->get()
            ->map(function ($ticket) {
                $exists = TRecTicket::where('bticket_id', $ticket->id)->exists();
                $ticket->possibilite_suppression = $exists ? 0 : 1;
                return $ticket;
            });

            $directions = Direction::groupby('DIRECTION')
                ->select('DIRECTION')
                ->get();

            $directions_visibilite = Direction::groupBy('DIRECTION')
                ->select('DIRECTION')
                    // ->whereNotIn('DIRECTION', function ($query) {
                    //     $query->select('direction')
                    //         ->from('b_rec_tickets');
                    // })
                ;
                if ($privilege->visibilite == 'P'|| $privilege->visibilite == 'L') {
                $directions_visibilite= $directions_visibilite->where('direction', Auth::user()->direction);
                }
                $directions_visibilite= $directions_visibilite->get();

            // Formater les données pour une meilleure présentation
            $formattedTickets = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'libelle' => $ticket->libelle,
                    // 'documentAfornir' => $ticket->documentAfornir,
                    'direction' => $ticket->direction,
                    'possibilite_suppression' => $ticket->possibilite_suppression,
                    'definition' => $ticket->definition,
                    'is_active' => $ticket->is_active,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attribut' => $info->key_attirubut, // Note: utilise le nom du champ avec la faute de frappe
                            'obligatoire' => (bool) ($info->obligatoire ?? false),
                            'type' => $info->type,
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
                    }),
                    'files_demandes' => $ticket->filesDemandes->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'libelle' => $file->libelle,
                            'obligatoire' => (bool) ($file->obligatoire ?? false),
                            'format' => $file->format_fichier,
                        ];
                    }),
                    'default_directions' => $ticket->defaultDirections->map(function ($d) {
                        return [
                            'id' => $d->id,
                            'direction' => $d->direction,
                            'statut_direction' => $d->statut_direction,
                            'created_at' => $d->created_at,
                            'updated_at' => $d->updated_at,
                        ];
                    })
                ];
            });

            // Charger les utilisateurs disponibles pour la commission (filtrage par visibilité si nécessaire)
            $usersQuery = User::query();
            // if (in_array($privilege->visibilite, ['P','L'])) {
            //     $usersQuery->where('direction', Auth::user()->direction);
            // }
            $users = $usersQuery->select('id','Nom','Prenom','Email','Matricule','direction')->get();

            // Charger la composition actuelle de la commission de recours
            $commission = TRecCommissionRecours::select('id','user_id','nom','prenom','email','matricule','direction','role','created_at','updated_at')
                ->orderByDesc('role') // président en premier
                ->get();

            $data = [
                'tickets' => $formattedTickets,
                'directions' => $directions,
                'directions_visibilite' => $directions_visibilite,
                'privilege' => $privilege,
                'privilege_pcr' => $privilege_pcr,
                'users' => $users,
                'commission_recours' => $commission,
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
     * Enregistrer / mettre à jour la composition de la commission de recours
     */
    public function saveCommissionRecours(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'president_id' => 'required|',
                'member_ids' => 'nullable|array',
                // 'member_ids.*' => 'string'
            ], [
                'president_id.required' => 'Le président est obligatoire.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $presidentId = $request->input('president_id');
            $memberIds = collect($request->input('member_ids', []))
                ->filter(fn($id) => $id !== $presidentId)
                ->unique()
                ->values();

            // Vérifier l'existence des utilisateurs
            $allUserIds = collect([$presidentId])->merge($memberIds)->values();
            $users = User::whereIn('id', $allUserIds)->get()->keyBy('id');

            if (!$users->has($presidentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Président introuvable'
                ], 404);
            }

            // Réinitialiser la composition (remplacer les anciens enregistrements)
            DB::transaction(function () use ($users, $presidentId, $memberIds) {
                TRecCommissionRecours::query()->delete();

                // Enregistrer le président
                $p = $users->get($presidentId);
                TRecCommissionRecours::create([
                    'user_id' => $p->id,
                    'nom' => $p->Nom ?? null,
                    'prenom' => $p->Prenom ?? null,
                    'email' => $p->Email ?? null,
                    'matricule' => $p->Matricule ?? null,
                    'direction' => $p->direction ?? null,
                    'role' => 'président',
                ]);

                // Enregistrer les membres
                foreach ($memberIds as $mid) {
                    if ($users->has($mid)) {
                        $m = $users->get($mid);
                        TRecCommissionRecours::create([
                            'user_id' => $m->id,
                            'nom' => $m->Nom ?? null,
                            'prenom' => $m->Prenom ?? null,
                            'email' => $m->Email ?? null,
                            'matricule' => $m->Matricule ?? null,
                            'direction' => $m->direction ?? null,
                            'role' => 'membre',
                        ]);
                    }
                }
            });

            $commission = TRecCommissionRecours::select('id','user_id','nom','prenom','email','matricule','direction','role','created_at','updated_at')
                ->orderByDesc('role')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Composition de la commission mise à jour',
                'commission_recours' => $commission
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la commission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/désactiver un ticket. Lors de l'activation, désactiver les autres tickets de la même direction.
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        try {
            $ticket = BRecTickets::find($id);
            if (!$ticket) {
                return response()->json([
                    'error' => 'Ticket non trouvé',
                    'message' => 'Le ticket avec l\'ID ' . $id . ' n\'existe pas.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            return DB::transaction(function () use ($request, $ticket) {
                $newState = (bool) $request->is_active;

                // if ($newState === true) {
                //     // Désactiver tous les autres tickets de la même direction
                //     BRecTickets::where('direction', $ticket->direction)
                //         ->where('id', '!=', $ticket->id)
                //         ->update(['is_active' => false]);
                // }
                // Mettre à jour l'état du ticket courant
                $ticket->update(['is_active' => $newState]);

                return response()->json([
                    'message' => 'Etat du ticket mis à jour',
                    'ticket' => $ticket->fresh()
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour de l\'état',
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
                // 'documentAfornir' => 'nullable|string',
                'direction' => 'required|string|exists:direction,DIRECTION',
                'definition' => 'nullable|string',
                'infos_generales' => 'nullable|array',
                'infos_generales.*.libelle' => 'required|string',
                'infos_generales.*.key_attribut' => 'required|boolean',
                'infos_generales.*.obligatoire' => 'required|boolean',
                'infos_generales.*.type' => 'nullable|string|in:date,texte,montant,numéro',
                'files_demandes' => 'nullable|array',
                'files_demandes.*.libelle' => 'required|string',
                'files_demandes.*.obligatoire' => 'required|boolean',
                'files_demandes.*.format' => 'nullable|string',
            ], [
                'libelle.required' => 'Le libellé est obligatoire',
                'libelle.string' => 'Le libellé doit être une chaîne de caractères',
                'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères',
                // 'documentAfornir.string' => 'Le document à fournir doit être une chaîne de caractères',
                'direction.required' => 'La direction est obligatoire',
                'direction.string' => 'La direction doit être une chaîne de caractères',
                'direction.exists' => 'La direction sélectionnée n\'existe pas',
                'infos_generales.array' => 'Les informations générales doivent être un tableau',
                'infos_generales.*.libelle.required' => 'Le libellé de l\'information générale est obligatoire',
                'infos_generales.*.libelle.string' => 'Le libellé de l\'information générale doit être une chaîne de caractères',
                'infos_generales.*.key_attribut.required' => 'L\'attribut clé est obligatoire',
                'infos_generales.*.key_attribut.boolean' => 'L\'attribut clé doit être un booléen',
                'infos_generales.*.obligatoire.required' => 'Le caractère obligatoire est requis',
                'infos_generales.*.obligatoire.boolean' => 'Le caractère obligatoire doit être un booléen',
                'infos_generales.*.type.in' => 'Le type de champ doit être parmi: date, texte, montant, numéro',
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
                    // 'documentAfornir' => $request->documentAfornir,
                    'direction' => $request->direction,
                    'definition' => $request->definition,
                    'is_active' => false,
                ]);

                // Enregistrer les infos générales si elles sont fournies
                if ($request->has('infos_generales') && is_array($request->infos_generales)) {
                    foreach ($request->infos_generales as $info) {
                        // Notez que nous utilisons key_attirubut (avec la faute de frappe) car c'est le nom du champ dans le modèle
                        BRecInfoGeneral::create([
                            'bticket_id' => $ticket->id,
                            'libelle' => $info['libelle'],
                            'key_attirubut' => $info['key_attribut'],
                            'obligatoire' => $info['obligatoire'],
                            'type' => $info['type'] ?? null,
                        ]);
                    }
                }

                // Enregistrer les fichiers demandés si fournis
                if ($request->has('files_demandes') && is_array($request->files_demandes)) {
                    foreach ($request->files_demandes as $file) {
                        BRecTicketFile::create([
                            'bticket_id' => $ticket->id,
                            'libelle' => $file['libelle'],
                            'obligatoire' => $file['obligatoire'],
                            'format_fichier' => $file['format'] ?? null,
                        ]);
                    }
                }

                // Charger les relations pour la réponse
                $ticket->load(['infosGenerales','filesDemandes']);

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
                // 'documentAfornir' => 'nullable|string',
                'direction' => 'required|string|exists:direction,DIRECTION',
                'definition' => 'nullable|string',
                'infos_generales' => 'nullable|array',
                'infos_generales.*.libelle' => 'required|string',
                'infos_generales.*.key_attribut' => 'required|boolean',
                'infos_generales.*.obligatoire' => 'required|boolean',
                'infos_generales.*.type' => 'nullable|string|in:date,texte,montant,numéro',
                'files_demandes' => 'nullable|array',
                'files_demandes.*.libelle' => 'required|string',
                'files_demandes.*.obligatoire' => 'required|boolean',
                'files_demandes.*.format' => 'nullable|string',
            ], [
                'libelle.required' => 'Le libellé est obligatoire',
                'libelle.string' => 'Le libellé doit être une chaîne de caractères',
                'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères',
                // 'documentAfornir.string' => 'Le document à fournir doit être une chaîne de caractères',
                'direction.required' => 'La direction est obligatoire',
                'direction.string' => 'La direction doit être une chaîne de caractères',
                'direction.exists' => 'La direction sélectionnée n\'existe pas',
                'infos_generales.array' => 'Les informations générales doivent être un tableau',
                'infos_generales.*.libelle.required' => 'Le libellé de l\'information générale est obligatoire',
                'infos_generales.*.libelle.string' => 'Le libellé de l\'information générale doit être une chaîne de caractères',
                'infos_generales.*.key_attribut.required' => 'L\'attribut clé est obligatoire',
                'infos_generales.*.key_attribut.boolean' => 'L\'attribut clé doit être un booléen',
                'infos_generales.*.obligatoire.required' => 'Le caractère obligatoire est requis',
                'infos_generales.*.obligatoire.boolean' => 'Le caractère obligatoire doit être un booléen',
                'infos_generales.*.type.in' => 'Le type de champ doit être parmi: date, texte, montant, numéro',
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
                    // 'documentAfornir' => $request->documentAfornir,
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
                            'key_attirubut' => $info['key_attribut'],
                            'obligatoire' => $info['obligatoire'],
                            'type' => $info['type'] ?? null,
                        ]);
                    }
                }

                // Supprimer les anciens fichiers demandés
                $ticket->filesDemandes()->delete();

                // Réinsérer les nouveaux fichiers demandés si fournis
                if ($request->has('files_demandes') && is_array($request->files_demandes)) {
                    foreach ($request->files_demandes as $file) {
                        BRecTicketFile::create([
                            'bticket_id' => $ticket->id,
                            'libelle' => $file['libelle'],
                            'obligatoire' => $file['obligatoire'],
                            'format_fichier' => $file['format'] ?? null,
                        ]);
                    }
                }

                // Charger les relations pour la réponse
                $ticket->load(['infosGenerales','filesDemandes']);

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
                'types.details',
                'defaultDirections',
                'filesDemandes'
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
                'is_active' => $ticket->is_active,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'libelle' => $info->libelle,
                        'key_attribut' => $info->key_attirubut,
                        'obligatoire' => (bool) ($info->obligatoire ?? false),
                        'type' => $info->type,
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
                }),
                'files_demandes' => $ticket->filesDemandes->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'libelle' => $file->libelle,
                        'obligatoire' => (bool) ($file->obligatoire ?? false),
                        'format' => $file->format_fichier,
                    ];
                }),
                'default_directions' => $ticket->defaultDirections->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'direction' => $d->direction,
                        'statut_direction' => $d->statut_direction,
                        'created_at' => $d->created_at,
                        'updated_at' => $d->updated_at,
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

            // Vérification d'usage: empêcher la suppression si le ticket est déjà utilisé
            if (TRecTicket::where('bticket_id', $ticket->id)->exists()) {
                return response()->json([
                    'error' => 'Suppression impossible',
                    'message' => 'Ce ticket est déjà utilisé dans des réclamations et ne peut pas être supprimé.'
                ], 409);
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

    /**
     * Lister toutes les directions automatiques configurées
     */
    public function defaultDirectionsIndex(Request $request): JsonResponse
    {
        try {
            $privilege = Auth::user()->scopePrivileges('parametrage');

            $query = BRecDefaultDirection::with(['ticket:id,libelle,direction']);

            // Respecter la visibilité: P/L -> filtrer sur la direction de l'utilisateur via le ticket associé
            // Inclure également les entrées globales (bticket_id NULL)
            if (in_array($privilege->visibilite, ['P', 'L'])) {
                $userDirection = Auth::user()->direction;
                $query->where(function ($q) use ($userDirection) {
                    $q->whereHas('ticket', function ($qq) use ($userDirection) {
                        $qq->where('direction', $userDirection);
                    })
                    ->orWhereNull('bticket_id');
                })->where('direction', $userDirection);
            }

            $items = $query->orderBy('id', 'desc')->get()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'direction' => $d->direction,
                    'statut_direction' => $d->statut_direction,
                    'bticket_id' => $d->bticket_id,
                    'bticket_libelle' => optional($d->ticket)->libelle,
                    'ticket_direction' => optional($d->ticket)->direction,
                    'created_at' => $d->created_at,
                    'updated_at' => $d->updated_at,
                ];
            });

            // Options pour les selects côté UI
            $tickets = BRecTickets::select('id', 'libelle', 'direction')
                ->when(in_array($privilege->visibilite, ['P', 'L']), function ($q) {
                    $q->where('direction', Auth::user()->direction);
                })
                ->orderBy('libelle')
                ->get();

            $directions = Direction::groupBy('DIRECTION')->select('DIRECTION')
                ->when(in_array($privilege->visibilite, ['P', 'L']), function ($q) {
                    $q->where('direction', Auth::user()->direction);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items,
                'tickets' => $tickets,
                'directions' => $directions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des directions automatiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une nouvelle direction automatique
     */
    public function defaultDirectionsStore(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'direction' => 'required|string|exists:direction,DIRECTION',
                'statut_direction' => 'required|string|in:consultation,traitement',
                'bticket_id' => 'nullable|integer|exists:b_rec_tickets,id',
            ], [
                'direction.required' => 'La direction est obligatoire',
                'direction.exists' => 'La direction sélectionnée n\'existe pas',
                'statut_direction.required' => 'Le statut est obligatoire',
                'statut_direction.in' => 'Le statut doit être consultation ou traitement',
                'bticket_id.exists' => 'Le ticket sélectionné n\'existe pas',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Empêcher les doublons sur (direction, bticket_id nullable) — ignore le statut
            $existsQuery = BRecDefaultDirection::query()
                ->where('direction', $request->direction);

            if (empty($request->bticket_id)) {
                $existsQuery->whereNull('bticket_id');
            } else {
                $existsQuery->where('bticket_id', $request->bticket_id);
            }

            $exists = $existsQuery->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette direction automatique existe déjà pour ce libellé de ticket (ou global).',
                ], 409);
            }

            $item = BRecDefaultDirection::create([
                'bticket_id' => $request->bticket_id,
                'direction' => $request->direction,
                'statut_direction' => $request->statut_direction,
            ]);

            $item->load('ticket');

            return response()->json([
                'success' => true,
                'message' => 'Direction automatique ajoutée',
                'data' => [
                    'id' => $item->id,
                    'direction' => $item->direction,
                    'statut_direction' => $item->statut_direction,
                    'bticket_id' => $item->bticket_id,
                    'bticket_libelle' => optional($item->ticket)->libelle,
                    'ticket_direction' => optional($item->ticket)->direction,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la direction automatique',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une direction automatique
     */
    public function defaultDirectionsDestroy(int $id): JsonResponse
    {
        try {
            $privilege = Auth::user()->scopePrivileges('parametrage');

            $item = BRecDefaultDirection::with('ticket')->find($id);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Direction automatique introuvable',
                ], 404);
            }

            // Visibilité: si P/L, ne permettre la suppression que si ticket.direction = user.direction ou bticket_id NULL
            if (in_array($privilege->visibilite, ['P', 'L'])) {
                $userDirection = Auth::user()->direction;
                $ticketDirection = optional($item->ticket)->direction;
                if (!is_null($ticketDirection) && $ticketDirection !== $userDirection) {
                    return response()->json([
                        'success' => false,
                        'message' => "Suppression non autorisée pour cette direction",
                    ], 403);
                }
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Direction automatique supprimée',
                'data' => [ 'deleted_id' => $id ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la direction automatique',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
