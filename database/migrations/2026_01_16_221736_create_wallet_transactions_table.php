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
        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
                $table->string('transaction_id')->unique(); // TXN-{id} format
                $table->enum('type', ['ADDED', 'DEDUCTED']); // ADDED or DEDUCTED
                $table->decimal('amount', 15, 2); // Credit amount (positive for added, negative for deducted)
                $table->decimal('balance_after', 15, 2); // Balance after transaction
                $table->string('description'); // e.g., "Credits Added", "Requirement Posted", "Deal Closed"
                $table->enum('transaction_type', [
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
                ])->default('OTHER');
                $table->string('reference_id')->nullable(); // Reference to inquiry, session, etc.
                $table->string('reference_type')->nullable(); // inquiry, session, etc.
                $table->json('metadata')->nullable(); // Additional data
                $table->timestamps();

                $table->index('wallet_id');
                $table->index('type');
                $table->index('transaction_type');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
