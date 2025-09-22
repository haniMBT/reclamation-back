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
    public function index()
    {
        $tickets = BRecTickets::with('infosGenerales')->get();
        
        $formattedTickets = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'libelle' => $ticket->libelle,
                'description' => $ticket->description,
                'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'libelle' => $info->libelle,
                        'type' => $info->type,
                        'obligatoire' => $info->obligatoire,
                        'key_attribut' => $info->key_attribut
                    ];
                })
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedTickets
        ]);
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
                'description' => 'required|string|min:10|max:1000',
                'info_general_data' => 'required|array|min:1',
                'info_general_data.*.info_general_id' => 'required|integer|min:1',
                'info_general_data.*.value' => 'required|string|min:1|max:255',
                'info_general_data.*.key_attribut' => 'required|boolean'
            ], [
                'bticket_id.required' => 'L\'identifiant du ticket est requis.',
                'bticket_id.exists' => 'Le ticket sélectionné n\'existe pas.',
                'description.required' => 'La description est requise.',
                'description.min' => 'La description doit contenir au moins 10 caractères.',
                'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
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
            $description = $request->input('description');
            $infoGeneralData = $request->input('info_general_data');

            // Filtrer seulement les champs avec key_attribut = true
            $keyAttributes = collect($infoGeneralData)->filter(function ($item) {
                return $item['key_attribut'] === true;
            });

            // Si aucun attribut clé à vérifier, insérer directement
            if ($keyAttributes->isEmpty()) {
                return $this->insertTicketData($bticketId, $userId, $direction, $status, $description, $infoGeneralData);
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
            return $this->insertTicketData($bticketId, $userId, $direction, $status, $description, $infoGeneralData);

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
     * @param string $description
     * @param array $infoGeneralData
     * @return JsonResponse
     */
    private function insertTicketData($bticketId, $userId, $direction, $status, $description, $infoGeneralData): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Insérer dans t_rec_tickets
            $ticketId = DB::table('t_rec_tickets')->insertGetId([
                'bticket_id' => $bticketId,
                'user_id' => $userId,
                'direction' => $direction,
                'status' => $status,
                'description' => $description,
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
                        'description' => $description,
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveComplete(Request $request): JsonResponse
    {
        try {
            // Validation de la requête
            $validatedData = $request->validate([
                'tticket_id' => 'required|integer|exists:t_rec_tickets,id',
                'description' => 'nullable|string|max:5000',
                'type_selection' => 'required|array|min:1',
                'type_selection.*.type_id' => 'nullable|integer',
                'type_selection.*.libelle' => 'required|string|max:255',
                'type_selection.*.details' => 'nullable|array',
                'type_selection.*.details.*.detail_id' => 'nullable|integer',
                'type_selection.*.details.*.libelle' => 'required|string|max:255',
                'type_selection.*.autre' => 'nullable|string|max:500',
                'files' => 'nullable|array',
                'files.*' => 'file|max:2048|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt', // 2MB max
            ], [
                'tticket_id.required' => 'L\'identifiant du ticket est requis.',
                'tticket_id.exists' => 'Le ticket spécifié n\'existe pas.',
                'type_selection.required' => 'Au moins un type doit être sélectionné.',
                'type_selection.min' => 'Au moins un type doit être sélectionné.',
                'files.*.max' => 'La taille du fichier ne peut pas dépasser 2 MB.',
                'files.*.mimes' => 'Le format du fichier n\'est pas autorisé.',
            ]);

            // Démarrer une transaction
            DB::beginTransaction();

            $tticketId = $validatedData['tticket_id'];
            $typesSavedCount = 0;

            // Mise à jour de la description du ticket
            if (isset($validatedData['description'])) {
                TRecTicket::where('id', $tticketId)
                    ->update(['description' => $validatedData['description']]);
            }

            // Gestion des fichiers uploadés
            if ($request->hasFile('files')) {
                $this->handleTicketFileUploads($request->file('files'), $tticketId);
            }

            // Insertion des types et détails
            foreach ($validatedData['type_selection'] as $typeData) {
                // Créer le type
                $tRecType = TRecType::create([
                    'tticket_id' => $tticketId,
                    'b_rec_type_id' => $typeData['type_id'] ?? null,
                    'libelle' => $typeData['libelle'],
                ]);

                $typesSavedCount++;

                // Insérer les détails associés
                if (isset($typeData['details']) && is_array($typeData['details'])) {
                    foreach ($typeData['details'] as $detailData) {
                        TRecDetail::create([
                            't_rec_type_id' => $tRecType->id,
                            'b_rec_detail_id' => $detailData['detail_id'] ?? null,
                            'libelle' => $detailData['libelle'],
                        ]);
                    }
                }

                // Gérer le cas "autre" - insérer même sans b_rec_detail_id
                if (isset($typeData['autre']) && !empty(trim($typeData['autre']))) {
                    TRecDetail::create([
                        't_rec_type_id' => $tRecType->id,
                        'b_rec_detail_id' => null,
                        'libelle' => trim($typeData['autre']),
                    ]);
                }
            }

            // Récupérer les informations du ticket pour la réponse
            $ticket = TRecTicket::find($tticketId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation complétée',
                'data' => [
                    't_rec_ticket_id' => $tticketId,
                    'b_rec_ticket_id' => $ticket->bticket_id ?? null,
                    'types_saved_count' => $typesSavedCount
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