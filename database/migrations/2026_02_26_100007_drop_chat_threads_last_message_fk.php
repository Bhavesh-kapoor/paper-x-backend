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
        Schema::table('chat_threads', function (Blueprint $table) {
            try {
                $table->dropForeign(['last_message_id']);
            } catch (\Throwable $e) {
                // Foreign key may already be absent in some environments.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            try {
                $table->foreign('last_message_id')->references('id')->on('chat_messages')->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key cannot be restored due schema drift.
            }
        });
    }
};
