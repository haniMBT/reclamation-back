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
        Schema::create('b_rec_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('libelle')->comment('Libellé du ticket');
            $table->text('documentAfornir')->nullable()->comment('Document à fournir');
            $table->string('direction')->nullable()->comment('Direction');
            $table->timestamps();

            // Index
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_rec_tickets');
    }
};