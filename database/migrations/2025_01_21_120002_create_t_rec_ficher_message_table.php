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
        Schema::create('t_rec_ficher_message', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('nom_fichier');
            $table->string('nom_fichier_stocke');
            $table->string('chemin_fichier');
            $table->string('type_mime')->nullable();
            $table->unsignedBigInteger('taille_fichier')->nullable();
            $table->timestamp('date_upload');
            $table->timestamps();

            // Clé étrangère (sera ajoutée après création des tables)
            // $table->foreign('message_id')->references('id')->on('t_rec_message')->onDelete('cascade');

            // Index
            $table->index('message_id');
            $table->index('nom_fichier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_ficher_message');
    }
};