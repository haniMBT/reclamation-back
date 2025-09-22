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
        Schema::create('t_rec_type', function (Blueprint $table) {
            $table->id();
            $table->string('tticket_id');
            $table->unsignedBigInteger('b_rec_type_id')->nullable();
            $table->string('libelle');
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index('tticket_id');
            $table->index('b_rec_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_type');
    }
};