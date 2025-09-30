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
        Schema::create('t_rec_message', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tticket_id');
            $table->string('titre');
            $table->text('texte');
            $table->text('direction_envoi')->nullable(); // Direction de l'utilisateur qui envoie (texte libre)
            $table->string('sender_id'); // ID de l'utilisateur qui envoie (string car users.id est string)
            $table->timestamp('date_envoie');
            $table->timestamps();

            // Clés étrangères (seront ajoutées après création des tables)
            // $table->foreign('tticket_id')->references('id')->on('t_rec_tickets')->onDelete('cascade');
            // $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            // Note: direction_envoi est maintenant un champ text libre, pas de clé étrangère

            // Index
            $table->index(['tticket_id', 'date_envoie']);
            $table->index('sender_id');
            // Index supprimé car direction_envoi est maintenant un champ text
            // $table->index('direction_envoi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_message');
    }
};