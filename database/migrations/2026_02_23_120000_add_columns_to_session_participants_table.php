<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * session_participants was created with only id + timestamps.
     * Add columns required by SessionParticipant model and MatchingSession scope.
     */
    public function up(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('session_participants', 'session_id')) {
                $table->foreignId('session_id')->after('id')->constrained('matching_sessions')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('session_participants', 'participant_type')) {
                $table->string('participant_type')->nullable()->after('session_id'); // dealer, converter, etc.
            }
            if (!Schema::hasColumn('session_participants', 'participant_id')) {
                $table->unsignedBigInteger('participant_id')->nullable()->after('participant_type');
            }
            if (!Schema::hasColumn('session_participants', 'role')) {
                $table->string('role')->nullable()->after('participant_id'); // poster, responder
            }
            if (!Schema::hasColumn('session_participants', 'can_see_full_specs')) {
                $table->boolean('can_see_full_specs')->default(false)->after('role');
            }
            if (!Schema::hasColumn('session_participants', 'can_see_exact_location')) {
                $table->boolean('can_see_exact_location')->default(false)->after('can_see_full_specs');
            }
            if (!Schema::hasColumn('session_participants', 'can_see_brand_identity')) {
                $table->boolean('can_see_brand_identity')->default(false)->after('can_see_exact_location');
            }
            if (!Schema::hasColumn('session_participants', 'can_chat')) {
                $table->boolean('can_chat')->default(false)->after('can_see_brand_identity');
            }
            if (!Schema::hasColumn('session_participants', 'status')) {
                $table->string('status')->nullable()->after('can_chat'); // active, etc.
            }
            if (!Schema::hasColumn('session_participants', 'joined_at')) {
                $table->timestamp('joined_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('session_participants', 'left_at')) {
                $table->timestamp('left_at')->nullable()->after('joined_at');
            }
            if (!Schema::hasColumn('session_participants', 'is_selected')) {
                $table->boolean('is_selected')->default(false)->after('left_at');
            }
            if (!Schema::hasColumn('session_participants', 'selected_at')) {
                $table->timestamp('selected_at')->nullable()->after('is_selected');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            if (Schema::hasColumn('session_participants', 'session_id')) {
                $table->dropForeign(['session_id']);
            }
            $columns = [
                'participant_type', 'participant_id', 'role',
                'can_see_full_specs', 'can_see_exact_location', 'can_see_brand_identity',
                'can_chat', 'status', 'joined_at', 'left_at', 'is_selected', 'selected_at',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('session_participants', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('session_participants', 'session_id')) {
                $table->dropColumn('session_id');
            }
        });
    }
};
