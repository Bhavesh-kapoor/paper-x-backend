<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtd_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('rtd_orders')->cascadeOnDelete();
            $table->foreignId('converter_id')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->string('payout_status')->default('HELD');
            $table->dateTime('released_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('converter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtd_payouts');
    }
};
