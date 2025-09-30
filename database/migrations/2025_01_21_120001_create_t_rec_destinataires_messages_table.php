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
        Schema::create('t_rec_destinataires_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->text('direction_destinataire');
            $table->string('statut')->default('non_lu');
            $table->timestamp('date_lecture')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamps();

            // Clés étrangères (seront ajoutées après création des tables)
            // $table->foreign('message_id')->references('id')->on('t_rec_message')->onDelete('cascade');
            // direction_destinataire est maintenant un champ text, pas de clé étrangère

            // Index
            $table->index('message_id');
            $table->index(['lu', 'date_lecture'], 'idx_lu_date');
            $table->index('statut');
            // direction_destinataire est un champ text, pas d'index dessus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_destinataires_messages');
    }
};