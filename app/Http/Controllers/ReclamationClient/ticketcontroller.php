<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\TRecType;
use App\Models\ReclamationClient\TRecDetail;
use App\Models\ReclamationClient\BRecType;
use App\Models\ReclamationClient\BRecDetail;
use App\Models\ReclamationClient\FichierClient;
use App\Models\ReclamationClient\TRecCommissionRecours;
use App\Models\ReclamationClient\TRecTicketFile;
use App\Models\ReclamationClient\TRecInfoGeneral;
use Illuminate\Support\Facades\Auth;
use App\Models\ReclamationClient\TRecTicketDirection;
use App\Models\ReclamationClient\BRecDefaultDirection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\ReclamationClient\NotificationService;

class TicketController extends Controller
{
    /**
     * Récupère tous les tickets avec leurs informations générales.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Récupérer tous les tickets avec leurs informations générales
            $tickets = BRecTickets::with('infosGenerales')
                ->where('is_active', true)
                ->orderBy('libelle', 'asc')
                ->get();

            // Formater les données pour le frontend
            $formattedTickets = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'libelle' => $ticket->libelle,
                    'documentAfornir' => $ticket->documentAfornir,
                    'direction' => $ticket->direction,
                    'definition' => $ticket->definition,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'date_en_cours' => $ticket->date_en_cours,
                    'date_recours' => $ticket->date_recours,
                    'date_cloture_recours' => $ticket->date_cloture_recours,
                    'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attirubut' => $info->key_attirubut,
                            'bticket_id' => $info->bticket_id,
                            'type' => $info->type ?? null
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Tickets récupérés avec succès',
                'data' => $formattedTickets
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère tous les tickets avec pagination, recherche et filtres avancés.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function indexAll(Request $request): JsonResponse
    {
        try {

            $privilege = Auth::user()->scopePrivileges('liste_des_reclamations');

            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $search = $request->get('q', '');
            $direction = $request->get('direction', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('date_from', '');
            $dateTo = $request->get('date_to', '');
            $typeId = $request->get('type_id', '');

               // Privilege
            if (!empty($privilege)) {
                // Query de base avec eager loading
                $query = TRecTicket::with([
                    'baseTicket.infosGenerales',
                    'types.bRecType',
                    'types.details.bRecDetail',
                    'user' // Ajouter la relation avec l'utilisateur créateur
                ]);


                if ($privilege->role == 'employe_Répondeur') {
                //    if(Auth::user()->direction == 'CAB'){
                        $tticket_ids = TRecTicketDirection::where('direction', Auth::user()->direction)
                        ->pluck('tticket_id');
                        $query->where(function ($q) use ($tticket_ids) {
                            $q->whereIn('t_rec_tickets.id', $tticket_ids)
                            ->orWhere('t_rec_tickets.user_id', Auth::id());
                        });
                    // }
                }else{ // esq tous les utilisateur en le droit de cree une reclamaation ou non
                    $query->where('t_rec_tickets.user_id', Auth::id());
                }

                // // Logique complémentaire: membres de la commission de recours
                $isCommissionMember = TRecCommissionRecours::where('user_id', Auth::id())->exists();
                if ($isCommissionMember) {
                    // Étendre la visibilité: inclure les tickets en recours ou recours clôturé
                    $query->orWhere(function ($q) {
                        $q->whereIn('t_rec_tickets.status', ['Recours', 'Recours clôturé']);
                    });
                }


            // Filtrage par recherche globale
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
                      ->orWhere('objet', 'like', '%' . $search . '%')
                      ->orWhereHas('baseTicket', function ($subQ) use ($search) {
                          $subQ->where('libelle', 'like', '%' . $search . '%')
                               ->orWhere('direction', 'like', '%' . $search . '%');
                      });
                });
            }

            // Filtrage par direction
            if (!empty($direction)) {
                $query->whereHas('baseTicket', function ($q) use ($direction) {
                    $q->where('direction', $direction);
                });
            }

            // Filtrage par statut (si applicable)
            if (!empty($status)) {
                $query->where('status', $status);
            }

            // Filtrage par date de création
            if (!empty($dateFrom)) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if (!empty($dateTo)) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Filtrage par type
            if (!empty($typeId)) {
                $query->whereHas('types', function ($q) use ($typeId) {
                    $q->where('b_rec_type_id', $typeId);
                });
            }

            // Pagination avec tri
            $tickets = $query->orderBy('created_at', 'desc')
                           ->paginate($perPage, ['*'], 'page', $page);

            } else {
                $tickets = null;
            }

            // Formater les données
            $formattedTickets = $tickets?->getCollection()->map(function ($ticket) {
                $baseTicket = $ticket->baseTicket;
                $currentUserId = Auth::id();

                // Logique conditionnelle pour afficher le nom/prénom du créateur
                // Masquer pour le créateur lui-même
                $creatorInfo = null;
                if ($ticket->user_id !== $currentUserId && $ticket->user) {
                    $creatorInfo = [
                        'nom' => $ticket->user->Nom ?? $ticket->nom,
                        'prenom' => $ticket->user->Prenom ?? $ticket->prenom,
                        'nom_complet' => trim(($ticket->user->Prenom ?? $ticket->prenom) . ' ' . ($ticket->user->Nom ?? $ticket->nom))
                    ];
                }

                return [
                    'id' => $ticket->id,
                    'bticket_id' => $ticket->bticket_id,
                    'user_id' => $ticket->user_id,
                    'direction' => $ticket->direction,
                    'status' => $ticket->status ?? 'OUVERT',
                    'description' => $ticket->description,
                    'is_creator_validated' => $ticket->is_creator_validated,
                    'objet' => $ticket->objet,
                    'closed_at' => $ticket->closed_at,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'date_validation_createur' => $ticket->date_validation_createur,
                    'date_en_cours' => $ticket->date_en_cours,
                    'date_recours' => $ticket->date_recours,
                    'date_cloture_recours' => $ticket->date_cloture_recours,
                    'libelle' => $baseTicket ? $baseTicket->libelle : null,
                    'definition' => $baseTicket ? $baseTicket->definition : null,
                    'documentAfornir' => $baseTicket ? $baseTicket->documentAfornir : null,
                    'createur' => $creatorInfo, // Informations du créateur (null si c'est le créateur lui-même)
                    'infos_generales' => $baseTicket && $baseTicket->infosGenerales ? $baseTicket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attribut' => $info->key_attribut,
                            'type' => $info->type ?? null,
                              'bticket_id' => $info->bticket_id
                        ];
                    }) : [],
                    'types' => $ticket->types->map(function ($type) {
                        return [
                            'id' => $type->id,
                            'b_rec_type_id' => $type->b_rec_type_id,
                            'libelle' => $type->libelle,
                            'type_info' => $type->bRecType ? [
                                'id' => $type->bRecType->id,
                                'libelle' => $type->bRecType->libelle,
                            ] : null,
                            'details' => $type->details->map(function ($detail) {
                                return [
                                    'id' => $detail->id,
                                    'b_rec_detail_id' => $detail->b_rec_detail_id,
                                    'libelle' => $detail->libelle,
                                    'detail_info' => $detail->bRecDetail ? [
                                        'id' => $detail->bRecDetail->id,
                                        'libelle' => $detail->bRecDetail->libelle,
                                    ] : null,
                                ];
                            })
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Tickets récupérés avec succès',
                'data' => [
                    'privilege' => $privilege,
                    'is_commission_member' => isset($isCommissionMember) ? $isCommissionMember : false,
                    'items' => $formattedTickets,
                    'meta' => [
                        'current_page' => $tickets->currentPage(),
                        'per_page' => $tickets->perPage(),
                        'total' => $tickets->total(),
                        'last_page' => $tickets->lastPage(),
                        'from' => $tickets->firstItem(),
                        'to' => $tickets->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie s'il existe déjà une réclamation avec les mêmes informations clés.
     * Si aucun doublon, insère les données dans t_rec_tickets et t_rec_info_general.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'bticket_id' => 'required|integer|exists:b_rec_tickets,id',
                'user_id' => 'required|integer|min:1',
                'direction' => 'required|string|in:ENTRANT,SORTANT',
                'status' => 'required|string|in:OUVERT,FERME,EN_COURS',
                'objet' => 'nullable|string|min:1|max:255',
                'info_general_data' => 'required|array|min:1',
                'info_general_data.*.info_general_id' => 'required|integer|min:1',
                'info_general_data.*.value' => 'required|string|min:1|max:255',
                'info_general_data.*.key_attribut' => 'required|boolean',
                'info_general_data.*.type' => 'nullable|string|in:date,texte,montant,numéro',
                'ignore_duplicate' => 'sometimes|boolean'
            ], [
                'bticket_id.required' => 'L\'identifiant du ticket est requis.',
                'bticket_id.exists' => 'Le ticket sélectionné n\'existe pas.',
                'objet.min' => 'L\'objet ne peut pas être vide.',
                'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
                'info_general_data.required' => 'Les informations générales sont requises.',
                'info_general_data.min' => 'Au moins une information générale est requise.',
                'info_general_data.*.value.required' => 'La valeur du champ est requise.',
                'info_general_data.*.value.min' => 'La valeur du champ ne peut pas être vide.',
                'info_general_data.*.value.max' => 'La valeur du champ ne peut pas dépasser 255 caractères.',
                'info_general_data.*.type.in' => 'Le type de champ doit être parmi: date, texte, montant, numéro'
            ]);

            $bticketId = $request->input('bticket_id');
            $userId = $request->input('user_id');
            $direction = BRecTickets::find($bticketId)->direction;
            $status = $request->input('status');
            $objet = $request->input('objet');
            $infoGeneralData = $request->input('info_general_data');

            // Option de contournement: créer directement sans vérifier les doublons
            if ($request->boolean('ignore_duplicate')) {
                return $this->insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData, $objet);
            }

            // Filtrer seulement les champs avec key_attribut = true
            $keyAttributes = collect($infoGeneralData)->filter(function ($item) {
                return $item['key_attribut'] === true;
            });

            // Si aucun attribut clé à vérifier, insérer directement
            if ($keyAttributes->isEmpty()) {
                return $this->insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData, $objet);
            }

            // Nouvelle logique: trouver les tickets (tticket_id) qui possèdent TOUTES les paires (info_general_id, value)
            $matchingTicketIds = null;
            foreach ($keyAttributes as $attr) {
                $ids = collect(DB::table('t_rec_info_general')
                    ->where('info_general_id', $attr['info_general_id'])
                    ->where('value', $attr['value'])
                    ->pluck('tticket_id'))
                    ->unique();

                if ($matchingTicketIds === null) {
                    $matchingTicketIds = $ids;
                } else {
                    $matchingTicketIds = $matchingTicketIds->intersect($ids);
                }
            }

            $matchingTicketIdsArr = $matchingTicketIds ? $matchingTicketIds->values()->toArray() : [];

            // Un doublon existe si au moins un ticket correspond à toutes les informations clés
            $isDuplicate = count($matchingTicketIdsArr) > 0;

            if ($isDuplicate) {
                // Charger toutes les réclamations correspondantes et leurs fichiers de conclusion
                $existingTickets = \App\Models\ReclamationClient\TRecTicket::with(['files' => function ($query) {
                    $query->where('mode', 'conclusion');
                }])
                ->whereIn('id', $matchingTicketIdsArr)
                ->whereIn('status', ['clôturé', 'Recours clôturé'])
                ->orderByDesc('closed_at')
                ->get();

                $duplicates = $existingTickets->map(function ($ticket) {
                    $closureFiles = $ticket->files->map(function ($fichier) {
                        return [
                            'id' => $fichier->id,
                            'nom_fichier' => $fichier->nom_fichier ?? $fichier->filename,
                            'chemin_fichier' => $fichier->chemin_fichier ?? $fichier->path,
                            'taille_fichier' => isset($fichier->taille_fichier) ? $fichier->taille_fichier : ($fichier->size ?? 0),
                            'type_fichier' => $fichier->type_fichier ?? $fichier->mime_type,
                            'created_at' => $fichier->created_at,
                        ];
                    })->toArray();

                    return [
                        'id' => $ticket->id,
                        'objet' => $ticket->objet,
                        'description' => $ticket->description,
                        'conclusion' => $ticket->conclusion,
                        'closed_at' => $ticket->closed_at,
                        'files' => $closureFiles,
                    ];
                })->values()->toArray();

                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Cette réclamation (ou des réclamations similaires) ont déjà été traitées.',
                    'duplicate_found' => true,
                    'duplicates' => $duplicates,
                ], 200);
            }

            // Aucun doublon trouvé, insérer les données
            return $this->insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData, $objet);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des doublons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Insère les données dans t_rec_tickets et t_rec_info_general
     *
     * @param int $bticketId
     * @param int $userId
     * @param string $direction
     * @param string $status
     * @param array $infoGeneralData
     * @param string|null $objet
     * @return JsonResponse
     */
    private function insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData, $objet = null): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Insérer dans t_rec_tickets
            $ticketId = DB::table('t_rec_tickets')->insertGetId([
                'bticket_id' => $bticketId,
                'user_id' => auth::user()->id,
                'direction' => $direction,
                'status' => $status,
                'objet' => $objet,
                'nom' => auth::user()->Nom,
                'prenom' => auth::user()->Prenom,
                'created_at' => now(),
                'updated_at' => now(),
                'reply_permission' => 'employe_Répondeur',
                'closed_at' => null
            ]);

            // Insérer dans t_rec_info_general
            foreach ($infoGeneralData as $infoData) {
                DB::table('t_rec_info_general')->insert([
                    'tticket_id' => $ticketId,
                    'info_general_id' => $infoData['info_general_id'],
                    'libelle' => $infoData['libelle'],
                    'value' => $infoData['value'],
                    'key_attribut' => $infoData['key_attribut'],
                    'type' => $infoData['type'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation créée avec succès',
                'duplicate_found' => false,
                'data' => [
                    't_rec_ticket_id' => $ticketId,
                    'b_rec_ticket_id' => $bticketId,
                    'ticket_data' => [
                        'bticket_id' => $bticketId,
                        'user_id' => auth::user()->id,
                        'direction' => $direction,
                        'status' => $status,
                        'info_general_data' => $infoGeneralData
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupère les données complètes d'un ticket avec ses types et détails
     *
     * @param int $ticketId
     * @return JsonResponse
     */
    public function getCompleteTicketData($ticketId): JsonResponse
    {
        try {
            // Récupérer le ticket de base avec ses informations
            $baseTicket = BRecTickets::with('infosGenerales')->find($ticketId);

            if (!$baseTicket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket non trouvé'
                ], 404);
            }

            // Récupérer les types associés (t_rec_type)
            $types = DB::table('b_rec_type')
                ->where('id_btickes', $ticketId)
                ->get();

            // Pour chaque type, récupérer ses détails (t_rec_detail)
            $typesWithDetails = $types->map(function ($type) {
                $details = DB::table('b_rec_detail')
                    ->where('id_btype', $type->id)
                    ->get();

                return [
                    'id' => $type->id,
                    'name' => $type->libelle ?? $type->name ?? 'Type ' . $type->id,
                    'description' => $type->description ?? null,
                    'details' => $details->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'label' => $detail->libelle ?? $detail->label ?? 'Détail ' . $detail->id,
                            'type' => $detail->type ?? 'text', // text, textarea, select, checkbox, etc.
                            'required' => (bool) ($detail->required ?? false),
                            'placeholder' => $detail->placeholder ?? null,
                            'default_value' => $detail->default_value ?? null,
                            'options' => null
                        ];
                    })->toArray()
                ];
            });

            // Formater les données du ticket
            $ticketData = [
                'id' => $baseTicket->id,
                'libelle' => $baseTicket->libelle,
                'documentAFournir' => $baseTicket->documentAfornir,
                'direction' => $baseTicket->direction,
                'created_at' => $baseTicket->created_at,
                'updated_at' => $baseTicket->updated_at,
                'infos_generales' => $baseTicket->infosGenerales->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'libelle' => $info->libelle,
                        'key_attribut' => $info->key_attirubut,
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Données du ticket récupérées avec succès',
                'data' => [
                    'ticket' => $ticketData,
                    'types' => $typesWithDetails->toArray()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalise une réclamation avec les informations complémentaires
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeTicket(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ticket_id' => 'required|integer|exists:t_rec_tickets,id',
                'objet' => 'required|string|min:1|max:255',
                'description' => 'required|string|min:10',
                // 'type_details' => 'required|string', // JSON string
                'files.*' => 'nullable|file|max:10240' // 10MB max per file
            ], [
                'ticket_id.required' => 'L\'identifiant du ticket est requis.',
                'ticket_id.exists' => 'Le ticket spécifié n\'existe pas.',
                'objet.required' => 'L\'objet est obligatoire.',
                'objet.min' => 'L\'objet ne peut pas être vide.',
                'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
                'description.required' => 'La description détaillée est requise.',
                'description.min' => 'La description doit contenir au moins 10 caractères.',
                'files.*.max' => 'Chaque fichier ne peut pas dépasser 10MB.'
            ]);

            $ticketId = $request->input('ticket_id');
            $objet = $request->input('objet');
            $description = $request->input('description');
            $typeDetails = json_decode($request->input('type_details'), true);

            DB::beginTransaction();

            // Mettre à jour la description du ticket
            DB::table('t_rec_tickets')
                ->where('id', $ticketId)
                ->update([
                    'objet' => $objet,
                    'description' => $description,
                    'status' => 'COMPLETE',
                    'updated_at' => now()
                ]);

            // Sauvegarder les détails des types
            if ($typeDetails && is_array($typeDetails)) {
                foreach ($typeDetails as $detailId => $value) {
                    DB::table('t_rec_ticket_details')->updateOrInsert(
                        [
                            'ticket_id' => $ticketId,
                            'detail_id' => $detailId
                        ],
                        [
                            'value' => $value,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }

            // Gérer les fichiers joints
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('reclamations/tickets/' . $ticketId, $filename, 'public');

                    DB::table('t_rec_ticket_files')->insert([
                        'ticket_id' => $ticketId,
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation finalisée avec succès',
                'data' => [
                    'ticket_id' => $ticketId,
                    'status' => 'COMPLETE',
                    'objet' => $objet
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation de la réclamation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalise une réclamation en sauvegardant la description, les fichiers, types et détails.
     * Format standardisé du payload :
     * {
     *   "tticket_id": 123,
     *   "b_rec_ticket_id": 45,
     *   "description": "Texte descriptif",
     *   "type_selection": [
     *     {
     *       "b_rec_type_id": 1,
     *       "libelle": "Type A",
     *       "details": [
     *         { "b_rec_detail_id": 10, "libelle": "Détail 1" },
     *         { "b_rec_detail_id": null, "libelle": "Autre texte libre" }
     *       ]
     *     }
     *   ],
     *   "files": [ fichier1, fichier2 ]
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveComplete(Request $request): JsonResponse
    {
        try {
            // Décoder le type_selection s'il est en JSON
            $typeSelection = $request->input('type_selection');
            if (is_string($typeSelection)) {
                $typeSelection = json_decode($typeSelection, true);
            }

            // Validation de la requête avec le nouveau format
            $validatedData = $request->validate([
                'tticket_id' => 'required|integer|exists:t_rec_tickets,id',
                'b_rec_ticket_id' => 'nullable|integer',
                'objet' => 'required|string',
                'description' => 'nullable|string|max:5000',
                'files' => 'nullable|array',
                'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt', // 10MB max
            ], [
                'tticket_id.required' => 'L\'identifiant du ticket est requis.',
                'tticket_id.exists' => 'Le ticket spécifié n\'existe pas.',
                'files.*.max' => 'La taille du fichier ne peut pas dépasser 10 MB.',
                'files.*.mimes' => 'Le format du fichier n\'est pas autorisé.',
            ]);

            // Normaliser type_selection (facultatif)
            if (!$typeSelection || !is_array($typeSelection)) {
                $typeSelection = [];
            }

            // Valider chaque type dans type_selection (si fourni)
            foreach ($typeSelection as $index => $typeData) {
                if (isset($typeData['libelle']) && empty(trim($typeData['libelle']))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreurs de validation',
                        'errors' => ["type_selection.{$index}.libelle" => ['Le libellé du type est requis si fourni.']]
                    ], 422);
                }

                if (isset($typeData['details']) && is_array($typeData['details'])) {
                    foreach ($typeData['details'] as $detailIndex => $detailData) {
                        if (isset($detailData['libelle']) && empty(trim($detailData['libelle']))) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Erreurs de validation',
                                'errors' => ["type_selection.{$index}.details.{$detailIndex}.libelle" => ['Le libellé du détail est requis si fourni.']]
                            ], 422);
                        }
                    }
                }
            }

            // Démarrer une transaction
            DB::beginTransaction();

            $tticketId = $validatedData['tticket_id'];
            $typesSavedCount = 0;
            $detailsSavedCount = 0;

            // Mise à jour de la description du ticket
            if (isset($validatedData['description']) && !empty(trim($validatedData['description']))) {
                TRecTicket::where('id', $tticketId)
                    ->update([
                        'description' => trim($validatedData['description']),
                        'objet' => trim($validatedData['objet'])
                    ]);
            }

            // Gestion des fichiers uploadés
            if ($request->hasFile('files')) {
                $this->handleTicketFileUploads($request->file('files'), $tticketId);
            }

            // Insertion des types et détails selon le nouveau format (si fourni)
            foreach ($typeSelection as $typeData) {
                // Créer le type avec le nouveau format
                $tRecType = TRecType::create([
                    'tticket_id' => $tticketId,
                    'b_rec_type_id' => isset($typeData['b_rec_type_id']) ? (int)$typeData['b_rec_type_id'] : null,
                    'libelle' => trim($typeData['libelle']),
                ]);

                $typesSavedCount++;

                // Insérer les détails associés
                if (isset($typeData['details']) && is_array($typeData['details'])) {
                    foreach ($typeData['details'] as $detailData) {
                        TRecDetail::create([
                            't_rec_type_id' => $tRecType->id,
                            'b_rec_detail_id' => isset($detailData['b_rec_detail_id']) ? (int)$detailData['b_rec_detail_id'] : null,
                            'libelle' => trim($detailData['libelle']),
                        ]);
                        $detailsSavedCount++;
                    }
                }
            }

            // Récupérer les informations du ticket pour la réponse
            $ticket = TRecTicket::find($tticketId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation complétée avec succès',
                'data' => [
                    'tticket_id' => $tticketId,
                    'b_rec_ticket_id' => $ticket->bticket_id ?? null,
                    'types_saved_count' => $typesSavedCount,
                    'details_saved_count' => $detailsSavedCount
                ]
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation de la réclamation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gérer l'upload des fichiers pour un ticket.
     *
     * @param array $files
     * @param int $tticketId
     * @return void
     */
    private function handleTicketFileUploads(array $files, int $tticketId, ?string $mode = null): void
    {
        foreach ($files as $file) {
            if ($file->isValid()) {
                // Générer un nom unique pour le fichier
                $nomStockage = $this->generateUniqueFileName($file);

                // Définir le chemin de stockage
                $cheminStockage = 'tickets/' . date('Y/m');

                // Stocker le fichier
                $cheminComplet = $file->storeAs($cheminStockage, $nomStockage, 'public');

                // Enregistrer les informations du fichier en base
                TRecTicketFile::create([
                    'ticket_id' => $tticketId,
                    'nom_fichier' => $file->getClientOriginalName(),
                    'chemin_fichier' => 'public/' . $cheminComplet,
                    'taille_fichier' => $file->getSize(),
                    'type_fichier' => $file->getClientMimeType(),
                    'mode' => $mode,
                ]);
            }
        }
    }

    /**
     * Récupère les données complètes d'un ticket créé (TRecTicket) pour l'édition
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getTicketForEdit($id): JsonResponse
    {
        try {
            // Récupérer le ticket créé avec toutes ses relations
            $ticket = TRecTicket::with([
                'baseTicket.infosGenerales',
                'baseTicket.types.details',
                'types.details',
                'infosGenerales.baseInfoGeneral',
                'files'
            ])->find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket non trouvé'
                ], 404);
            }

            // Formater les données pour le frontend
            $ticketData = [
                'id' => $ticket->id,
                'bticket_id' => $ticket->bticket_id,
                'user_id' => $ticket->user_id,
                'direction' => $ticket->direction,
                'status' => $ticket->status,
                'description' => $ticket->description,
                'objet' => $ticket->objet,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'base_ticket' => [
                    'id' => $ticket->baseTicket->id,
                    'libelle' => $ticket->baseTicket->libelle,
                    'documentAFournir' => $ticket->baseTicket->documentAfornir,
                    'direction' => $ticket->baseTicket->direction,
                    'definition' => $ticket->baseTicket->definition,
                    'types' => $ticket->baseTicket->types->map(function ($type) {
                        return [
                            'id' => $type->id,
                            'libelle' => $type->libelle,
                            'details' => $type->details->map(function ($detail) {
                                return [
                                    'id' => $detail->id,
                                    'libelle' => $detail->libelle,
                                ];
                            })
                        ];
                    }),
                    'infos_generales' => $ticket->baseTicket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attribut' => $info->key_attirubut,
                        ];
                    })
                ],
                'types' => $ticket->types->map(function ($type) {
                    // Séparer les détails normaux des valeurs "Autre"
                    $normalDetails = $type->details->filter(function ($detail) {
                        return $detail->b_rec_detail_id !== null;
                    });

                    $autreDetails = $type->details->filter(function ($detail) {
                        return $detail->b_rec_detail_id === null;
                    });

                    return [
                        'id' => $type->id,
                        'b_rec_type_id' => $type->b_rec_type_id,
                        'libelle' => $type->libelle,
                        'details' => $normalDetails->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'b_rec_detail_id' => $detail->b_rec_detail_id,
                                'libelle' => $detail->libelle,
                            ];
                        }),
                        'autre' => $autreDetails->first() ? $autreDetails->first()->libelle : ''
                    ];
                }),
                'files' => $ticket->files
                ->whereNull('mode') // ✅ on filtre d'abord les fichiers dont le champ "mode" est null
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'nom_fichier' => $file->nom_fichier,
                        'chemin_fichier' => $file->chemin_fichier,
                        'taille_fichier' => $file->taille_fichier,
                        'type_fichier' => $file->type_fichier,
                        'created_at' => $file->created_at,
                    ];
                }),
                'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'info_general_id' => $info->info_general_id,
                        'libelle' => $info->baseInfoGeneral ? $info->baseInfoGeneral->libelle : $info->libelle,
                        'key_attribut' => $info->key_attribut,
                        'value' => $info->value,
                        'type' => $info->baseInfoGeneral ? $info->baseInfoGeneral->type : ($info->type ?? null),
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Données du ticket récupérées avec succès',
                'data' => $ticketData,
                'ticket' => $ticket
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un ticket créé (TRecTicket) avec ses types, détails et fichiers
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateTicket(Request $request, $id): JsonResponse
    {
        try {
            // Vérifier que le ticket existe
            $ticket = TRecTicket::find($id);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket non trouvé'
                ], 404);
            }

            // Décoder le type_selection s'il est en JSON
            $typeSelection = $request->input('type_selection');
            if (is_string($typeSelection)) {
                $typeSelection = json_decode($typeSelection, true);
            }

            //  return response()->json([
            //         'message' => $request->all()
            //     ], 404);
            // Décoder les infos_generales s'il est en JSON
            $infosGenerales = $request->input('infos_generales');
            if (is_string($infosGenerales)) {
                $infosGenerales = json_decode($infosGenerales, true);
            }

            // Décoder files_to_delete s'il est en JSON
            $filesToDelete = $request->input('files_to_delete');
            if (is_string($filesToDelete)) {
                $filesToDelete = json_decode($filesToDelete, true);
                $request->merge(['files_to_delete' => $filesToDelete]);
            }

            // Validation de la requête
            $validatedData = $request->validate([
                'objet' => 'nullable|string|min:1|max:255',
                'description' => 'nullable|string|max:5000',
                'files' => 'nullable|array',
                'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
                'files_to_delete' => 'nullable|array',
                'files_to_delete.*' => 'integer|exists:t_rec_ticket_files,id',
            ], [
                'objet.min' => 'L\'objet ne peut pas être vide.',
                'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
                'files.*.max' => 'La taille du fichier ne peut pas dépasser 10 MB.',
                'files.*.mimes' => 'Le format du fichier n\'est pas autorisé.',
            ]);

            // Normaliser type_selection (facultatif)
            if (!$typeSelection || !is_array($typeSelection)) {
                $typeSelection = [];
            }

            DB::beginTransaction();

            // Mettre à jour l'objet et la description du ticket
            $updateData = [];
            if (isset($validatedData['objet'])) {
                $updateData['objet'] = trim($validatedData['objet']);
            }
            if (isset($validatedData['description'])) {
                $updateData['description'] = trim($validatedData['description']);
            }
            if (!empty($updateData)) {
                $ticket->update($updateData);
            }

            // Supprimer les anciens types et détails
            foreach ($ticket->types as $type) {
                $type->details()->delete();
            }
            $ticket->types()->delete();

            // Insérer les nouveaux types et détails
            foreach ($typeSelection as $typeData) {
                $tRecType = TRecType::create([
                    'tticket_id' => $ticket->id,
                    'b_rec_type_id' => isset($typeData['b_rec_type_id']) ? (int)$typeData['b_rec_type_id'] : null,
                    'libelle' => trim($typeData['libelle']),
                ]);

                if (isset($typeData['details']) && is_array($typeData['details'])) {
                    foreach ($typeData['details'] as $detailData) {
                        TRecDetail::create([
                            't_rec_type_id' => $tRecType->id,
                            'b_rec_detail_id' => isset($detailData['b_rec_detail_id']) ? (int)$detailData['b_rec_detail_id'] : null,
                            'libelle' => trim($detailData['libelle']),
                        ]);
                    }
                }

                // Traiter la valeur "autre" si elle existe
                if (isset($typeData['autre']) && !empty(trim($typeData['autre']))) {
                    TRecDetail::create([
                        't_rec_type_id' => $tRecType->id,
                        'b_rec_detail_id' => null,
                        'libelle' => trim($typeData['autre']),
                    ]);
                }
            }

            // Mettre à jour les informations générales
            if ($infosGenerales && is_array($infosGenerales)) {
                // Supprimer les anciennes informations générales
                TRecInfoGeneral::where('tticket_id', $ticket->id)->delete();

                // Insérer les nouvelles informations générales
                foreach ($infosGenerales as $infoData) {
                    if (isset($infoData['valeur']) && !empty(trim($infoData['valeur']))) {
                        TRecInfoGeneral::create([
                            'tticket_id' => $ticket->id,
                            'info_general_id' => $infoData['id'],
                            'libelle' => isset($infoData['libelle']) ? trim($infoData['libelle']) : '',
                            'value' => trim($infoData['valeur']),
                            'key_attribut' => true,
                            'type' => isset($infoData['type']) ? trim($infoData['type']) : null,
                        ]);
                    }
                }
            }

            // Supprimer les fichiers demandés
            if (isset($validatedData['files_to_delete']) && !empty($validatedData['files_to_delete'])) {
                foreach ($validatedData['files_to_delete'] as $fileId) {
                    $file = TRecTicketFile::where('id', $fileId)->where('ticket_id', $ticket->id)->first();
                    if ($file) {
                        // Supprimer le fichier physique
                        if (Storage::exists($file->chemin_fichier)) {
                            Storage::delete($file->chemin_fichier);
                        }
                        // Supprimer l'enregistrement en base
                        $file->delete();
                    }
                }
            }

            // Ajouter les nouveaux fichiers
            if ($request->hasFile('files')) {
                $this->handleTicketFileUploads($request->file('files'), $ticket->id);
            }

            // Forcer la mise à jour du champ updated_at du ticket principal
            $ticket->touch();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket mis à jour avec succès',
                'data' => [
                    'ticket_id' => $ticket->id
                ]
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharge un fichier joint à un ticket.
     *
     * @param int $fileId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadFile(int $fileId)
    {
        try {
            // Récupérer le fichier
            $fichier = TRecTicketFile::findOrFail($fileId);

            // Vérifier que le fichier existe sur le disque
            if (!Storage::exists($fichier->chemin_fichier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé sur le serveur'
                ], 404);
            }

            // Retourner le fichier en téléchargement
            return Storage::download(
                $fichier->chemin_fichier,
                $fichier->nom_fichier,
                [
                    'Content-Type' => $fichier->type_fichier,
                    'Content-Length' => $fichier->taille_fichier
                ]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du fichier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un nom de fichier unique.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    private function generateUniqueFileName($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $nomSansExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Nettoyer le nom du fichier
        $nomSansExtension = Str::slug($nomSansExtension, '_');

        do {
            // Créer un nom de fichier avec timestamp et UUID court
            $timestamp = now()->format('Ymd_His');
            $uniqueId = substr(Str::uuid()->toString(), 0, 8);
            $fileName = "{$nomSansExtension}_{$timestamp}_{$uniqueId}.{$extension}";
        } while (Storage::disk('public')->exists("tickets/{$fileName}"));

        return $fileName;
    }

    /**
     * Supprimer un ticket créé et toutes ses relations (tables t_rec_ seulement)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Vérifier que le ticket créé existe
            $ticket = TRecTicket::find($id);

            if (!$ticket) {
                return response()->json([
                    'error' => 'Ticket non trouvé',
                    'message' => 'Le ticket avec l\'ID ' . $id . ' n\'existe pas.'
                ], 404);
            }

            DB::beginTransaction();

            // Supprimer les fichiers associés au ticket
            foreach ($ticket->files as $file) {
                // Supprimer le fichier physique
                if (Storage::exists($file->chemin_fichier)) {
                    Storage::delete($file->chemin_fichier);
                }
                // Supprimer l'enregistrement en base
                $file->delete();
            }

            // Supprimer les détails des types du ticket
            foreach ($ticket->types as $type) {
                $type->details()->delete();
            }

            // Supprimer les types du ticket
            $ticket->types()->delete();

            // Supprimer les informations générales du ticket
            $ticket->infosGenerales()->delete();

            // Supprimer le ticket
            $ticket->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket supprimé avec succès',
                'data' => [
                    'deleted_ticket_id' => $id
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
     * Valide un ticket en mettant à jour le champ is_creator_validated
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateTicket(Request $request): JsonResponse
    {
        try {
            // Validation des données d'entrée
            $request->validate([
                'ticket_id' => 'required|integer|exists:t_rec_tickets,id',
            ]);

            $ticketId = $request->input('ticket_id');

            // Récupérer le ticket
            $ticket = TRecTicket::findOrFail($ticketId);
            $bticket = BRecTickets::findOrFail($ticket->bticket_id);

            // Vérifier si le ticket n'est pas déjà validé
            if ($ticket->is_creator_validated) {
                return response()->json([
                    'error' => 'Le ticket est déjà validé',
                    'message' => 'Ce ticket a déjà été validé'
                ], 400);
            }

            // 1. Récupérer la direction du ticket et l'insérer dans t_rec_ticket_direction
            TRecTicketDirection::create([
                'tticket_id' => $ticketId,
                'direction' => $ticket->direction,
                'statut_direction' => 'traitement',
                'source_orientation' => $bticket->libelle,
                'type_orientation' => 'ticket'
            ]);

            // 2. Récupérer les types sélectionnés et leurs directions
            $ticketTypes = TRecType::where('tticket_id', $ticketId)->get();
            foreach ($ticketTypes as $tRecType) {
                if ($tRecType->b_rec_type_id) {
                    $bRecType = BRecType::find($tRecType->b_rec_type_id);
                    if ($bRecType && $bRecType->direction != null) {
                        $typeDirections = is_array($bRecType->direction) ? $bRecType->direction : [$bRecType->direction];
                        foreach ($typeDirections as $dir) {
                            TRecTicketDirection::create([
                                'tticket_id' => $ticketId,
                                'direction' => $dir,
                                'statut_direction' => $bRecType->statut_direction,
                                'source_orientation' => $bRecType->libelle,
                                'type_orientation' => 'type'
                            ]);
                        }
                    }
                }
            }

            // 3. Récupérer les détails sélectionnés et leurs directions
            $ticketDetails = TRecDetail::whereIn('t_rec_type_id', $ticketTypes->pluck('id'))->get();
            foreach ($ticketDetails as $tRecDetail) {
                if ($tRecDetail->b_rec_detail_id) {
                    $bRecDetail = BRecDetail::find($tRecDetail->b_rec_detail_id);
                    if ($bRecDetail && $bRecDetail->direction != null) {
                        $detailDirections = is_array($bRecDetail->direction) ? $bRecDetail->direction : [$bRecDetail->direction];
                        foreach ($detailDirections as $dir) {
                            TRecTicketDirection::create([
                                'tticket_id' => $ticketId,
                                'direction' => $dir,
                                'statut_direction' => $bRecDetail->statut_direction,
                                'source_orientation' => $bRecDetail->libelle,
                                'type_orientation' => 'detail'
                            ]);
                        }
                    }
                }
            }

              // 0. Directions par défaut paramétrées pour le ticket de base
            $existingDirections = TRecTicketDirection::where('tticket_id', $ticketId)
                ->pluck('direction');

            $defaultDirections = BRecDefaultDirection::where(function ($q) use ($ticket) {
                    $q->where('bticket_id', $ticket->bticket_id)
                    ->orWhereNull('bticket_id');
                })
                ->whereNotIn('direction', $existingDirections)
                ->get();

            foreach ($defaultDirections as $defDir) {
                TRecTicketDirection::create([
                    'tticket_id' => $ticketId,
                    'direction' => $defDir->direction,
                    'statut_direction' => $defDir->statut_direction ?? 'traitement',
                    'source_orientation' => $bticket->libelle,
                    'type_orientation' => 'default'
                ]);
            }

            // 4. Mettre à jour le champ is_creator_validated à 1, le statut à 'En attente' et enregistrer la date de validation
            $ticket->update([
                'is_creator_validated' => 1,
                'status' => 'En attente',
                'date_validation_createur' => now()
            ]);

            // 5. Créer les notifications automatiques pour les employés répondeurs des directions concernées
            $notificationService = new NotificationService();
            $notificationService->createTicketValidationNotifications($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Ticket validé avec succès',
                'data' => [
                    'ticket_id' => $ticketId,
                    'is_creator_validated' => true
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la validation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les détails complets d'un ticket créé par un client
     *
     * @param int $ticketId
     * @return JsonResponse
     */
    public function getTicketDetails(int $ticketId): JsonResponse
    {
        try {
            // Récupérer le ticket avec toutes ses relations
            $ticket = TRecTicket::with([
                'types.bRecType',
                'types.details.bRecDetail',
                'infosGenerales.bRecInfoGeneral',
                'fichiers',
                'directions'
            ])->find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket non trouvé'
                ], 404);
            }

            // Récupérer le ticket de base pour les informations générales
            $baseTicket = BRecTickets::with('infosGenerales')->find($ticket->bticket_id);

            // Formater les données pour le frontend
            $ticketDetails = [
                'id' => $ticket->id,
                'objet' => $ticket->objet,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'date_validation_createur' => $ticket->date_validation_createur,
                'is_creator_validated' => $ticket->is_creator_validated,

                // Informations du ticket de base
                'type_name' => $baseTicket ? $baseTicket->libelle : null,
                'definition' => $baseTicket ? $baseTicket->definition : null,
                'document_a_fournir' => $baseTicket ? $baseTicket->documentAfornir : null,

                // Types sélectionnés avec leurs détails
                'types' => $ticket->types->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'libelle' => $type->bRecType ? $type->bRecType->libelle : null,
                        'direction' => $type->bRecType ? $type->bRecType->direction : null,
                        'statut_direction' => $type->bRecType ? $type->bRecType->statut_direction : null,
                        'details' => $type->details->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'libelle' => $detail->bRecDetail ? $detail->bRecDetail->libelle : null,
                                'direction' => $detail->bRecDetail ? $detail->bRecDetail->direction : null,
                                'statut_direction' => $detail->bRecDetail ? $detail->bRecDetail->statut_direction : null,
                            ];
                        })
                    ];
                }),

                // Informations générales avec leurs valeurs
                'info_general' => $ticket->infosGenerales->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'libelle' => $info->bRecInfoGeneral ? $info->bRecInfoGeneral->libelle : null,
                        'key_attribut' => $info->bRecInfoGeneral ? $info->bRecInfoGeneral->key_attirubut : null,
                        'value' => $info->value
                    ];
                }),

                // Fichiers joints
                'fichiers' => $ticket->fichiers->map(function ($fichier) {
                    return [
                        'id' => $fichier->id,
                        'nom_fichier' => $fichier->filename,
                        'chemin' => $fichier->path,
                        'taille_fichier' => $this->formatFileSize($fichier->size),
                        'type_fichier' => $fichier->mime_type,
                        'created_at' => $fichier->created_at
                    ];
                }),

                // Directions destinataires
                'directions' => $ticket->directions->map(function ($direction) {
                    return [
                        'id' => $direction->id,
                        'direction' => $direction->direction,
                        'statut_direction' => $direction->statut_direction,
                        'source_orientation' => $direction->source_orientation,
                        'type_orientation' => $direction->type_orientation
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Détails du ticket récupérés avec succès',
                'data' => $ticketDetails
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formate la taille d'un fichier en format lisible
     *
     * @param int $size
     * @return string
     */
    private function formatFileSize(int $size): string
    {
        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }

    /**
     * Télécharge un fichier joint d'un ticket
     *
     * @param int $fileId
     * @return \Illuminate\Http\Response
     */
    public function downloadTicketFile(int $fileId)
    {
        try {
            // Récupérer le fichier
            $file = TRecTicketFile::find($fileId);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], 404);
            }

            // Vérifier si le fichier existe sur le disque
            if (!Storage::disk('public')->exists($file->path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé sur le serveur'
                ], 404);
            }

            // Retourner le fichier en téléchargement
            return Storage::disk('public')->download($file->path, $file->filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du fichier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clôture un ticket en mettant à jour le statut et la date de clôture.
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function closeTicket(Request $request, int $ticketId): JsonResponse
    {
        try {
            // Validation: la conclusion est obligatoire
            $request->validate([
                'conclusion' => 'required|string'
            ]);

            $ticket = TRecTicket::findOrFail($ticketId);
            $closedByUserId = \Illuminate\Support\Facades\Auth::id();

            // Transaction pour garantir l'atomicité des mises à jour
            \Illuminate\Support\Facades\DB::transaction(function () use ($ticket, $closedByUserId, $request, $ticketId) {
                // Mise à jour du ticket
                $ticket->update([
                    'status' => $ticket->status == 'Recours' ? 'Recours clôturé' : 'clôturé',
                    'closed_at' => $ticket->status == 'Recours' ? $ticket->closed_at : now(),
                    'date_cloture_recours' => $ticket->status == 'Recours' ? now() : $ticket->date_cloture_recours,
                    'closed_by' => $closedByUserId,
                    'reply_permission' => $ticket->status == 'Recours' ? 'employe_Répondeur' : $ticket->reply_permission,
                    'conclusion' => $request->input('conclusion')
                ]);

                // Mise à jour des orientations liées au ticket pour passage en recour
                if ($ticket->status == 'clôturé') {
                    $directions = \App\Models\ReclamationClient\TRecTicketDirection::where('tticket_id', $ticketId)->get();
                    foreach ($directions as $dir) {
                        if ($dir->type_orientation !== 'recour') {
                            $dir->old_orientation = $dir->type_orientation;
                            $dir->type_orientation = 'recour';
                            $dir->statut_direction = 'consultation';
                            $dir->save();
                        }
                    }
                }
            });

            // Envoyer les notifications de clôture
            $notificationService = new NotificationService();
            $notificationService->createTicketClosureNotifications($ticket, $closedByUserId);
            if ($ticket->status === 'Recours clôturé') {
                $notificationService->createRecoursClosureNotifications($ticket, $closedByUserId);
            }

            // Enregistrer les fichiers de conclusion s'ils existent
            if ($request->hasFile('files')) {
                $this->handleTicketFileUploads($request->file('files'), $ticketId, 'conclusion');
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket clôturé avec succès',
                'data' => [
                    'ticket_id' => $ticketId,
                    'status' => $ticket->status,
                    'closed_at' => $ticket->closed_at,
                    'conclusion' => $ticket->conclusion,
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket non trouvé',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la clôture du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
