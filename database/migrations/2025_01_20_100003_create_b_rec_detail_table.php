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
        Schema::create('b_rec_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_btype')->comment('ID du type associé');
            $table->string('libelle')->comment('Libellé du détail');
            $table->string('direction')->nullable()->comment('Direction');
            $table->string('statut_direction')->nullable()->comment('Statut de la direction');
            $table->timestamps();

            // Clé étrangère
            $table->foreign('id_btype')->references('id')->on('b_rec_type')->onDelete('cascade');
            
            // Index
            $table->index('id_btype');
            $table->index('direction');
            $table->index('statut_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_rec_detail');
    }
};