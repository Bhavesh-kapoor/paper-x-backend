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
        Schema::create('brand_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // fmcg, pharma, beauty, etc
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_types');
    }
};
