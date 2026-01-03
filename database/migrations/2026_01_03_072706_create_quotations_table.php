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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('matching_sessions')->cascadeOnDelete();
            $table->decimal('quoted_price', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->integer('delivery_days');
            $table->text('notes')->nullable();
            $table->enum('deal_status', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING');
            $table->timestamps();

            $table->unique(['dealer_id', 'inquiry_id']);
            $table->index('session_id');
            $table->index('deal_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
