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
        Schema::create('dealer_material_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('agent_type', ['AUTHORIZED_AGENT', 'DEALER'])->nullable(); // Only if brand_id is set
            $table->json('finish_ids')->nullable(); // Array of finish/grade IDs
            $table->json('thickness_ranges')->nullable(); // [{"unit": "GSM", "min": 200, "max": 400}, {"unit": "MM", "min": 0.5, "max": 2.0}]
            $table->timestamps();

            $table->unique(['dealer_id', 'material_id', 'brand_id'], 'dealer_mat_brand_unique');
            $table->index('dealer_id');
            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_material_details');
    }
};
