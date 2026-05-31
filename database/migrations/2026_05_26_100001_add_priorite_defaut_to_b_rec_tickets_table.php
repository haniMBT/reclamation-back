<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('b_rec_tickets', function (Blueprint $table) {
            $table->string('priorite_defaut', 20)->default('normal')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('b_rec_tickets', function (Blueprint $table) {
            $table->dropColumn('priorite_defaut');
        });
    }
};
