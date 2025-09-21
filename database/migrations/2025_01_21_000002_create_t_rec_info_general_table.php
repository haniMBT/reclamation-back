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
        Schema::create('t_rec_info_general', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tticket_id')->comment('ID de la réclamation (t_rec_tickets)');
            $table->unsignedBigInteger('info_general_id')->comment('ID de l\'information générale (b_rec_info_general)');
            $table->string('libelle')->comment('Libellé de l\'information');
            $table->boolean('key_attribut')->default(false)->comment('Indique si c\'est un attribut clé pour la vérification de doublons');
            $table->string('value', 191)->nullable()->comment('Valeur saisie par l\'utilisateur');
            $table->timestamps();

            // Clés étrangères
            $table->foreign('tticket_id')->references('id')->on('t_rec_tickets')->onDelete('cascade');
            $table->foreign('info_general_id')->references('id')->on('b_rec_info_general')->onDelete('cascade');

            // Index pour optimiser les requêtes
            $table->index('tticket_id');
            $table->index('info_general_id');
            $table->index('key_attribut');
            $table->index(['tticket_id', 'key_attribut']); // Index composite pour la vérification de doublons
            $table->index(['info_general_id', 'value']);   // Index composite pour les recherches par valeur

            // Index unique pour éviter les doublons d'informations pour un même ticket
            $table->unique(['tticket_id', 'info_general_id'], 'unique_ticket_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_info_general');
    }
};
