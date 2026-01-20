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
        Schema::create('matchmaking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('matching_sessions')->nullOnDelete();
            
            // Matchmaking criteria
            $table->boolean('material_match')->default(false);
            $table->boolean('finish_match')->default(false);
            $table->boolean('thickness_match')->default(false);
            $table->boolean('location_match')->default(false);
            
            // Tolerance applied
            $table->decimal('thickness_tolerance_percent', 5, 2)->nullable();
            $table->decimal('thickness_tolerance_absolute', 8, 3)->nullable();
            
            // Priority scoring
            $table->integer('priority_score')->default(0);
            $table->json('score_breakdown')->nullable(); // Detailed scoring breakdown
            
            // Visibility tracking
            $table->timestamp('visible_to_dealer_at')->nullable();
            $table->timestamp('hidden_from_dealer_at')->nullable();
            $table->boolean('is_visible')->default(false);
            
            // Response tracking
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('response_id')->nullable()->constrained('responses')->nullOnDelete();
            
            // Selection tracking
            $table->boolean('is_selected')->default(false);
            $table->timestamp('selected_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['inquiry_id', 'dealer_id']);
            $table->index(['inquiry_id', 'is_visible']);
            $table->index(['session_id', 'is_selected']);
            $table->index('priority_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_logs');
    }
};
