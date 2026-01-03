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
        Schema::table('matching_sessions', function (Blueprint $table) {
            $table->timestamp('discovery_start')->nullable()->after('inquiry_id');
            $table->timestamp('discovery_end')->nullable()->after('discovery_start');
            $table->timestamp('active_session_start')->nullable()->after('discovery_end');
            $table->integer('republish_count')->default(0)->after('expires_at');
            $table->timestamp('republished_at')->nullable()->after('republish_count');
            $table->timestamp('republish_cooldown_until')->nullable()->after('republished_at');
            $table->boolean('is_night_mode')->default(false)->after('republish_cooldown_until');
            $table->timestamp('full_matching_starts_at')->nullable()->after('is_night_mode'); // for night mode inquiries
            
            $table->index('discovery_end');
            $table->index('republish_cooldown_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matching_sessions', function (Blueprint $table) {
            $table->dropIndex(['discovery_end']);
            $table->dropIndex(['republish_cooldown_until']);
            
            $table->dropColumn([
                'discovery_start',
                'discovery_end',
                'active_session_start',
                'republish_count',
                'republished_at',
                'republish_cooldown_until',
                'is_night_mode',
                'full_matching_starts_at',
            ]);
        });
    }
};
