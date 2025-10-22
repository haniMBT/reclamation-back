<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\TRecMessage;
use App\Models\ReclamationClient\TRecTicketDirection;
use App\Models\ReclamationClient\TRecDestinataireMessage;
use App\Models\ReclamationClient\TRecFicherMessage;
use App\Models\ReclamationClient\TRecTicket;
use App\Services\ReclamationClient\NotificationService;
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

            $privilege = Auth::user()->scopePrivileges('message');

            $ticket = TRecTicket::find($ticketId);
            $ticket->user_crateur = $ticket->user()->first();
            $ticket->privilege_crateur =  DB::table('p_privileges')->join('p_profils', 'p_profils.code', 'p_privileges.profil_code')
            ->where('p_profils.code', $ticket->user_crateur->privilege)->where('volet','message')
            ->select('p_privileges.*')->first();
            $ticket->ticket_direction_crateur = TRecTicketDirection::where('tticket_id', $ticketId)
                ->where('direction', $ticket->user_crateur->direction)
                ->first();

            if ($ticket && $ticket->status == 'En attente') {
                $updateData = ['status' => 'En cours'];
                // Ajouter la date_en_cours seulement si elle n'est pas déjà définie
                if (is_null($ticket->date_en_cours)) {
                    $updateData['date_en_cours'] = now();
                }
                $ticket->where('user_id','!=', Auth::id())->where('id',$ticketId)
                ->update($updateData);
            }

            if ($privilege->role == 'employe_Répondeur') {
                $ticket_direction = TRecTicketDirection::where('tticket_id', $ticketId)
                ->where('direction', auth::user()->direction)
                ->first();
            } else {
                $ticket_direction = null;
            }

            // Construire la requête de base (fichiers toujours chargés)
            $messagesQuery = TRecMessage::with('fichiers')
                ->where('tticket_id', $ticketId);

            // Vérifier si l'utilisateur courant est l'auteur du ticket
            if ($ticket->user_id == Auth::id()) {

                // Cas 1 : l'utilisateur est un "employe_Répondeur"
                if ($privilege->role == 'employe_Répondeur') {

                // Cas 1 : l'utilisateur est un "employe_Répondeur" concerne par la reclamation
                    if ($ticket_direction!=null && $ticket_direction->direction == Auth::user()->direction) {

                        // concerne par la reclamation
                        $messagesQuery->with('destinataires');

                    } else {

                        // ne pas concerne par la reclamation, client ne voir que les messages destinés au client
                      $messagesQuery->whereHas('destinataires', function ($q) {
                            $q->where('direction_destinataire', 'client')
                            ->orWhere('direction_destinataire', 'directions');
                        })->with(['destinataires' => function ($q) {
                            $q->where('direction_destinataire', 'client')
                            ->orWhere('direction_destinataire', 'directions');
                        }]);
                    }

                } else {
                      // client : ne voir que les messages destinés au client
                        $messagesQuery->whereHas('destinataires', function ($q) {
                            $q->where('direction_destinataire', 'client')
                            ->orWhere('direction_destinataire', 'directions');
                        })->with(['destinataires' => function ($q) {
                            $q->where('direction_destinataire', 'client')
                            ->orWhere('direction_destinataire', 'directions');
                        }]);
                }

            } else {
                // Cas 3 : utilisateur ≠ créateur du ticket → on charge tout
                $messagesQuery->with('destinataires');
            }

            // Final : tri puis récupération
            $messages = $messagesQuery
                ->orderBy('date_envoie', 'desc')
                ->get();


            return response()->json([
                'success' => true,
                'data' => $messages,
                'privilege' => $privilege,
                'ticket' => $ticket,
                'ticket_direction' => $ticket_direction
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
            'titre' => 'required|string',
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
                'sender_id' => $user->id,
                'date_envoie' => now(),
                // champ optionnel, pas défini pour store classique
            ]);

            // 2. Créer les enregistrements destinataires dans t_rec_destinataires_messages
            foreach ($directionsDestinaires as $directionId) {
                TRecDestinataireMessage::create([
                    'message_id' => $message->id,
                    'direction_destinataire' => $directionId,
                    'statut' => 'non_lu'
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

            // Envoyer les notifications pour le message à d'autres directions
            $ticket = TRecTicket::findOrFail($ticketId);
            $notificationService = new NotificationService();
            $notificationService->createDirectionMessageNotifications($ticket, $user->id, $directionsDestinaires);

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
     * Envoyer un message de recours (direction_envoi = recour, message_vers = recour, destinataire = directions)
     */
    public function recour(Request $request, $ticketId)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string',
            'description' => 'required|string',
            'attachments.*' => 'file|max:10240'
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
            $ticket = TRecTicket::findOrFail($ticketId);
            $updateData = ['status' => 'Recours'];
            // Ajouter la date_recours seulement si elle n'est pas déjà définie
            if (is_null($ticket->date_recours)) {
                $updateData['date_recours'] = now();
            }
            $ticket->update($updateData);

            // Créer le message principal avec les attributs spécifiques au recours
            $message = TRecMessage::create([
                'tticket_id' => $ticketId,
                'titre' => $request->titre,
                'texte' => $request->description,
                'direction_envoi' => 'recour',
                'sender_id' => $user->id,
                'date_envoie' => now(),
                'message_vers' => 'recour',
            ]);

            // Destinataire global "directions" (sans sélection individuelle)
            TRecDestinataireMessage::create([
                'message_id' => $message->id,
                'direction_destinataire' => 'directions',
                'statut' => 'non_lu'
            ]);

            // Fichiers joints
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('messages/attachments', $fileName, 'public');

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

            // Envoyer les notifications pour le recours
            $notificationService = new NotificationService();
            $notificationService->createRecoursCreationNotifications($ticket, $user->id);

            DB::commit();
            $message->load(['destinataires', 'fichiers']);
            return response()->json([
                'success' => true,
                'message' => 'Message de recours envoyé avec succès',
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du recours: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une réponse à un ticket (sans directions sélectionnées)
     */
    public function reply(Request $request, $ticketId)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string',
            'description' => 'required|string',
            'attachments.*' => 'file|max:10240'
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
            $ticket = TRecTicket::findOrFail($ticketId);
            $isClient = ($user && $ticket && $user->id == $ticket->user_id);

            $ticket->update([
                'reply_permission' => $ticket->reply_permission == 'client' ? 'employe_Répondeur' : 'client',
            ]);


            // Créer le message principal
            $message = TRecMessage::create([
                'tticket_id' => $ticketId,
                'titre' => $request->titre,
                'texte' => $request->description,
                'direction_envoi' => $isClient ? 'client' : ($user->direction ?? null),
                'sender_id' => $user->id,
                'date_envoie' => now(),
                'message_vers' => $isClient ? 'client vers direction' : 'direction vers client',
            ]);

            // Destinataire "réponse" (sans direction ciblée)
            TRecDestinataireMessage::create([
                'message_id' => $message->id,
                'direction_destinataire' => $isClient ? 'directions' : 'client',
                'statut' => 'non_lu'
            ]);

            // Fichiers joints
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('messages/attachments', $fileName, 'public');

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

            // Envoyer les notifications pour la réponse
            $notificationService = new NotificationService();
            $notificationService->createMessageReplyNotifications($ticket, $user->id, $isClient);

            DB::commit();
            $message->load(['destinataires', 'fichiers']);
            return response()->json([
                'success' => true,
                'message' => 'Réponse envoyée avec succès',
                'data' => $message,
                'ticket' => $ticket
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la réponse: ' . $e->getMessage()
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
