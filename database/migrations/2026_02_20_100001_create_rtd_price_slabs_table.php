<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtd_price_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('rtd_products')->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->unsignedInteger('max_qty');
            $table->decimal('price_per_unit', 10, 2);
            $table->timestamps();

            $table->index('product_id');
            $table->unique(['product_id', 'min_qty', 'max_qty'], 'rtd_slabs_product_range_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtd_price_slabs');
    }
};
