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
        Schema::create('t_rec_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tticket_id');
            $table->unsignedInteger('sender_id')->nullable();
            $table->unsignedInteger('id_recepteur')->nullable();
            $table->string('direction')->nullable();
            $table->text('message');
            $table->string('type', 100)->nullable();
            $table->string('mode', 100)->nullable();
            $table->json('meta')->nullable();
            $table->tinyInteger('is_read')->default(0);
            $table->timestamps();

            // Index
            $table->index('tticket_id');
            $table->index('is_read');

            // Foreign key constraints
            $table->foreign('tticket_id')->references('id')->on('t_rec_tickets')->onDelete('cascade');
            // Note: Foreign key for id_recepteur will be added separately due to potential type mismatch
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rec_notifications');
    }
};
