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
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('matching_sessions')->cascadeOnDelete();
            
            // Thread metadata
            $table->string('thread_name')->nullable();
            $table->enum('thread_type', ['one_to_one', 'one_to_few'])->default('one_to_one');
            
            // Thread status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_read_only')->default(false);
            $table->timestamp('archived_at')->nullable();
            
            // Last activity tracking
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('last_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
