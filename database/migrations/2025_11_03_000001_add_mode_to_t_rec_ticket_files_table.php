<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_rec_ticket_files', function (Blueprint $table) {
            $table->string('mode')->nullable()->index()->after('type_fichier');
        });
    }

    public function down(): void
    {
        Schema::table('t_rec_ticket_files', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};