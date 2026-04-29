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
            $table->boolean('accepter_piloter')->nullable()->after('motif_changement');
            $table->text('motif_refu_changement')->nullable()->after('accepter_piloter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropColumn(['accepter_piloter', 'motif_refu_changement']);
        });
    }
};