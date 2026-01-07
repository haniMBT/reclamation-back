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
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table_name = 't_rec_tickets';

            // Fonction helper pour vérifier si un index existe
            $indexExists = function($table, $indexName) {
                $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                return count($indexes) > 0;
            };

            if (!$indexExists($table_name, 't_rec_tickets_status_index')) {
                $table->index('status');
            }
            if (!$indexExists($table_name, 't_rec_tickets_user_id_index')) {
                $table->index('user_id');
            }
            if (!$indexExists($table_name, 't_rec_tickets_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rec_tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });
    }
};
