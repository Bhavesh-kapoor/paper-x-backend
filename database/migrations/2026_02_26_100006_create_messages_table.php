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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('chat_threads')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sender_role', ['DEALER', 'CONVERTER', 'BRAND', 'MACHINE_DEALER']);
            $table->text('body')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['SENT', 'DELIVERED', 'READ'])->default('SENT');
            $table->timestamps();

            $table->index(['thread_id', 'id'], 'messages_thread_id_id_idx');
            $table->index(['thread_id', 'created_at'], 'messages_thread_created_at_idx');
            $table->index('sender_user_id', 'messages_sender_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
