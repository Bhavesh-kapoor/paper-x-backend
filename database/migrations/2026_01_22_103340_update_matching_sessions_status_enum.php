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
        // Update matching_sessions.status enum to include all SessionStatus values
        // MySQL doesn't support direct enum modification, so we use raw SQL
        DB::statement("ALTER TABLE `matching_sessions` MODIFY COLUMN `status` ENUM(
            'DRAFT',
            'POSTED',
            'MATCHING',
            'RESPONSES_RECEIVED',
            'LOCKED',
            'CHAT_ACTIVE',
            'DEAL_SUCCESS',
            'DEAL_FAILED',
            'EXPIRED',
            'REPUBLISHED',
            'ACTIVE',
            'DEAL_WON',
            'DEAL_LOST',
            'CANCELLED'
        ) NOT NULL DEFAULT 'ACTIVE'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE `matching_sessions` MODIFY COLUMN `status` ENUM(
            'ACTIVE',
            'DEAL_WON',
            'DEAL_LOST',
            'EXPIRED',
            'CANCELLED'
        ) NOT NULL DEFAULT 'ACTIVE'");
    }
};
