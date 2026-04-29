<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            // SQL Server ne supporte pas enum()->change() directement
            // On s'assure d'abord que la colonne est de type compatible
            DB::statement("ALTER TABLE b_rec_type ALTER COLUMN statut_direction NVARCHAR(255) NULL");
            // On ajoute la contrainte CHECK manuellement
            // On supprime d'abord la contrainte si elle existe (pour éviter erreur si re-run)
            try {
                DB::statement("ALTER TABLE b_rec_type DROP CONSTRAINT ck_b_rec_type_statut_direction");
            } catch (\Exception $e) {}
            DB::statement("ALTER TABLE b_rec_type ADD CONSTRAINT ck_b_rec_type_statut_direction CHECK (statut_direction IN ('consultation', 'traitement'))");

            // Pour b_rec_detail
            DB::statement("ALTER TABLE b_rec_detail ALTER COLUMN statut_direction NVARCHAR(255) NULL");
            try {
                DB::statement("ALTER TABLE b_rec_detail DROP CONSTRAINT ck_b_rec_detail_statut_direction");
            } catch (\Exception $e) {}
            DB::statement("ALTER TABLE b_rec_detail ADD CONSTRAINT ck_b_rec_detail_statut_direction CHECK (statut_direction IN ('consultation', 'traitement'))");

        } else {
            // Modifier la table b_rec_type
            Schema::table('b_rec_type', function (Blueprint $table) {
                $table->enum('statut_direction', ['consultation', 'traitement'])->nullable()->change();
            });

            // Modifier la table b_rec_detail
            Schema::table('b_rec_detail', function (Blueprint $table) {
                $table->enum('statut_direction', ['consultation', 'traitement'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            // Supprimer les contraintes CHECK
            try {
                DB::statement("ALTER TABLE b_rec_type DROP CONSTRAINT ck_b_rec_type_statut_direction");
            } catch (\Exception $e) {}
            try {
                DB::statement("ALTER TABLE b_rec_detail DROP CONSTRAINT ck_b_rec_detail_statut_direction");
            } catch (\Exception $e) {}
            
            // Revenir à string simple (déjà le cas physiquement, mais pour la forme)
            DB::statement("ALTER TABLE b_rec_type ALTER COLUMN statut_direction NVARCHAR(255) NULL");
            DB::statement("ALTER TABLE b_rec_detail ALTER COLUMN statut_direction NVARCHAR(255) NULL");

        } else {
            // Revenir au type string pour b_rec_type
            Schema::table('b_rec_type', function (Blueprint $table) {
                $table->string('statut_direction')->nullable()->change();
            });

            // Revenir au type string pour b_rec_detail
            Schema::table('b_rec_detail', function (Blueprint $table) {
                $table->string('statut_direction')->nullable()->change();
            });
        }
    }
};
