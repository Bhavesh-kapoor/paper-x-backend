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
        Schema::create('material_finishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name'); // Uncoated, Coated, Gloss, Matt, etc.
            $table->string('type')->nullable(); // finish, coating, grade, variant
            $table->timestamps();

            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_finishes');
    }
};
