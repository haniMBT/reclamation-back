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
        Schema::create('b_rec_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_btickes')->comment('ID du ticket associé');
            $table->string('libelle')->comment('Libellé du type');
            $table->string('direction')->nullable()->comment('Direction');
            $table->string('statut_direction')->nullable()->comment('Statut de la direction');
            $table->timestamps();

            // Clé étrangère
            $table->foreign('id_btickes')->references('id')->on('b_rec_tickets')->onDelete('cascade');
            
            // Index
            $table->index('id_btickes');
            $table->index('direction');
            $table->index('statut_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_rec_type');
    }
};