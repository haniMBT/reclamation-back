<?php

namespace App\Services\ReclamationClient;

use App\Models\ReclamationClient\TRecNotification;
use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\TRecTicketDirection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Créer des notifications automatiques lors de la validation d'un ticket par un client
     *
     * @param TRecTicket $ticket
     * @return void
     */
    public function createTicketValidationNotifications(TRecTicket $ticket): void
    {
        try {
            // Charger les relations nécessaires
            if (!$ticket->relationLoaded('user')) {
                $ticket->load('user');
            }
            if (!$ticket->relationLoaded('baseTicket')) {
                $ticket->load('baseTicket');
            }

            // Récupérer toutes les directions associées au ticket avec leurs informations
            $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)->get();

            // Préparer les données du client (nom et prénom en majuscules)
            $clientName = $ticket->user
                ? strtoupper(trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? '')))
                : 'CLIENT INCONNU';

            // Récupérer le libellé du ticket de base
            $libelle = $ticket->baseTicket ? $ticket->baseTicket->libelle : $ticket->objet;

            // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
            foreach ($ticketDirections as $ticketDirection) {
                $targetUser = $this->findEmployeRepondeursByDirection($ticketDirection->direction);

                // Créer une notification seulement si un utilisateur valide est trouvé
                if ($targetUser) {
                    // Déterminer le message selon type_orientation et statut_direction
                    $message = $this->generateValidationMessage(
                        $clientName,
                        $libelle,
                        $ticketDirection->type_orientation,
                        $ticketDirection->statut_direction
                    );

                    $this->createNotification([
                        'tticket_id' => $ticket->id,
                        'sender_id' => $ticket->user_id,
                        'id_recepteur' => $targetUser->id,
                        'direction' => $ticketDirection->direction,
                        'message' => $message,
                        'type' => 'validation_ticket',
                        'mode' => $ticketDirection->statut_direction,
                        'meta' => [
                            'ticket_title' => $ticket->objet,
                            'created_by' => $clientName,
                            'type_orientation' => $ticketDirection->type_orientation,
                            'statut_direction' => $ticketDirection->statut_direction,
                            'libelle' => $libelle
                        ],
                        'is_read' => 0
                    ]);
                }
            }

            Log::info("Notifications créées pour la validation du ticket {$ticket->id}");

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création des notifications pour le ticket {$ticket->id}: " . $e->getMessage());
        }
    }

    /**
     * Générer le message de notification selon type_orientation et statut_direction
     *
     * @param string $clientName
     * @param string $libelle
     * @param string $typeOrientation
     * @param string $statutDirection
     * @return string
     */
    private function generateValidationMessage(string $clientName, string $libelle, string $typeOrientation, string $statutDirection): string
    {
        if ($typeOrientation === 'ticket') {
            return "{$clientName} a validé la réclamation « {$libelle} ». Vous êtes invité en tant que pilote à résoudre et répondre à cette réclamation.";
        }

        // Si type_orientation != "ticket"
        if ($statutDirection === 'consultation') {
            return "{$clientName} a validé la réclamation « {$libelle} ». Vous êtes invité à consulter le traitement et les réponses associées à cette réclamation.";
        } elseif ($statutDirection === 'traitement') {
            return "{$clientName} a validé la réclamation « {$libelle} ». Vous êtes invité à collaborer au traitement de cette réclamation.";
        }

        // Message par défaut si aucune condition n'est remplie
        return "{$clientName} a validé la réclamation « {$libelle} ».";
    }

    /**
     * Trouver un utilisateur avec le rôle employe_Répondeur pour une direction donnée
     *
     * @param string $direction
     * @return User|null
     */
    private function findEmployeRepondeursByDirection(string $direction)
    {
        // Chercher un utilisateur appartenant à cette direction
        $user = User::where('direction', $direction)->first();

        // Si aucun utilisateur trouvé dans cette direction, retourner null
        if (!$user) {
            return null;
        }

        // Vérifier si cet utilisateur a le bon privilège
        $hasPrivilege = DB::table('p_privileges')
            ->join('p_profils', 'p_profils.code', '=', 'p_privileges.profil_code')
            ->where('p_profils.code', $user->privilege)
            ->where('p_privileges.volet', 'liste_des_reclamations')
            ->where('p_privileges.role', 'employe_Répondeur')
            ->exists();

        // Retourner l'utilisateur seulement s'il a le bon privilège
        return $hasPrivilege ? $user : null;
    }

    /**
     * Créer une notification dans la base de données
     *
     * @param array $notificationData
     * @return TRecNotification
     */
    private function createNotification(array $notificationData): TRecNotification
    {
        return TRecNotification::create($notificationData);
    }

    /**
     * Créer des notifications pour la clôture d'un ticket
     *
     * @param TRecTicket $ticket
     * @param int $closedByUserId ID de l'utilisateur qui a effectué la clôture
     * @return void
     */
    public function createTicketClosureNotifications(TRecTicket $ticket, int $closedByUserId): void
    {
        try {
            // Charger la relation user si elle n'est pas déjà chargée
            if (!$ticket->relationLoaded('user')) {
                $ticket->load('user');
            }

            // Préparer les données du client
            $clientName = $ticket->user
                ? trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? ''))
                : 'Client inconnu';

            // 1. Envoyer une notification au créateur du ticket (s'il n'est pas celui qui a clôturé)
            if ($ticket->user_id && $ticket->user_id != $closedByUserId) {
                // Adapter le message selon le type de clôture
                $isRecoursClotured = $ticket->status === 'Recours clôturé';
                $messageText = $isRecoursClotured
                    ? "Votre recours sur la réclamation \"{$ticket->objet}\" a été clôturé."
                    : "Votre réclamation \"{$ticket->objet}\" a été clôturée.";

                $notificationType = $isRecoursClotured ? 'cloture_recours' : 'cloture_ticket';

                $this->createNotification([
                    'tticket_id' => $ticket->id,
                    'sender_id' => $closedByUserId,
                    'id_recepteur' => $ticket->user_id,
                    'direction' =>$ticket->user ? $ticket->user->direction : null,
                    'message' => $messageText,
                    'type' => $notificationType,
                    'mode' => null,
                    'meta' => [
                        'ticket_title' => $ticket->objet,
                        'status' => $ticket->status,
                        'conclusion' => $ticket->conclusion ?? ''
                    ],
                    'is_read' => 0
                ]);
            }

            // 2. Envoyer des notifications aux utilisateurs concernés (comme après validation)
            // Récupérer toutes les directions associées au ticket
            $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)->get();

            // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
            foreach ($ticketDirections as $ticketDirection) {
                $targetUser = $this->findEmployeRepondeursByDirection($ticketDirection->direction);

                // Créer une notification seulement si un utilisateur valide est trouvé
                // et s'il n'est pas celui qui a effectué la clôture
                if ($targetUser && $targetUser->id != $closedByUserId) {
                    // Adapter le message selon le type de clôture
                    $isRecoursClotured = $ticket->status === 'Recours clôturé';
                    $messageText = $isRecoursClotured
                        ? "Le recours de {$clientName} sur la réclamation \"{$ticket->objet}\" a été clôturé."
                        : "La réclamation de {$clientName} \"{$ticket->objet}\" a été clôturée.";

                    $notificationType = $isRecoursClotured ? 'cloture_recours' : 'cloture_ticket';

                    $this->createNotification([
                        'tticket_id' => $ticket->id,
                        'sender_id' => $closedByUserId,
                        'id_recepteur' => $targetUser->id,
                        'direction' => $ticketDirection->direction,
                        'message' => $messageText,
                        'type' => $notificationType,
                        'mode' => $ticketDirection->statut_direction,
                        'meta' => [
                            'ticket_title' => $ticket->objet,
                            'created_by' => $clientName,
                            'status' => $ticket->status,
                            'conclusion' => $ticket->conclusion ?? ''
                        ],
                        'is_read' => 0
                    ]);
                }
            }

            Log::info("Notifications de clôture créées pour le ticket {$ticket->id}");

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création des notifications de clôture pour le ticket {$ticket->id}: " . $e->getMessage());
        }
    }

    /**
     * Créer des notifications pour la création d'un recours
     *
     * @param TRecTicket $ticket
     * @param int $recoursAuthorId ID de l'utilisateur qui a créé le recours
     * @return void
     */
    public function createRecoursCreationNotifications(TRecTicket $ticket, int $recoursAuthorId): void
    {
        try {
            // Charger la relation user si elle n'est pas déjà chargée
            if (!$ticket->relationLoaded('user')) {
                $ticket->load('user');
            }
            if (!$ticket->relationLoaded('baseTicket')) {
                $ticket->load('baseTicket');
            }

            // Récupérer toutes les directions associées au ticket
            $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)->get();

            // Préparer les données du client
            $clientName = $ticket->user
                ? trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? ''))
                : 'Client inconnu';

            $libelle= $ticket->baseTicket->libelle;

            // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
            foreach ($ticketDirections as $ticketDirection) {
                $targetUser = $this->findEmployeRepondeursByDirection($ticketDirection->direction);

                // Créer une notification seulement si un utilisateur valide est trouvé
                // et que ce n'est pas l'auteur du recours
                if ($targetUser && $targetUser->id != $recoursAuthorId) {
                    $this->createNotification([
                        'tticket_id' => $ticket->id,
                        'sender_id' => $recoursAuthorId,
                        'id_recepteur' => $targetUser->id,
                        'direction' => $ticketDirection->direction,
                        'message' => "Le client {$clientName} a effectué un recours sur la réclamation \"{$ticket->objet}\" ticket \"{$libelle}\".",
                        'type' => 'creation_recours',
                        'mode' => $ticketDirection->statut_direction,
                        'meta' => [
                            'ticket_title' => $ticket->objet,
                            'client_name' => $clientName,
                            'status' => $ticket->status
                        ],
                        'is_read' => 0
                    ]);
                }
            }

            Log::info("Notifications créées pour le recours du ticket {$ticket->id}");

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création des notifications pour le recours du ticket {$ticket->id}: " . $e->getMessage());
        }
    }

    /**
     * Créer des notifications pour la clôture d'un recours (optionnel pour plus tard)
     *
     * @param TRecTicket $ticket
     * @return void
     */
    public function createRecoursClosureNotifications(TRecTicket $ticket): void
    {
        // TODO: Implémenter la logique pour la clôture de recours
        // Cette méthode pourra être utilisée plus tard
    }

    /**
     * Créer des notifications pour les réponses aux messages
     *
     * @param TRecTicket $ticket
     * @param int $senderId ID de l'utilisateur qui a envoyé la réponse
     * @param bool $isClient True si l'expéditeur est le client, false si c'est un employé
     * @return void
     */
    public function createMessageReplyNotifications(TRecTicket $ticket, int $senderId, bool $isClient): void
    {
        try {
            // Charger la relation user si elle n'est pas déjà chargée
            if (!$ticket->relationLoaded('user')) {
                $ticket->load('user');
            }
            if (!$ticket->relationLoaded('baseTicket')) {
                $ticket->load('baseTicket');
            }
            $libelle= $ticket->baseTicket->libelle;

            // Préparer les données du client
            $clientName = $ticket->user
                ? trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? ''))
                : 'Client inconnu';

            if ($isClient) {
                // Cas 1: Le client envoie une réponse
                // Envoyer une notification à tous les employés répondeurs concernés

                // Récupérer toutes les directions associées au ticket
                $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)->get();

                // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
                foreach ($ticketDirections as $ticketDirection) {
                    $targetUser = $this->findEmployeRepondeursByDirection($ticketDirection->direction);

                    // Créer une notification seulement si un utilisateur valide est trouvé
                    // et que ce n'est pas l'expéditeur (ne devrait pas arriver car client != employé)
                    if ($targetUser && $targetUser->id != $senderId) {
                        $this->createNotification([
                            'tticket_id' => $ticket->id,
                            'sender_id' => $senderId,
                            'id_recepteur' => $targetUser->id,
                            'direction' => $ticketDirection->direction,
                            'message' => "Le client {$clientName} a répondu à la réclamation \"{$ticket->objet}\" ticket \"{$libelle}\".",
                            'type' => 'reponse_client',
                            'mode' => $ticketDirection->statut_direction,
                            'meta' => [
                                'ticket_title' => $ticket->objet,
                                'client_name' => $clientName,
                                'status' => $ticket->status
                            ],
                            'is_read' => 0
                        ]);
                    }
                }

                Log::info("Notifications créées pour la réponse client du ticket {$ticket->id}");

            } else {
                // Cas 2: Un employé répondeur envoie une réponse
                // Envoyer une notification uniquement au client (créateur du ticket)

                if ($ticket->user_id && $ticket->user_id != $senderId) {
                    $this->createNotification([
                        'tticket_id' => $ticket->id,
                        'sender_id' => $senderId,
                        'id_recepteur' => $ticket->user_id,
                        'direction' => $ticket->user ? $ticket->user->direction : null,
                        'message' => "Vous avez reçu une réponse à votre réclamation \"{$ticket->objet}\".",
                        'type' => 'reponse_employe',
                        'mode' => null,
                        'meta' => [
                            'ticket_title' => $ticket->objet,
                            'status' => $ticket->status
                        ],
                        'is_read' => 0
                    ]);

                    Log::info("Notification créée pour la réponse employé du ticket {$ticket->id} vers le client {$ticket->user_id}");
                }
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création des notifications pour la réponse du ticket {$ticket->id}: " . $e->getMessage());
        }
    }

    /**
     * Créer des notifications pour l'envoi de messages à d'autres directions
     *
     * @param TRecTicket $ticket
     * @param int $senderId ID de l'utilisateur qui a envoyé le message
     * @param array $directionsDestinaires Liste des directions destinataires
     * @return void
     */
    public function createDirectionMessageNotifications(TRecTicket $ticket, int $senderId, array $directionsDestinaires): void
    {

        try {

            // Charger la relation user si elle n'est pas déjà chargée
            if (!$ticket->relationLoaded('user')) {
                $ticket->load('user');
            }

            // Préparer les données de l'expéditeur
            $senderUser = \App\Models\User::find($senderId);
            $senderName = $senderUser
                ? trim(($senderUser->Prenom ?? '') . ' ' . ($senderUser->Nom ?? ''))
                : 'Utilisateur inconnu';


            // Pour chaque direction destinataire
            foreach ($directionsDestinaires as $direction) {
                // Trouver les utilisateurs employés répondeurs de cette direction
                $targetUser = $this->findEmployeRepondeursByDirection($direction);

                // Créer une notification pour chaque utilisateur trouvé
                // foreach ($targetUsers as $targetUser) {

                    // Ne pas notifier l'expéditeur lui-même
                    if ($targetUser->id != $senderId) {
                        $this->createNotification([
                            'tticket_id' => $ticket->id,
                            'sender_id' => $senderId,
                            'id_recepteur' => $targetUser->id,
                            'direction' => $direction,
                            'message' => "{$senderName} vous a envoyé un message concernant la réclamation \"{$ticket->objet}\".",
                            'type' => 'message_direction',
                            'mode' => 'consultation',
                            'meta' => [
                                'ticket_title' => $ticket->objet,
                                'sender_name' => $senderName,
                                'sender_direction' => $senderUser ? $senderUser->direction : null,
                                'status' => $ticket->status
                            ],
                            'is_read' => 0
                        ]);
                    }
                // }
            }

            Log::info("Notifications créées pour le message du ticket {$ticket->id} vers les directions: " . implode(', ', $directionsDestinaires));

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création des notifications pour le message du ticket {$ticket->id}: " . $e->getMessage());
        }
    }
}
