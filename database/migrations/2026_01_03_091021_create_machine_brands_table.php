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
        Schema::create('machine_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('machine_category')->nullable(); // printing, die_cutting, corrugation, etc
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('machine_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_brands');
    }
};
