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
        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            
            // Item specifications
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('material_category')->nullable();
            $table->string('finish_coating')->nullable();
            
            // Thickness specifications
            $table->decimal('thickness_gsm', 10, 2)->nullable();
            $table->decimal('thickness_mm', 8, 3)->nullable();
            $table->string('thickness_unit')->default('gsm'); // gsm or mm
            
            // Tolerance settings
            $table->decimal('thickness_tolerance_percent', 5, 2)->default(5.00); // Default ±5%
            $table->decimal('thickness_tolerance_absolute', 8, 3)->default(0.2); // Default ±0.2mm
            
            // Quantity
            $table->decimal('quantity', 15, 2);
            $table->string('quantity_unit');
            
            // Additional specs
            $table->json('additional_specs')->nullable();
            
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('inquiry_id');
            $table->index(['material_id', 'material_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_items');
    }
};
