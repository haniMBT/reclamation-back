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
        Schema::table('t_rec_message', function (Blueprint $table) {
            // Ajout du champ 'messag_vers' de type TEXT, nullable
            $table->text('message_vers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_message', function (Blueprint $table) {
            $table->dropColumn('message_vers');
        });
    }
};
