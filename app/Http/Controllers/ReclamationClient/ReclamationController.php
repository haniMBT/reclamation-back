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
    // private function generateUniqueFileName($file): string
    // {
    //     $extension = $file->getClientOriginalExtension();
    //     $nomSansExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

    //     // Nettoyer le nom du fichier
    //     $nomSansExtension = Str::slug($nomSansExtension, '_');

    //     // Ajouter timestamp et hash unique
    //     $timestamp = time();
    //     $hash = Str::random(8);

    //     return $nomSansExtension . '_' . $timestamp . '_' . $hash . '.' . $extension;
    // }

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
}
