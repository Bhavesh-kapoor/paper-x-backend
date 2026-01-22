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
        Schema::create('inquiry_finishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('finish_id')->constrained('material_finishes')->cascadeOnDelete();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate finish entries for same inquiry
            $table->unique(['inquiry_id', 'finish_id'], 'unique_inquiry_finish');
            
            // Indexes for better query performance
            $table->index('inquiry_id');
            $table->index('finish_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_finishes');
    }
};
