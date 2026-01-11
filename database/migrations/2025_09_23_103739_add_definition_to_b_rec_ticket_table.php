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
        Schema::table('b_rec_tickets', function (Blueprint $table) {
             if (!Schema::hasColumn('b_rec_tickets', 'definition')) {
                $table->text('definition')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b_rec_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('b_rec_tickets', 'definition')) {
                $table->dropColumn('definition');
            }
        });
    }
};
