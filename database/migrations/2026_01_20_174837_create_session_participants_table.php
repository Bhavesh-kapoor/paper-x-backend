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
        // Skip if table already exists
        if (Schema::hasTable('session_participants')) {
            return;
        }

        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('matching_sessions')->cascadeOnDelete();
            
            // Participant can be Brand/Converter (poster) or Dealer (responder)
            $table->morphs('participant'); // participant_type, participant_id
            
            $table->enum('role', ['poster', 'responder'])->default('responder');
            
            // Visibility permissions
            $table->boolean('can_see_full_specs')->default(false);
            $table->boolean('can_see_exact_location')->default(false);
            $table->boolean('can_see_brand_identity')->default(false);
            $table->boolean('can_chat')->default(false);
            
            // Participation status
            $table->enum('status', ['invited', 'active', 'declined', 'removed'])->default('invited');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            
            // Selection tracking
            $table->boolean('is_selected')->default(false);
            $table->timestamp('selected_at')->nullable();
            
            $table->timestamps();
            
            // Use custom name to avoid MySQL 64-character limit
            $table->unique(['session_id', 'participant_type', 'participant_id'], 'sess_part_unique');
            $table->index(['session_id', 'role']);
            $table->index(['session_id', 'is_selected']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
