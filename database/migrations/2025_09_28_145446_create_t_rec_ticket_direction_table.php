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
        Schema::create('t_rec_ticket_direction', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('direction')->nullable();
            $table->string('statut_direction')->nullable();
            $table->string('source_orientation')->nullable();
            $table->string('type_orientation')->nullable();
            $table->timestamps();
            
            // Clé étrangère vers la table t_rec_tickets
            $table->foreign('ticket_id')->references('id')->on('t_rec_tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_ticket_direction');
    }
};
