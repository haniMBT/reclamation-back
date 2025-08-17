<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\Reclamation;
use App\Models\ReclamationClient\FichierClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReclamationController extends Controller
{
    /**
     * Créer une nouvelle réclamation client.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation des données entrantes
            $validatedData = $request->validate([
                'objet' => 'required|string|max:255',
                'contenu' => 'required|string',
                'fichiers' => 'nullable|array',
                'fichiers.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt', // 10MB max
            ], [
                'objet.required' => 'L\'objet de la réclamation est requis.',
                'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
                'contenu.required' => 'Le contenu de la réclamation est requis.',
                'fichiers.*.file' => 'Le fichier doit être un fichier valide.',
                'fichiers.*.max' => 'La taille du fichier ne peut pas dépasser 10 MB.',
                'fichiers.*.mimes' => 'Le format du fichier n\'est pas autorisé.',
            ]);

            // Nettoyer le contenu HTML si nécessaire
            $contenu = $this->cleanHtmlContent($validatedData['contenu']);

            if (empty($contenu)) {
                throw ValidationException::withMessages([
                    'contenu' => ['Le contenu de la réclamation ne peut pas être vide.']
                ]);
            }

            // Utiliser une transaction pour garantir la cohérence
            DB::beginTransaction();

            try {
                // Créer la réclamation
                $reclamation = Reclamation::create([
                    'objet' => $validatedData['objet'],
                    'contenu' => $validatedData['contenu'],
                    // 'contenu' => $contenu,
                    'user_id' => Auth::id(),
                    'statut' => 'nouvelle',
                    'date_creation' => now(),
                ]);

                // Gérer les fichiers joints s'ils existent
                if ($request->hasFile('fichiers')) {
                    $this->handleFileUploads($request->file('fichiers'), $reclamation->id);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Réclamation créée avec succès.',
                    'data' => [
                        'reclamation_id' => $reclamation->id,
                        'objet' => $reclamation->objet,
                        'statut' => $reclamation->statut_formatte,
                        'date_creation' => $reclamation->date_creation->format('d/m/Y H:i'),
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la réclamation', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['fichiers'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Gérer l'upload des fichiers.
     *
     * @param array $files
     * @param int $reclamationId
     * @return void
     */
    private function handleFileUploads(array $files, int $reclamationId): void
    {
        foreach ($files as $file) {
            if ($file->isValid()) {
                // Générer un nom unique pour le fichier
                $nomStockage = $this->generateUniqueFileName($file);

                // Définir le chemin de stockage
                $cheminStockage = 'reclamations/' . date('Y/m');

                // Stocker le fichier
                $cheminComplet = $file->storeAs($cheminStockage, $nomStockage, 'public');

                // Enregistrer les informations du fichier en base
                FichierClient::create([
                    'reclamation_id' => $reclamationId,
                    'nom_original' => $file->getClientOriginalName(),
                    'nom_stockage' => $nomStockage,
                    'chemin' => 'public/' . $cheminComplet,
                    'taille' => $file->getSize(),
                    'type_mime' => $file->getMimeType(),
                    'date_upload' => now(),
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

    private function generateUniqueFileName($file, $directory = 'reclamations'): string
    {
        $extension = $file->getClientOriginalExtension();
        $nomSansExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Nettoyer le nom du fichier (slug avec underscore)
        $nomSansExtension = Str::slug($nomSansExtension, '_');

        do {
            // Crée un nom de fichier avec timestamp et UUID court (base36 pour réduire la longueur)
            $timestamp = now()->format('Ymd_His'); // Exemple : 20250805_122045
            $uniqueId = substr(Str::uuid()->toString(), 0, 8); // UUID court
            $fileName = "{$nomSansExtension}_{$timestamp}_{$uniqueId}.{$extension}";
        } while (Storage::disk('local')->exists("{$directory}/{$fileName}"));

        return $fileName;
    }

    /**
     * Nettoyer le contenu HTML.
     *
     * @param string $content
     * @return string
     */
    private function cleanHtmlContent(string $content): string
    {
        // Retirer les balises HTML vides communes de l'éditeur
        $content = str_replace(['<p><br></p>', '<p></p>', '<br>', '<br/>', '<br />'], '', $content);

        // Nettoyer les espaces en début et fin
        $content = trim($content);

        // Si après nettoyage il ne reste que des balises vides, retourner une chaîne vide
        if (strip_tags($content) === '' || $content === '<p></p>') {
            return '';
        }

        return $content;
    }

        public function update(Request $request, $id): JsonResponse
    {

        // return response()->json([
        //             'success' => $request->all(),
        //         ], 400);
        try {
            // Validation
            $validatedData = $request->validate([
                'objet' => 'required|string|max:255',
                'contenu' => 'required|string',
                'fichiers' => 'nullable|array',
                'fichiers.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
                'fichiers_a_supprimer' => 'nullable|array',
                'fichiers_a_supprimer.*' => 'integer|exists:fichiers_clients,id'
            ], [
                'objet.required' => 'L\'objet de la réclamation est requis.',
                'objet.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
                'contenu.required' => 'Le contenu de la réclamation est requis.',
                'fichiers.*.file' => 'Le fichier doit être un fichier valide.',
                'fichiers.*.max' => 'La taille du fichier ne peut pas dépasser 10 MB.',
                'fichiers.*.mimes' => 'Le format du fichier n\'est pas autorisé.',
                'fichiers_a_supprimer.*.exists' => 'Le fichier à supprimer est introuvable.'
            ]);

            // Nettoyage du contenu
            $contenu = $this->cleanHtmlContent($validatedData['contenu']);
            if (empty($contenu)) {
                throw ValidationException::withMessages([
                    'contenu' => ['Le contenu de la réclamation ne peut pas être vide.']
                ]);
            }

            DB::beginTransaction();

            try {
                // Récupérer la réclamation
                $reclamation = Reclamation::findOrFail($id);

                // Vérification d'autorisation
                if ($reclamation->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas autorisé à modifier cette réclamation.'
                    ], 403);
                }

                // Mise à jour
                $reclamation->update([
                    'objet' => $validatedData['objet'],
                    'contenu' => $validatedData['contenu'], // ou $contenu si tu veux le texte nettoyé
                    'updated_at' => now(),
                ]);

                // Suppression des fichiers si demandé
                if (!empty($validatedData['fichiers_a_supprimer'])) {
                    $fichiers = FichierClient::whereIn('id', $validatedData['fichiers_a_supprimer'])
                        ->where('reclamation_id', $reclamation->id)
                        ->get();

                    foreach ($fichiers as $fichier) {
                        Storage::disk('public')->delete(str_replace('public/', '', $fichier->chemin));
                        $fichier->delete();
                    }
                }

                // Ajout de nouveaux fichiers
                if ($request->hasFile('fichiers')) {
                    $this->handleFileUploads($request->file('fichiers'), $reclamation->id);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Réclamation mise à jour avec succès.',
                    'data' => [
                        'reclamation_id' => $reclamation->id,
                        'objet' => $reclamation->objet,
                        'statut' => $reclamation->statut_formatte,
                        // 'date_modification' => $reclamation->date_modification->format('d/m/Y H:i'),
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la réclamation', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['fichiers'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Récupérer la liste des réclamations de l'utilisateur connecté avec pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validation des paramètres de pagination
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
            ], [
                'page.integer' => 'Le numéro de page doit être un nombre entier.',
                'page.min' => 'Le numéro de page doit être supérieur à 0.',
                'per_page.integer' => 'Le nombre d\'éléments par page doit être un nombre entier.',
                'per_page.min' => 'Le nombre d\'éléments par page doit être supérieur à 0.',
                'per_page.max' => 'Le nombre d\'éléments par page ne peut pas dépasser 50.',
            ]);

            // Paramètres de pagination
            $perPage = $validatedData['per_page'] ?? 10;
            $page = $validatedData['page'] ?? 1;

            if ($request->has('id_reclamation')) {
                // Récupérer la réclamation avec ses relations
                $reclamation = Reclamation::with(['fichiers', 'utilisateur', 'traitePar'])
                    // ->where('user_id', Auth::id()) // Sécurité : l'utilisateur ne peut voir que ses propres réclamations
                    ->findOrFail($request->id_reclamation);

                    // Préparer les données pour la réponse
                $data = [
                    'id' => $reclamation->id,
                    'objet' => $reclamation->objet,
                    'contenu' => $reclamation->contenu,
                    'statut' => $reclamation->statut,
                    'statut_formatte' => $reclamation->statut_formatte,
                    'date_creation' => $reclamation->date_creation,
                    'date_traitement' => $reclamation->date_traitement,
                    'reponse' => $reclamation->reponse,
                    'traite_par' => $reclamation->traite_par,
                    'traite_par_name' => $reclamation->traitePar ? $reclamation->traitePar->name : null,
                    'utilisateur' => [
                        'id' => $reclamation->utilisateur->id,
                        'name' => $reclamation->utilisateur->name,
                        'email' => $reclamation->utilisateur->email,
                    ],
                    'fichiers' => $reclamation->fichiers->map(function ($fichier) {
                        return [
                            'id' => $fichier->id,
                            'nom_original' => $fichier->nom_original,
                            'nom_stockage' => $fichier->nom_stockage,
                            'chemin' => $fichier->chemin,
                            'taille' => $fichier->taille,
                            'type_mime' => $fichier->type_mime,
                            'date_upload' => $fichier->date_upload,
                        ];
                    }),
                ];
                $reclamation = $data;
            }

            // Récupérer les réclamations avec pagination
            $reclamations = Reclamation::where('user_id', Auth::id())
                ->withCount('fichiers') // Compter les fichiers joints
                ->orderBy('date_creation', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Préparer les données pour la réponse
            $data = $reclamations->getCollection()->map(function ($reclamation) {
                return [
                    'id' => $reclamation->id,
                    'objet' => $reclamation->objet,
                    'statut' => $reclamation->statut,
                    'statut_formatte' => $reclamation->statut_formatte,
                    'date_creation' => $reclamation->date_creation,
                    'date_traitement' => $reclamation->date_traitement,
                    'fichiers_count' => $reclamation->fichiers_count,
                ];
            });

            // Informations de pagination
            $paginationData = [
                'current_page' => $reclamations->currentPage(),
                'per_page' => $reclamations->perPage(),
                'total' => $reclamations->total(),
                'last_page' => $reclamations->lastPage(),
                'from' => $reclamations->firstItem(),
                'to' => $reclamations->lastItem(),
                'has_more_pages' => $reclamations->hasMorePages(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Réclamations récupérées avec succès.',
                'data' => [
                    'data' => $data,
                    'current_page' => $paginationData['current_page'],
                    'per_page' => $paginationData['per_page'],
                    'total' => $paginationData['total'],
                    'last_page' => $paginationData['last_page'],
                    'from' => $paginationData['from'],
                    'to' => $paginationData['to'],
                    'has_more_pages' => $paginationData['has_more_pages'],
                    'reclamation' => $reclamation ?? null, // Inclure la réclamation si elle est demandée
                    'tous' => $request->all()
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des paramètres.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des réclamations', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Récupérer toutes les réclamations avec pagination et filtres.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function indexAll(Request $request): JsonResponse
    {
        try {
            // Validation des paramètres de requête
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:nouvelle,en_cours,traitee,fermee',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ], [
                'page.integer' => 'Le numéro de page doit être un nombre entier.',
                'page.min' => 'Le numéro de page doit être supérieur à 0.',
                'search.string' => 'La recherche doit être une chaîne de caractères.',
                'search.max' => 'La recherche ne peut pas dépasser 255 caractères.',
                'status.in' => 'Le statut sélectionné n\'est pas valide.',
                'date_from.date' => 'La date de début doit être une date valide.',
                'date_to.date' => 'La date de fin doit être une date valide.',
                'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            ]);

            // Paramètres par défaut - pagination fixe à 10 éléments
            $perPage = 10; // Fixé à 10 éléments par page
            $page = $validatedData['page'] ?? 1;

            // Construire la requête de base
            $query = Reclamation::where('user_id', Auth::id())
                ->with(['fichiers' => function ($query) {
                    $query->select('reclamation_id');
                }])
                ->withCount('fichiers');

            // Appliquer les filtres de recherche
            if (!empty($validatedData['search'])) {
                $searchTerm = $validatedData['search'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('objet', 'like', "%{$searchTerm}%")
                      ->orWhere('contenu', 'like', "%{$searchTerm}%");
                });
            }

            // Filtre par statut
            if (!empty($validatedData['status'])) {
                $query->where('statut', $validatedData['status']);
            }

            // Filtres par date
            if (!empty($validatedData['date_from'])) {
                $query->where('date_creation', '>=', $validatedData['date_from']);
            }

            if (!empty($validatedData['date_to'])) {
                $query->where('date_creation', '<=', $validatedData['date_to'] . ' 23:59:59');
            }

            // Tri et pagination
            $reclamations = $query->orderBy('date_creation', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Préparer les données pour la réponse
            $data = $reclamations->getCollection()->map(function ($reclamation) {
                return [
                    'id' => $reclamation->id,
                    'objet' => $reclamation->objet,
                    'contenu' => $reclamation->contenu,
                    'statut' => $reclamation->statut,
                    'statut_formatte' => $reclamation->statut_formatte,
                    'date_creation' => $reclamation->date_creation,
                    'date_traitement' => $reclamation->date_traitement,
                    'fichiers_count' => $reclamation->fichiers_count,
                ];
            });

            // Informations de pagination
            $paginationData = [
                'current_page' => $reclamations->currentPage(),
                'per_page' => $reclamations->perPage(),
                'total' => $reclamations->total(),
                'last_page' => $reclamations->lastPage(),
                'from' => $reclamations->firstItem(),
                'to' => $reclamations->lastItem(),
                'has_more_pages' => $reclamations->hasMorePages(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Réclamations récupérées avec succès.',
                'data' => [
                    'data' => $data,
                    'current_page' => $paginationData['current_page'],
                    'per_page' => $paginationData['per_page'],
                    'total' => $paginationData['total'],
                    'last_page' => $paginationData['last_page'],
                    'from' => $paginationData['from'],
                    'to' => $paginationData['to'],
                    'has_more_pages' => $paginationData['has_more_pages'],
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des paramètres.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des réclamations avec filtres', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une réclamation spécifique.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Récupérer la réclamation avec ses relations
            $reclamation = Reclamation::with(['fichiers', 'utilisateur', 'traitePar'])
                ->where('user_id', Auth::id()) // Sécurité : l'utilisateur ne peut voir que ses propres réclamations
                ->findOrFail($id);

            // Préparer les données pour la réponse
            $data = [
                'id' => $reclamation->id,
                'objet' => $reclamation->objet,
                'contenu' => $reclamation->contenu,
                'statut' => $reclamation->statut,
                'statut_formatte' => $reclamation->statut_formatte,
                'date_creation' => $reclamation->date_creation,
                'date_traitement' => $reclamation->date_traitement,
                'reponse' => $reclamation->reponse,
                'traite_par' => $reclamation->traite_par,
                'traite_par_name' => $reclamation->traitePar ? $reclamation->traitePar->name : null,
                'utilisateur' => [
                    'id' => $reclamation->utilisateur->id,
                    'name' => $reclamation->utilisateur->name,
                    'email' => $reclamation->utilisateur->email,
                ],
                'fichiers' => $reclamation->fichiers->map(function ($fichier) {
                    return [
                        'id' => $fichier->id,
                        'nom_original' => $fichier->nom_original,
                        'nom_stockage' => $fichier->nom_stockage,
                        'chemin' => $fichier->chemin,
                        'taille' => $fichier->taille,
                        'type_mime' => $fichier->type_mime,
                        'date_upload' => $fichier->date_upload,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Réclamation récupérée avec succès.',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Tentative d\'accès à une réclamation inexistante', [
                'reclamation_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Réclamation non trouvée.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la réclamation', [
                'error' => $e->getMessage(),
                'reclamation_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Télécharger un fichier joint à une réclamation.
     *
     * @param int $reclamationId
     * @param int $fichierId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadFile(int $reclamationId, int $fichierId)
    {
        try {
            // Vérifier que la réclamation appartient à l'utilisateur connecté
            $reclamation = Reclamation::where('user_id', Auth::id())
                ->findOrFail($reclamationId);

            // Récupérer le fichier
            $fichier = FichierClient::where('reclamation_id', $reclamationId)
                ->findOrFail($fichierId);

            // Vérifier que le fichier existe sur le disque
            if (!Storage::disk('public')->exists(str_replace('public/', '', $fichier->chemin))) {
                throw new \Exception('Fichier non trouvé sur le serveur');
            }

            // Retourner le fichier pour téléchargement
            $path = str_replace('public/', '', $fichier->chemin);
            return response()->download(storage_path('app/public/' . $path), $fichier->nom_original);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Tentative de téléchargement d\'un fichier inexistant', [
                'reclamation_id' => $reclamationId,
                'fichier_id' => $fichierId,
                'user_id' => Auth::id()
            ]);

            abort(404, 'Fichier non trouvé');

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du fichier', [
                'error' => $e->getMessage(),
                'reclamation_id' => $reclamationId,
                'fichier_id' => $fichierId,
                'user_id' => Auth::id()
            ]);

            abort(500, 'Erreur lors du téléchargement');
        }
    }
    public function deleteFile(int $reclamationId, int $fichierId)
    {
        try {
             // Récupérer le fichier
            $fichier = FichierClient::where('reclamation_id', $reclamationId)
                ->findOrFail($fichierId);

            Storage::disk('public')->delete(str_replace('public/', '', $fichier->chemin));
            $fichier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprimé avec succès.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Tentative de téléchargement d\'un fichier inexistant', [
                'reclamation_id' => $reclamationId,
                'fichier_id' => $fichierId,
                'user_id' => Auth::id()
            ]);

            abort(404, 'Fichier non trouvé');

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du fichier', [
                'error' => $e->getMessage(),
                'reclamation_id' => $reclamationId,
                'fichier_id' => $fichierId,
                'user_id' => Auth::id()
            ]);

            abort(500, 'Erreur lors du téléchargement');
        }
    }
}
