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
            $table->string('nom')->nullable()->after('user_id')->comment('Nom de l\'utilisateur qui a créé la réclamation');
            $table->string('prenom')->nullable()->after('nom')->comment('Prénom de l\'utilisateur qui a créé la réclamation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropColumn(['nom', 'prenom']);
        });
    }
};
