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
        Schema::create('b_rec_ticket_files', function (Blueprint $table) {
            $table->id();
            // Référence au ticket paramétré
            $table->foreignId('bticket_id')
                ->constrained('b_rec_tickets')
                ->onDelete('cascade');

            // Nom du fichier demandé
            $table->string('libelle', 255);

            // Fichier obligatoire ou non
            $table->boolean('obligatoire')->default(false);

            // Optionnel: préciser le format/type de fichier accepté (ex: pdf, jpg, png)
            $table->string('format_fichier', 50)->nullable();

            $table->timestamps();

            $table->index('bticket_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_rec_ticket_files');
    }
};