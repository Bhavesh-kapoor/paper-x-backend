<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtd_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('rtd_products');
            $table->foreignId('brand_id')->constrained('users');
            $table->foreignId('converter_id')->constrained('users');
            $table->unsignedInteger('quantity');
            $table->string('logo_path')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('gst_percent', 5, 2)->default(18.00);
            $table->decimal('gst_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->index();
            $table->dateTime('confirmation_deadline')->nullable();
            $table->dateTime('dispatch_deadline')->nullable();
            $table->dateTime('delivery_deadline')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('brand_id');
            $table->index('converter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtd_orders');
    }
};
