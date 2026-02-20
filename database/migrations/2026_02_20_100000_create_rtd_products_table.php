<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtd_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('converter_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->string('product_name');
            $table->string('image_path')->nullable();
            $table->string('size')->nullable();
            $table->string('material')->nullable();
            $table->string('gsm')->nullable();
            $table->string('finish')->nullable();
            $table->string('branding_method')->nullable();
            $table->enum('lead_time', ['SAME_DAY', 'H24', 'H48', 'DAYS_3_5']);
            $table->unsignedInteger('moq');
            $table->unsignedInteger('max_capacity')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->boolean('buy_now_enabled')->default(true);
            $table->unsignedInteger('decline_count')->default(0);
            $table->unsignedInteger('visibility_score')->default(100);
            $table->enum('status', ['active', 'paused', 'inactive'])->default('active');
            $table->string('delivery_geography')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('converter_id');
            $table->index('status');
            $table->index('category');
            $table->index('lead_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtd_products');
    }
};
