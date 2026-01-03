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
        Schema::create('machine_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_dealer_id')->constrained('machine_dealers')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_brand_id')->nullable();
            $table->string('machine_type')->nullable(); // specific machine type
            $table->enum('condition', ['Brand New', 'Excellent', 'Working Condition', 'Needs Repair'])->nullable();
            $table->enum('intent', ['sell', 'buy'])->default('sell');
            $table->enum('urgency', ['normal', 'urgent'])->default('normal');
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['ACTIVE', 'SOLD', 'WITHDRAWN', 'EXPIRED'])->default('ACTIVE');
            $table->boolean('posting_fee_paid')->default(false);
            $table->decimal('posting_fee_amount', 10, 2)->nullable();
            $table->timestamps();

            $table->index('machine_dealer_id');
            $table->index('machine_id');
            $table->index('status');
            $table->index('intent');
            $table->index('urgency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_listings');
    }
};
