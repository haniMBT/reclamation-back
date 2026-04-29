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
        Schema::table('b_rec_info_general', function (Blueprint $table) {
            $table->string('type')->nullable()->after('key_attirubut')->comment('Type de champ (date, texte, montant, numéro), nullable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b_rec_info_general', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};