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
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->datetime('date_en_cours')->nullable()->comment('Date à laquelle le ticket passe en traitement/en cours');
            $table->datetime('date_recours')->nullable()->comment('Date à laquelle le ticket entre en recours');
            $table->datetime('date_cloture_recours')->nullable()->comment('Date à laquelle le recours est clôturé');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropColumn(['date_en_cours', 'date_recours', 'date_cloture_recours']);
        });
    }
};
