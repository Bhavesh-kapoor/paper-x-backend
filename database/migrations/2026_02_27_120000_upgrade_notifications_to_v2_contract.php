<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->text('body')->nullable()->after('title');
            $table->json('meta')->nullable()->after('body');
            $table->string('navigation_type', 50)->nullable()->after('meta');
            $table->string('navigation_id', 100)->nullable()->after('navigation_type');
            $table->string('dedupe_key', 150)->nullable()->after('read_at');
        });

        DB::table('notifications')->update([
            'body' => DB::raw('message'),
        ]);

        // Move type off enum to avoid runtime drift during contract evolution (MySQL/MariaDB only).
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN type VARCHAR(50) NOT NULL');
        }

        DB::table('notifications')->where('type', 'NEW_OPPORTUNITY')->update(['type' => 'MATCH_FOUND']);
        DB::table('notifications')->where('type', 'SESSION_LOCKED')->update(['type' => 'MATCH_FOUND']);
        DB::table('notifications')->where('type', 'NEW_MESSAGE')->update(['type' => 'FIRST_RESPONSE']);
        DB::table('notifications')->where('type', 'DEAL_RESULT')->update(['type' => 'OFFER_ACCEPTED']);
        DB::table('notifications')->where('type', 'SESSION_EXPIRED')->update(['type' => 'OFFER_REJECTED']);

        $idCast = Schema::getConnection()->getDriverName() === 'sqlite'
            ? 'COALESCE(CAST(notifiable_id AS TEXT), CAST(id AS TEXT))'
            : 'COALESCE(CAST(notifiable_id AS CHAR), CAST(id AS CHAR))';

        DB::table('notifications')
            ->whereNull('navigation_id')
            ->update([
                'navigation_type' => 'SESSION',
                'navigation_id' => DB::raw($idCast),
            ]);

        DB::table('notifications')
            ->whereNull('meta')
            ->update([
                'meta' => json_encode([
                    'inquiry_id' => null,
                    'material_name' => 'Requirement',
                    'counterparty_name' => 'System',
                ]),
            ]);

        $this->dropIndexIfExists('notifications', 'notifications_notifiable_type_notifiable_id_index');

        // Drop legacy indexes that reference columns we remove (SQLite requires indexes gone before DROP COLUMN).
        $this->dropIndexIfExists('notifications', 'notifications_user_id_index');
        $this->dropIndexIfExists('notifications', 'notifications_read_index');
        $this->dropIndexIfExists('notifications', 'notifications_type_index');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['message', 'notifiable_type', 'notifiable_id', 'read']);
        });

        // Rebuild indexes for cursor feed, unread filtering, and dedupe.

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at', 'id'], 'notifications_user_created_id_index');
            $table->index(['user_id', 'read_at'], 'notifications_user_read_at_index');
            $table->index('type', 'notifications_type_index');
            $table->unique(['user_id', 'dedupe_key'], 'notifications_user_dedupe_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('notifications_user_dedupe_key_unique');
            $table->dropIndex('notifications_user_created_id_index');
            $table->dropIndex('notifications_user_read_at_index');
            $table->dropIndex('notifications_type_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->text('message')->nullable()->after('title');
            $table->string('notifiable_type')->nullable()->after('message');
            $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
            $table->boolean('read')->default(false)->after('notifiable_id');
        });

        DB::table('notifications')->update([
            'message' => DB::raw('body'),
            'read' => DB::raw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END'),
        ]);

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['body', 'meta', 'navigation_type', 'navigation_id', 'dedupe_key']);
            $table->index('user_id');
            $table->index('read');
            $table->index('type');
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                DB::statement(sprintf('DROP INDEX IF EXISTS %s', $indexName));
            } else {
                DB::statement(sprintf('DROP INDEX %s ON %s', $indexName, $table));
            }
        } catch (\Throwable) {
            // Ignore missing indexes so migration remains idempotent across environments.
        }
    }
};

