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
        Schema::create('t_rec_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('t_rec_type_id');
            $table->unsignedBigInteger('b_rec_detail_id')->nullable();
            $table->string('libelle');
            $table->timestamps();
            
            // Clé étrangère vers t_rec_type
            $table->foreign('t_rec_type_id')->references('id')->on('t_rec_type')->onDelete('cascade');
            
            // Index pour optimiser les requêtes
            $table->index('t_rec_type_id');
            $table->index('b_rec_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_detail');
    }
};