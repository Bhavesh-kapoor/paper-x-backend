<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 1: Support multiple recipient types (dealer, converter, machine_dealer).
     * Option A: Add nullable converter_id and machine_dealer_id; make dealer_id nullable.
     */
    public function up(): void
    {
        // 1. Drop existing foreign key on dealer_id
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });

        // 2. Make dealer_id nullable (existing rows keep dealer_id set)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE matchmaking_logs MODIFY dealer_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('matchmaking_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('dealer_id')->nullable()->change();
            });
        }

        // 3. Re-add foreign key on dealer_id (nullable)
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
        });

        // 4. Add nullable FKs for converter and machine_dealer matches
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->foreignId('converter_id')->nullable()->after('dealer_id')
                ->constrained('converters')->nullOnDelete();
            $table->foreignId('machine_dealer_id')->nullable()->after('converter_id')
                ->constrained('machine_dealers')->nullOnDelete();
        });

        // 5. Add indexes for visibility/query by role
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->index(['inquiry_id', 'converter_id']);
            $table->index(['inquiry_id', 'machine_dealer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->dropIndex(['inquiry_id', 'converter_id']);
            $table->dropIndex(['inquiry_id', 'machine_dealer_id']);
        });

        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->dropForeign(['converter_id']);
            $table->dropForeign(['machine_dealer_id']);
        });

        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE matchmaking_logs MODIFY dealer_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('matchmaking_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('dealer_id')->nullable(false)->change();
            });
        }

        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->cascadeOnDelete();
        });
    }
};
