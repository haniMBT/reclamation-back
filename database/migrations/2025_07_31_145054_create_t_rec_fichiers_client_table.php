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
        Schema::create('t_rec_fichiers_client', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reclamation_id')->comment('ID de la réclamation associée');
            $table->string('nom_original')->comment('Nom original du fichier');
            $table->string('nom_stockage')->comment('Nom du fichier sur le serveur');
            $table->string('chemin')->comment('Chemin de stockage du fichier');
            $table->bigInteger('taille')->comment('Taille du fichier en octets');
            $table->string('type_mime')->comment('Type MIME du fichier');
            $table->timestamp('date_upload')->nullable()->comment('Date d\'upload du fichier');
            $table->timestamps();

            // Clé étrangère et index
            $table->foreign('reclamation_id')->references('id')->on('t_rec_reclamation')->onDelete('cascade');
            $table->index('reclamation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_fichiers_client');
    }
};
