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
            $table->unsignedBigInteger('closed_by')
                ->nullable()
                ->after('closed_at')
                ->comment("ID de l'utilisateur qui a clôturé la réclamation");
            $table->index('closed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropColumn('closed_by');
        });
    }
};