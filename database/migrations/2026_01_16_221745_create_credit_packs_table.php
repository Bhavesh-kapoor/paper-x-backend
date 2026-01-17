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
        if (!Schema::hasTable('credit_packs')) {
            Schema::create('credit_packs', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Starter, Growth, Business, Factory
                $table->string('slug')->unique();
                $table->integer('credits'); // Number of credits
                $table->decimal('price', 10, 2); // Price in INR
                $table->decimal('gst_percentage', 5, 2)->default(18); // GST percentage
                $table->text('description')->nullable();
                $table->string('validity')->default('Lifetime'); // Lifetime, 30 days, etc.
                $table->boolean('is_best_value')->default(false); // BEST VALUE tag
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('is_active');
                $table->index('sort_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_packs');
    }
};
