<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Convert existing string values to valid JSON arrays before changing column types
            DB::statement("UPDATE b_rec_type SET direction = JSON_ARRAY(direction) WHERE direction IS NOT NULL AND direction <> ''");
            DB::statement("UPDATE b_rec_detail SET direction = JSON_ARRAY(direction) WHERE direction IS NOT NULL AND direction <> ''");

            // Change column type to JSON
            DB::statement("ALTER TABLE b_rec_type MODIFY COLUMN direction JSON NULL");
            DB::statement("ALTER TABLE b_rec_detail MODIFY COLUMN direction JSON NULL");
        } elseif ($driver === 'sqlsrv') {
            // SQL Server: Drop indexes dependent on the column first
            try {
                DB::statement("DROP INDEX b_rec_type_direction_index ON b_rec_type");
            } catch (\Exception $e) {}
            try {
                DB::statement("DROP INDEX b_rec_detail_direction_index ON b_rec_detail");
            } catch (\Exception $e) {}

            // Manually construct JSON string
            DB::statement("UPDATE b_rec_type SET direction = CONCAT('[\"', direction, '\"]') WHERE direction IS NOT NULL AND direction <> ''");
            DB::statement("UPDATE b_rec_detail SET direction = CONCAT('[\"', direction, '\"]') WHERE direction IS NOT NULL AND direction <> ''");

            // SQL Server doesn't have native JSON type, usually NVARCHAR(MAX) is used
            DB::statement("ALTER TABLE b_rec_type ALTER COLUMN direction NVARCHAR(MAX) NULL");
            DB::statement("ALTER TABLE b_rec_detail ALTER COLUMN direction NVARCHAR(MAX) NULL");
        }
    }

    public function down(): void
    {
        // On ne fait rien dans le down pour éviter les erreurs de conversion de données
        // lors d'un refresh complet. Les tables seront de toute façon supprimées
        // par les migrations de création (create_table) qui seront rollbackées ensuite.
    }
};
