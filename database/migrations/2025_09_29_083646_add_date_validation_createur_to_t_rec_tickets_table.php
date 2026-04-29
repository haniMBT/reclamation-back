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
            $table->timestamp('date_validation_createur')->nullable()->comment('Date de validation par le créateur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropColumn('date_validation_createur');
        });
    }
};
