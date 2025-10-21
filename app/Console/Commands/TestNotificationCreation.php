<?php

namespace App\Console\Commands;

use App\Models\ReclamationClient\TRecTicket;
use App\Models\ReclamationClient\TRecNotification;
use App\Services\ReclamationClient\NotificationService;
use Illuminate\Console\Command;

class TestNotificationCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification-creation {ticket_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test la création automatique des notifications lors de la validation d\'un ticket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ticketId = $this->argument('ticket_id');
        
        if (!$ticketId) {
            // Récupérer le premier ticket disponible pour le test
            $ticket = TRecTicket::first();
            if (!$ticket) {
                $this->error('Aucun ticket trouvé dans la base de données.');
                return 1;
            }
            $ticketId = $ticket->id;
        } else {
            $ticket = TRecTicket::find($ticketId);
            if (!$ticket) {
                $this->error("Ticket avec l'ID {$ticketId} introuvable.");
                return 1;
            }
        }

        $this->info("Test de création des notifications pour le ticket ID: {$ticketId}");
        $this->info("Titre du ticket: {$ticket->objet}");
        
        if ($ticket->user) {
            $clientName = trim(($ticket->user->Prenom ?? '') . ' ' . ($ticket->user->Nom ?? ''));
            $this->info("Client: {$clientName}");
        } else {
            $this->warn("Aucun utilisateur associé au ticket");
        }
        
        // Compter les notifications avant
        $notificationsBefore = TRecNotification::where('tticket_id', $ticketId)->count();
        $this->info("Notifications existantes pour ce ticket: {$notificationsBefore}");

        // Tester le service de notification
        try {
            $notificationService = new NotificationService();
            $notificationService->createTicketValidationNotifications($ticket);
            
            // Compter les notifications après
            $notificationsAfter = TRecNotification::where('tticket_id', $ticketId)->count();
            $newNotifications = $notificationsAfter - $notificationsBefore;
            
            $this->info("Nouvelles notifications créées: {$newNotifications}");
            
            if ($newNotifications > 0) {
                $this->info("✅ Test réussi ! Les notifications ont été créées automatiquement.");
                
                // Afficher les détails des nouvelles notifications
                $latestNotifications = TRecNotification::where('tticket_id', $ticketId)
                    ->orderBy('created_at', 'desc')
                    ->limit($newNotifications)
                    ->get();
                
                $this->table(
                    ['ID', 'Direction', 'Message', 'Type', 'Mode'],
                    $latestNotifications->map(function ($notification) {
                        return [
                            $notification->id,
                            $notification->direction,
                            $notification->message,
                            $notification->type,
                            $notification->mode
                        ];
                    })
                );
            } else {
                $this->warn("⚠️ Aucune nouvelle notification créée. Vérifiez qu'il y a des utilisateurs avec le rôle employe_Répondeur dans les directions du ticket.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du test: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
