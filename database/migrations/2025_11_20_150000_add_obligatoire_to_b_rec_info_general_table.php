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
            $table->boolean('obligatoire')
                ->default(false)
                ->after('type')
                ->comment('Indique si le champ est obligatoire');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b_rec_info_general', function (Blueprint $table) {
            $table->dropColumn('obligatoire');
        });
    }
};