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
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('t_rec_tickets', 'motif_changement')) {
                $table->text('motif_changement')->nullable()->after('conclusion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('t_rec_tickets', 'motif_changement')) {
                $table->dropColumn('motif_changement');
            }
        });
    }
};