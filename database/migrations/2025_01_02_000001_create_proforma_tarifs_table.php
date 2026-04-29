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
        Schema::create('proforma_tarifs', function (Blueprint $table) {
            $table->id();
            $table->string('prscod')->unique()->comment('Code prestation');
            $table->string('prslib')->comment('Libellé prestation');
            $table->decimal('prspun', 10, 2)->comment('Prix unitaire');
            $table->timestamps();
            
            $table->index('prscod');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_tarifs');
    }
};