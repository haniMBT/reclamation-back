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
            // Charger la relation user si elle n'est pas déjà chargée
            if (!$ticket->relationLoaded('user')) {
                $ticket->load('user');
            }

            // Récupérer toutes les directions associées au ticket
            $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->pluck('direction')
                ->unique()
                ->toArray();

            // Préparer les données du client
            $clientName = $ticket->user
                ? trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? ''))
                : 'Client inconnu';

            // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
            foreach ($ticketDirections as $direction) {
                $targetUser = $this->findEmployeRepondeursByDirection($direction);

                // Créer une notification seulement si un utilisateur valide est trouvé
                if ($targetUser) {
                    $this->createNotification([
                        'tticket_id' => $ticket->id,
                        'sender_id' => $ticket->user_id,
                        'id_recepteur' => $targetUser->id,
                        'direction' => $direction,
                        'message' => "Le client {$clientName} a validé une réclamation.",
                        'type' => 'validation_ticket',
                        'mode' => 'consultation',
                        'meta' => [
                            'ticket_title' => $ticket->objet,
                            'created_by' => $clientName
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
                    'mode' => 'consultation',
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
            $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->pluck('direction')
                ->unique()
                ->toArray();

            // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
            foreach ($ticketDirections as $direction) {
                $targetUser = $this->findEmployeRepondeursByDirection($direction);

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
                        'direction' => $direction,
                        'message' => $messageText,
                        'type' => $notificationType,
                        'mode' => 'consultation',
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

            // Récupérer toutes les directions associées au ticket
            $ticketDirections = TRecTicketDirection::where('tticket_id', $ticket->id)
                ->pluck('direction')
                ->unique()
                ->toArray();

            // Préparer les données du client
            $clientName = $ticket->user
                ? trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? ''))
                : 'Client inconnu';

            // Pour chaque direction, trouver un utilisateur avec le rôle employe_Répondeur
            foreach ($ticketDirections as $direction) {
                $targetUser = $this->findEmployeRepondeursByDirection($direction);

                // Créer une notification seulement si un utilisateur valide est trouvé
                // et que ce n'est pas l'auteur du recours
                if ($targetUser && $targetUser->id != $recoursAuthorId) {
                    $this->createNotification([
                        'tticket_id' => $ticket->id,
                        'sender_id' => $recoursAuthorId,
                        'id_recepteur' => $targetUser->id,
                        'direction' => $direction,
                        'message' => "Le client {$clientName} a effectué un recours sur la réclamation \"{$ticket->objet}\".",
                        'type' => 'creation_recours',
                        'mode' => 'consultation',
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
}