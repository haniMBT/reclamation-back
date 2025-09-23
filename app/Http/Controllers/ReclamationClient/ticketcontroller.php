<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\TRecType;
use App\Models\ReclamationClient\TRecDetail;
use App\Models\ReclamationClient\FichierClient;
use App\Models\ReclamationClient\TRecTicketFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
                    'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attirubut' => $info->key_attirubut,
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
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $search = $request->get('q', '');
            $direction = $request->get('direction', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('date_from', '');
            $dateTo = $request->get('date_to', '');
            $typeId = $request->get('type_id', '');

            // Query de base avec eager loading
            $query = TRecTicket::with([
                'baseTicket.infosGenerales',
                'types.bRecType',
                'types.details.bRecDetail'
            ]);

            // Filtrage par recherche globale
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
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

            // Formater les données
            $formattedTickets = $tickets->getCollection()->map(function ($ticket) {
                $baseTicket = $ticket->baseTicket;

                return [
                    'id' => $ticket->id,
                    'bticket_id' => $ticket->bticket_id,
                    'user_id' => $ticket->user_id,
                    'direction' => $ticket->direction,
                    'status' => $ticket->status ?? 'OUVERT',
                    'description' => $ticket->description,
                    'closed_at' => $ticket->closed_at,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'libelle' => $baseTicket ? $baseTicket->libelle : null,
                    'infos_generales' => $baseTicket && $baseTicket->infosGenerales ? $baseTicket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attirubut' => $info->key_attirubut,
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
                'info_general_data' => 'required|array|min:1',
                'info_general_data.*.info_general_id' => 'required|integer|min:1',
                'info_general_data.*.value' => 'required|string|min:1|max:255',
                'info_general_data.*.key_attribut' => 'required|boolean'
            ], [
                'bticket_id.required' => 'L\'identifiant du ticket est requis.',
                'bticket_id.exists' => 'Le ticket sélectionné n\'existe pas.',
                'info_general_data.required' => 'Les informations générales sont requises.',
                'info_general_data.min' => 'Au moins une information générale est requise.',
                'info_general_data.*.value.required' => 'La valeur du champ est requise.',
                'info_general_data.*.value.min' => 'La valeur du champ ne peut pas être vide.',
                'info_general_data.*.value.max' => 'La valeur du champ ne peut pas dépasser 255 caractères.'
            ]);

            $bticketId = $request->input('bticket_id');
            $userId = $request->input('user_id');
            $direction = $request->input('direction');
            $status = $request->input('status');
            $infoGeneralData = $request->input('info_general_data');

            // Filtrer seulement les champs avec key_attribut = true
            $keyAttributes = collect($infoGeneralData)->filter(function ($item) {
                return $item['key_attribut'] === true;
            });

            // Si aucun attribut clé à vérifier, insérer directement
            if ($keyAttributes->isEmpty()) {
                return $this->insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData);
            }

            // Construire la requête pour vérifier les doublons
            $query = DB::table('t_rec_info_general')
                ->where('tticket_id', $bticketId)
                ->where('key_attribut', true);

            // Ajouter les conditions pour chaque attribut clé
            $keyAttributes->each(function ($item) use (&$query) {
                $query->orWhere(function ($subQuery) use ($item) {
                    $subQuery->where('info_general_id', $item['info_general_id'])
                             ->where('value', $item['value']);
                });
            });

            // Compter le nombre d'attributs clés qui correspondent
            $matchingCount = $query->count();

            // Si tous les attributs clés correspondent, c'est un doublon
            $isDuplicate = $matchingCount === $keyAttributes->count();

            if ($isDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il existe déjà une réclamation avec les mêmes informations clés.',
                    'duplicate_found' => true
                ], 200);
            }

            // Aucun doublon trouvé, insérer les données
            return $this->insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData);

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
     * @return JsonResponse
     */
    private function insertTicketData($bticketId, $userId, $direction, $status, $infoGeneralData): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Insérer dans t_rec_tickets
            $ticketId = DB::table('t_rec_tickets')->insertGetId([
                'bticket_id' => $bticketId,
                'user_id' => $userId,
                'direction' => $direction,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
                'closed_at' => null
            ]);

            // Insérer dans t_rec_info_general
            foreach ($infoGeneralData as $infoData) {
                DB::table('t_rec_info_general')->insert([
                    'tticket_id' => $ticketId,
                    'info_general_id' => $infoData['info_general_id'],
                    'libelle' => $infoData['value'],
                    'value' => $infoData['value'],
                    'key_attribut' => $infoData['key_attribut'],
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
                        'user_id' => $userId,
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
                'description' => 'required|string|min:10',
                // 'type_details' => 'required|string', // JSON string
                'files.*' => 'nullable|file|max:10240' // 10MB max per file
            ], [
                'ticket_id.required' => 'L\'identifiant du ticket est requis.',
                'ticket_id.exists' => 'Le ticket spécifié n\'existe pas.',
                'description.required' => 'La description détaillée est requise.',
                'description.min' => 'La description doit contenir au moins 10 caractères.',
                'files.*.max' => 'Chaque fichier ne peut pas dépasser 10MB.'
            ]);

            $ticketId = $request->input('ticket_id');
            $description = $request->input('description');
            $typeDetails = json_decode($request->input('type_details'), true);

            DB::beginTransaction();

            // Mettre à jour la description du ticket
            DB::table('t_rec_tickets')
                ->where('id', $ticketId)
                ->update([
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
                    'status' => 'COMPLETE'
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
                'description' => 'nullable|string|max:5000',
                'files' => 'nullable|array',
                'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt', // 10MB max
            ], [
                'tticket_id.required' => 'L\'identifiant du ticket est requis.',
                'tticket_id.exists' => 'Le ticket spécifié n\'existe pas.',
                'files.*.max' => 'La taille du fichier ne peut pas dépasser 10 MB.',
                'files.*.mimes' => 'Le format du fichier n\'est pas autorisé.',
            ]);

            // Validation manuelle du type_selection
            if (!$typeSelection || !is_array($typeSelection) || empty($typeSelection)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => ['type_selection' => ['Au moins un type doit être sélectionné.']]
                ], 422);
            }

            // Valider chaque type dans type_selection
            foreach ($typeSelection as $index => $typeData) {
                if (!isset($typeData['libelle']) || empty(trim($typeData['libelle']))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreurs de validation',
                        'errors' => ["type_selection.{$index}.libelle" => ['Le libellé du type est requis.']]
                    ], 422);
                }

                // Valider les détails si présents
                if (isset($typeData['details']) && is_array($typeData['details'])) {
                    foreach ($typeData['details'] as $detailIndex => $detailData) {
                        if (!isset($detailData['libelle']) || empty(trim($detailData['libelle']))) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Erreurs de validation',
                                'errors' => ["type_selection.{$index}.details.{$detailIndex}.libelle" => ['Le libellé du détail est requis.']]
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
                    ->update(['description' => trim($validatedData['description'])]);
            }

            // Gestion des fichiers uploadés
            if ($request->hasFile('files')) {
                $this->handleTicketFileUploads($request->file('files'), $tticketId);
            }

            // Insertion des types et détails selon le nouveau format
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
    private function handleTicketFileUploads(array $files, int $tticketId): void
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
                ]);
            }
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
}
