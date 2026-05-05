<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallet_payment_orders')) {
            Schema::create('wallet_payment_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('credit_pack_id')->constrained('credit_packs');
                $table->string('razorpay_order_id')->unique();
                $table->string('razorpay_payment_id')->nullable()->index();
                $table->string('receipt')->index();
                $table->unsignedBigInteger('amount_paise');
                $table->string('currency', 3)->default('INR');
                $table->unsignedInteger('credits');
                $table->enum('status', ['created', 'paid', 'failed', 'expired'])->default('created');
                $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions');
                $table->json('metadata')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_payment_orders');
    }
};
