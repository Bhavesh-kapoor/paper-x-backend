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
        Schema::create('converter_converter_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('converter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('converter_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['converter_id', 'converter_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('converter_converter_types');
    }
};
