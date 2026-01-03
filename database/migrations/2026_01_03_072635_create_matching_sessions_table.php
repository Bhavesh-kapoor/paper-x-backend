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
        Schema::create('matching_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['ACTIVE', 'DEAL_WON', 'DEAL_LOST', 'EXPIRED', 'CANCELLED'])->default('ACTIVE');
            $table->timestamp('locked_at');
            $table->timestamp('expires_at');
            $table->foreignId('winning_dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matching_sessions');
    }
};
