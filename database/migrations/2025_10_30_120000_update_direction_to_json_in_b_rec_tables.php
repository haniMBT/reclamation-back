<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing string values to valid JSON arrays before changing column types
        DB::statement("UPDATE b_rec_type SET direction = JSON_ARRAY(direction) WHERE direction IS NOT NULL AND direction <> ''");
        DB::statement("UPDATE b_rec_detail SET direction = JSON_ARRAY(direction) WHERE direction IS NOT NULL AND direction <> ''");

        // Change column type to JSON
        DB::statement("ALTER TABLE b_rec_type MODIFY COLUMN direction JSON NULL");
        DB::statement("ALTER TABLE b_rec_detail MODIFY COLUMN direction JSON NULL");
    }

    public function down(): void
    {
        // Convert JSON arrays back to string (first element) then revert column type
        DB::statement("UPDATE b_rec_type SET direction = JSON_UNQUOTE(JSON_EXTRACT(direction, '$[0]')) WHERE direction IS NOT NULL");
        DB::statement("ALTER TABLE b_rec_type MODIFY COLUMN direction VARCHAR(255) NULL");

        DB::statement("UPDATE b_rec_detail SET direction = JSON_UNQUOTE(JSON_EXTRACT(direction, '$[0]')) WHERE direction IS NOT NULL");
        DB::statement("ALTER TABLE b_rec_detail MODIFY COLUMN direction VARCHAR(255) NULL");
    }
};