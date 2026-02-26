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
            if (!Schema::hasColumn('chat_threads', 'inquiry_id')) {
                $table->foreignId('inquiry_id')
                    ->nullable()
                    ->after('session_id')
                    ->constrained('inquiries')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('chat_threads', 'poster_user_id')) {
                $table->foreignId('poster_user_id')
                    ->nullable()
                    ->after('inquiry_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('chat_threads', 'responder_user_id')) {
                $table->foreignId('responder_user_id')
                    ->nullable()
                    ->after('poster_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('chat_threads', 'responder_role')) {
                $table->string('responder_role')
                    ->nullable()
                    ->after('responder_user_id');
            }
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->index(['poster_user_id', 'inquiry_id', 'last_message_at'], 'chat_threads_poster_inquiry_last_msg_idx');
            $table->index(['responder_user_id', 'inquiry_id'], 'chat_threads_responder_inquiry_idx');
            $table->unique(['inquiry_id', 'responder_user_id'], 'chat_threads_inquiry_responder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropUnique('chat_threads_inquiry_responder_unique');
            $table->dropIndex('chat_threads_poster_inquiry_last_msg_idx');
            $table->dropIndex('chat_threads_responder_inquiry_idx');
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'responder_role')) {
                $table->dropColumn('responder_role');
            }

            if (Schema::hasColumn('chat_threads', 'responder_user_id')) {
                $table->dropForeign(['responder_user_id']);
                $table->dropColumn('responder_user_id');
            }

            if (Schema::hasColumn('chat_threads', 'poster_user_id')) {
                $table->dropForeign(['poster_user_id']);
                $table->dropColumn('poster_user_id');
            }

            if (Schema::hasColumn('chat_threads', 'inquiry_id')) {
                $table->dropForeign(['inquiry_id']);
                $table->dropColumn('inquiry_id');
            }
        });
    }
};
