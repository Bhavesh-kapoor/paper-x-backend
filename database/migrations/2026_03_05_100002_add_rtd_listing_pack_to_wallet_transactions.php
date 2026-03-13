<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM(
            'PURCHASE',
            'REFERRAL_BONUS',
            'REQUIREMENT_POSTED',
            'DEAL_CLOSED',
            'MACHINERY_INSPECTION',
            'LISTING_FEE',
            'PREMIUM_FEATURE',
            'RTD_LISTING_PACK',
            'REFUND',
            'ADMIN_ADJUSTMENT',
            'OTHER'
        ) DEFAULT 'OTHER'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM(
            'PURCHASE',
            'REFERRAL_BONUS',
            'REQUIREMENT_POSTED',
            'DEAL_CLOSED',
            'MACHINERY_INSPECTION',
            'LISTING_FEE',
            'PREMIUM_FEATURE',
            'REFUND',
            'ADMIN_ADJUSTMENT',
            'OTHER'
        ) DEFAULT 'OTHER'");
    }
};
