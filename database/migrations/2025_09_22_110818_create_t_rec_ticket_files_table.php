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
        Schema::create('t_rec_ticket_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->bigInteger('taille_fichier')->nullable();
            $table->string('type_fichier')->nullable();
            $table->timestamps();

            // Index et clé étrangère
            $table->index('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('t_rec_tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_ticket_files');
    }
};
