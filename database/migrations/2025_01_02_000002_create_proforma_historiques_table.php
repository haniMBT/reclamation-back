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
        Schema::create('proforma_historiques', function (Blueprint $table) {
            $table->id();
            $table->string('cnsbld')->comment('BL');
            $table->string('dctcod')->comment('Numéro conteneur');
            $table->boolean('scan')->default(false)->comment('Avec scanner');
            $table->date('date_fin')->comment('Date de fin prévisionnelle');
            $table->decimal('ttc', 12, 2)->comment('Montant TTC');
            
            // On utilise integer car users.id est int sur SQL Server existant
            // On ne met pas de contrainte foreign key car users.id n'a pas de PK
            $table->integer('user_id'); 
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('cnsbld');
            $table->index('dctcod');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_historiques');
    }
};
