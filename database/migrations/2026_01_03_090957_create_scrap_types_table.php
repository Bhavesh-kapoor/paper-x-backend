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
        Schema::create('scrap_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // paper_board, process, finishing, ancillary
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
        Schema::dropIfExists('scrap_types');
    }
};
