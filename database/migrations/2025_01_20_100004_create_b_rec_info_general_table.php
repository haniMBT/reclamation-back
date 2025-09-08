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
        Schema::create('b_rec_info_general', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bticket_id')->comment('ID du ticket associé');
            $table->string('libelle')->comment('Libellé de l\'information générale');
            $table->boolean('key_attirubut')->default(false)->comment('Attribut clé');
            $table->timestamps();

            // Clé étrangère
            $table->foreign('bticket_id')->references('id')->on('b_rec_tickets')->onDelete('cascade');
            
            // Index
            $table->index('bticket_id');
            $table->index('key_attirubut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_rec_info_general');
    }
};