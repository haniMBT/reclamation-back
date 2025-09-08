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
        // Modifier la table b_rec_type
        Schema::table('b_rec_type', function (Blueprint $table) {
            $table->enum('statut_direction', ['consultation', 'traitement'])->nullable()->change();
        });

        // Modifier la table b_rec_detail
        Schema::table('b_rec_detail', function (Blueprint $table) {
            $table->enum('statut_direction', ['consultation', 'traitement'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir au type string pour b_rec_type
        Schema::table('b_rec_type', function (Blueprint $table) {
            $table->string('statut_direction')->nullable()->change();
        });

        // Revenir au type string pour b_rec_detail
        Schema::table('b_rec_detail', function (Blueprint $table) {
            $table->string('statut_direction')->nullable()->change();
        });
    }
};