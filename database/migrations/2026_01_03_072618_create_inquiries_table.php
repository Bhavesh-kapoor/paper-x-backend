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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['DRAFT', 'MATCHING', 'SESSION_LOCKED', 'DEAL_WON', 'DEAL_LOST', 'SESSION_EXPIRED', 'BRAND_CANCELLED'])->default('DRAFT');
            $table->enum('urgency', ['normal', 'urgent'])->default('normal');
            $table->decimal('quantity', 15, 2);
            $table->string('quantity_unit'); // kg, tons, pieces, etc
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location')->nullable();
            $table->json('specs')->nullable(); // additional specifications
            $table->timestamp('deadline')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
