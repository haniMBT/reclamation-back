<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_rec_commission_recours', function (Blueprint $table) {
            $table->id();
            // users.id est un identifiant string dans ce projet; utiliser string pour éviter les problèmes de FK
            $table->string('user_id')->nullable();
            $table->index('user_id');
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('email')->nullable();
            $table->string('matricule')->nullable();
            $table->string('direction')->nullable();
            $table->string('role')->nullable(); // valeurs possibles: "membre" ou "président"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_commission_recours');
    }
};