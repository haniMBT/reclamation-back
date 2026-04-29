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
        // On récupère la liste des index existants avant de modifier la table
        $existingIndexes = $this->getExistingIndexes('t_rec_tickets');

        Schema::table('t_rec_tickets', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('t_rec_tickets_status_index', $existingIndexes)) {
                $table->index('status');
            }
            if (!in_array('t_rec_tickets_user_id_index', $existingIndexes)) {
                $table->index('user_id');
            }
            if (!in_array('t_rec_tickets_created_at_index', $existingIndexes)) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Récupère les noms des index pour une table donnée de manière compatible MySQL/SQL Server
     */
    private function getExistingIndexes($tableName)
    {
        $indexes = [];
        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'mysql') {
                $results = DB::select("SHOW INDEX FROM {$tableName}");
                foreach ($results as $result) {
                    $indexes[] = $result->Key_name;
                }
            } elseif ($driver === 'sqlsrv') {
                $results = DB::select("SELECT name FROM sys.indexes WHERE object_id = OBJECT_ID(?)", [$tableName]);
                foreach ($results as $result) {
                    $indexes[] = $result->name;
                }
            }
        } catch (\Exception $e) {
            // Si la table n'existe pas encore ou autre erreur, on retourne un tableau vide
            return [];
        }

        return $indexes;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            // La suppression gère généralement bien l'absence, mais on peut sécuriser si besoin
            // Pour l'instant on laisse le standard Laravel qui devrait fonctionner
            try {
                $table->dropIndex(['status']);
            } catch (\Exception $e) {}

            try {
                $table->dropIndex(['user_id']);
            } catch (\Exception $e) {}

            try {
                $table->dropIndex(['created_at']);
            } catch (\Exception $e) {}
        });
    }
};
