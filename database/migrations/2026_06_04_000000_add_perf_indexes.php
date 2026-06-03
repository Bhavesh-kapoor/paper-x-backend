<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds indexes on hot query columns identified during the latency audit.
 * Index creation is guarded so the migration is safe to run against a DB that
 * may already have some of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'inquiries', ['locked_at'], 'inquiries_locked_at_index');
            $this->addIndexIfMissing($table, 'inquiries', ['status', 'visibility'], 'inquiries_status_visibility_index');
            $this->addIndexIfMissing($table, 'inquiries', ['inquiry_type', 'status'], 'inquiries_type_status_index');
        });

        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'matchmaking_logs', ['converter_id'], 'matchmaking_logs_converter_id_index');
            $this->addIndexIfMissing($table, 'matchmaking_logs', ['machine_dealer_id'], 'matchmaking_logs_machine_dealer_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'inquiries_locked_at_index');
            $this->dropIndexIfExists($table, 'inquiries_status_visibility_index');
            $this->dropIndexIfExists($table, 'inquiries_type_status_index');
        });

        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'matchmaking_logs_converter_id_index');
            $this->dropIndexIfExists($table, 'matchmaking_logs_machine_dealer_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        // MySQL/MariaDB: ask the server directly.
        if ($connection->getDriverName() === 'mysql') {
            return count($connection->select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            )) > 0;
        }

        // Other drivers (e.g. sqlite test DB) are migrated fresh, so indexes
        // never pre-exist — just attempt to create them.
        return false;
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            $table->index($columns, $indexName);
        }
    }

    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        if ($this->indexExists($table->getTable(), $indexName)) {
            $table->dropIndex($indexName);
        }
    }
};
