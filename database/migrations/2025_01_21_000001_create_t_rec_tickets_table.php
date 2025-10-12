<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_rec_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bticket_id')->comment('ID du ticket de base (b_rec_tickets)');
            $table->unsignedBigInteger('user_id')->comment('ID de l\'utilisateur qui a créé la réclamation');
            $table->string('direction')->nullable()->comment('Direction concernée');
            $table->enum('status', ['ouvert', 'En cours', 'ferme', 'annule', 'En attente','clôturé','Recours','Recours clôturé'])->default('ouvert')->comment('Statut de la réclamation');
            $table->text('description')->nullable()->comment('Description détaillée de la réclamation');
            $table->text('reply_permission')->nullable()->comment('permission de repondre client et employe');
            $table->timestamp('closed_at')->nullable()->comment('Date de fermeture de la réclamation');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index('bticket_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('direction');
            $table->index(['bticket_id', 'status']); // Index composite pour les requêtes fréquentes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_tickets');
    }
};
