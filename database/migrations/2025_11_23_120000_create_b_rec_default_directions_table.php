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
        Schema::create('b_rec_default_directions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bticket_id');
            $table->string('direction', 100);
            $table->string('statut_direction', 50)->nullable();
            $table->timestamps();

            $table->foreign('bticket_id')->nullable()
                ->references('id')
                ->on('b_rec_tickets')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_rec_default_directions');
    }
};
