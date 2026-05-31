<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->string('priorite', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropColumn('priorite');
        });
    }
};
