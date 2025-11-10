<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ReclamationClient\TRecMessage;
use App\Models\ReclamationClient\TRecDestinataireMessage;
use App\Models\ReclamationClient\TRecFicherMessage;
use App\Models\ReclamationClient\TRecCommissionRecours;

class MessageRecoursController extends Controller
{
    /**
     * Créer et envoyer un message de recours sans notifications,
     * avec enregistrement des destinataires en tant que membres sélectionnés.
     */
    public function store(Request $request, $ticketId)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string',
            'description' => 'required|string',
            'recipients' => 'required|string', // JSON string des user_ids destinataires
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
            $recipientUserIds = json_decode($request->recipients, true);

            if (!is_array($recipientUserIds) || empty($recipientUserIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Au moins un destinataire doit être sélectionné'
                ], 422);
            }

            // 1. Créer le message principal dans t_rec_message
            $fullName = trim(($user->Prenom ?? '') . ' ' . ($user->Nom ?? ''));
            $message = TRecMessage::create([
                'tticket_id' => $ticketId,
                'titre' => $request->titre,
                'texte' => $request->description,
                // Stocker le nom complet de l’émetteur à la place de la direction
                'direction_envoi' => $fullName,
                'sender_id' => $user->id,
                'date_envoie' => now(),
                // Pas de message_vers spécifique
            ]);

            // 2. Créer les enregistrements destinataires en tant que membres (Nom Prénom)
            $members = TRecCommissionRecours::whereIn('user_id', $recipientUserIds)->get()->keyBy('user_id');
            foreach ($recipientUserIds as $userId) {
                $m = $members->get($userId);
                $destName = $m ? trim(($m->prenom ?? '') . ' ' . ($m->nom ?? '')) : ('Utilisateur ' . $userId);
                TRecDestinataireMessage::create([
                    'message_id' => $message->id,
                    'direction_destinataire' => $destName,
                    'statut' => 'non_lu'
                ]);
            }

            // 3. Gérer les fichiers joints
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

            // 4. Ne pas envoyer de notifications (différence clef avec le flux standard)

            DB::commit();

            // Charger les relations pour la réponse
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
                'message' => 'Erreur lors de l\'envoi du message de recours: ' . $e->getMessage()
            ], 500);
        }
    }
}