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
        // Add visibility fields to inquiries table
        Schema::table('inquiries', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable()->after('status');
            $table->timestamp('matching_started_at')->nullable()->after('posted_at');
            $table->timestamp('responses_received_at')->nullable()->after('matching_started_at');
            $table->timestamp('locked_at')->nullable()->after('responses_received_at');
            $table->timestamp('expires_at')->nullable()->after('locked_at');
            $table->timestamp('cooldown_until')->nullable()->after('expires_at');
            
            // Visibility flags
            $table->boolean('is_visible_to_dealers')->default(false)->after('cooldown_until');
            $table->boolean('is_visible_to_brand')->default(true)->after('is_visible_to_dealers');
            $table->boolean('hide_brand_identity')->default(true)->after('is_visible_to_brand');
            $table->boolean('hide_exact_location')->default(true)->after('hide_brand_identity');
            
            // Republish tracking
            $table->integer('republish_count')->default(0)->after('hide_exact_location');
            $table->timestamp('last_republished_at')->nullable()->after('republish_count');
            
            // Matchmaking metadata
            $table->integer('matched_dealers_count')->default(0)->after('last_republished_at');
            $table->integer('responses_count')->default(0)->after('matched_dealers_count');
            $table->integer('selected_dealers_count')->default(0)->after('responses_count');
            
            $table->index('is_visible_to_dealers');
            $table->index('expires_at');
            $table->index('cooldown_until');
        });
        
        // Add visibility fields to matching_sessions table
        Schema::table('matching_sessions', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable()->after('status');
            $table->timestamp('matching_started_at')->nullable()->after('posted_at');
            $table->timestamp('responses_received_at')->nullable()->after('matching_started_at');
            $table->timestamp('chat_opened_at')->nullable()->after('locked_at');
            $table->timestamp('cooldown_until')->nullable()->after('republish_cooldown_until');
            
            // Visibility flags
            $table->boolean('is_visible_to_dealers')->default(false)->after('cooldown_until');
            $table->boolean('is_visible_to_brand')->default(true)->after('is_visible_to_dealers');
            $table->boolean('chat_enabled')->default(false)->after('is_visible_to_brand');
            $table->boolean('full_specs_visible')->default(false)->after('chat_enabled');
            $table->boolean('brand_identity_visible')->default(false)->after('full_specs_visible');
            
            // Participant tracking
            $table->integer('total_participants')->default(0)->after('brand_identity_visible');
            $table->integer('selected_participants_count')->default(0)->after('total_participants');
            
            $table->index('is_visible_to_dealers');
            $table->index('chat_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'posted_at',
                'matching_started_at',
                'responses_received_at',
                'locked_at',
                'expires_at',
                'cooldown_until',
                'is_visible_to_dealers',
                'is_visible_to_brand',
                'hide_brand_identity',
                'hide_exact_location',
                'republish_count',
                'last_republished_at',
                'matched_dealers_count',
                'responses_count',
                'selected_dealers_count',
            ]);
        });
        
        Schema::table('matching_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'posted_at',
                'matching_started_at',
                'responses_received_at',
                'chat_opened_at',
                'cooldown_until',
                'is_visible_to_dealers',
                'is_visible_to_brand',
                'chat_enabled',
                'full_specs_visible',
                'brand_identity_visible',
                'total_participants',
                'selected_participants_count',
            ]);
        });
    }
};
