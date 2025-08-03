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
        Schema::create('t_rec_reclamation', function (Blueprint $table) {
            $table->id();
            $table->string('objet')->comment('Objet de la réclamation');
            $table->text('contenu')->comment('Contenu détaillé de la réclamation');
            $table->unsignedBigInteger('user_id')->comment('ID de l\'utilisateur qui a créé la réclamation');
            $table->enum('statut', ['nouvelle', 'en_cours', 'traitee', 'fermee'])->default('nouvelle')->comment('Statut de la réclamation');
            $table->timestamp('date_creation')->nullable()->comment('Date de création de la réclamation');
            $table->timestamp('date_traitement')->nullable()->comment('Date de traitement de la réclamation');
            $table->text('reponse')->nullable()->comment('Réponse à la réclamation');
            $table->unsignedBigInteger('traite_par')->nullable()->comment('ID de l\'utilisateur qui a traité la réclamation');
            $table->timestamps();

            // Index et contraintes
            $table->index('user_id');
            $table->index('statut');
            $table->index('date_creation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_reclamation');
    }
};
