<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\TRecMessage;
use App\Models\ReclamationClient\TRecDestinataireMessage;
use App\Models\ReclamationClient\TRecFicherMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Récupérer tous les messages d'un ticket
     */
    public function index($ticketId)
    {
        try {
            $messages = TRecMessage::with(['destinataires', 'fichiers'])
                ->where('tticket_id', $ticketId)
                ->orderBy('date_envoie', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau message
     */
    public function store(Request $request, $ticketId)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'directions' => 'required|string', // JSON string des directions destinataires
            'attachments.*' => 'file|max:10240' // 10MB max par fichier
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
            $user = Auth::user();
            $directionsDestinaires = json_decode($request->directions, true);

            if (!is_array($directionsDestinaires) || empty($directionsDestinaires)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Au moins une direction destinataire est requise'
                ], 422);
            }

            // 1. Créer le message principal dans t_rec_message
            $message = TRecMessage::create([
                'tticket_id' => $ticketId,
                'titre' => $request->titre,
                'texte' => $request->description,
                'direction_envoi' => $user->direction ?? null, // Direction de l'utilisateur qui envoie
                'sender_id' => $user->id, // ID de l'utilisateur qui envoie
                'date_envoie' => now()
            ]);



            // 2. Créer les enregistrements destinataires dans t_rec_destinataires_messages
            foreach ($directionsDestinaires as $directionId) {

                TRecDestinataireMessage::create([
                    'message_id' => $message->id,
                    'direction_destinataire' => $directionId,
                    'statut' => 'non_lu' // Statut par défaut
                ]);
            }

            // 3. Gérer les fichiers joints dans t_rec_ficher_message
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Stocker le fichier
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('messages/attachments', $fileName, 'public');

                    // Enregistrer dans la base de données
                    TRecFicherMessage::create([
                        'message_id' => $message->id,
                        'nom_fichier' => $file->getClientOriginalName(),
                        'nom_fichier_stocke' => $fileName,
                        'chemin_fichier' => $filePath,
                        'taille_fichier' => $file->getSize(),
                        'type_mime' => $file->getMimeType(),
                        'date_upload' => now()
                    ]);
                }
            }

            DB::commit();

            // Charger les relations pour la réponse
            $message->load(['destinataires', 'fichiers']);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un message spécifique
     */
    public function show($id)
    {
        try {
            $message = TRecMessage::with(['destinataires', 'fichiers'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvé'
            ], 404);
        }
    }

    /**
     * Marquer un message comme lu pour une direction
     */
    public function markAsRead(Request $request, $messageId)
    {
        try {
            $user = Auth::user();
            $userDirection = $user->direction;

            $destinataire = TRecDestinataireMessage::where('message_id', $messageId)
                ->where('direction_destinataire_recepteur', $userDirection)
                ->first();

            if ($destinataire) {
                $destinataire->update(['statut' => 'lu']);

                return response()->json([
                    'success' => true,
                    'message' => 'Message marqué comme lu'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Destinataire non trouvé'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un message
     */
    public function destroy($ticketId, $messageId)
    {
        DB::beginTransaction();

        try {
            $message = TRecMessage::where('id', $messageId)
                ->where('tticket_id', $ticketId)
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message non trouvé'
                ], 404);
            }

            // Supprimer les fichiers associés du stockage
            $fichiers = TRecFicherMessage::where('message_id', $messageId)->get();
            foreach ($fichiers as $fichier) {
                if (Storage::disk('public')->exists($fichier->chemin_fichier)) {
                    Storage::disk('public')->delete($fichier->chemin_fichier);
                }
            }

            // Supprimer les enregistrements de la base de données
            // Les suppressions en cascade devraient gérer les destinataires et fichiers
            // mais on les supprime explicitement pour être sûr
            TRecDestinataireMessage::where('message_id', $messageId)->delete();
            TRecFicherMessage::where('message_id', $messageId)->delete();
            
            // Supprimer le message principal
            $message->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un fichier joint
     */
    public function downloadAttachment($fileId)
    {
        try {
            $fichier = TRecFicherMessage::findOrFail($fileId);

            if (!Storage::disk('public')->exists($fichier->chemin_fichier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé sur le serveur'
                ], 404);
            }

            return Storage::disk('public')->download(
                $fichier->chemin_fichier,
                $fichier->nom_fichier
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], 500);
        }
    }
}