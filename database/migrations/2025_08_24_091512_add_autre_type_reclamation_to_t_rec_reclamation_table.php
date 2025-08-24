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
        Schema::table('t_rec_reclamation', function (Blueprint $table) {
            if (!Schema::hasColumn('t_rec_reclamation', 'autre_type_reclamation')) {
                $table->text('autre_type_reclamation')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_reclamation', function (Blueprint $table) {
            $table->dropColumn('autre_type_reclamation');
        });
    }
};
