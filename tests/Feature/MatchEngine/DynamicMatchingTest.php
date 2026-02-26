<?php

namespace Tests\Feature\MatchEngine;

use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Models\Dealer;
use App\Models\DealerLocation;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
use App\Models\User;
use App\Enums\DealerStatus;
use App\Enums\InquiryStatus;
use App\Enums\SessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DynamicMatchingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario: Inquiry posted first, then new dealer registers. ensureMatchesForUser
     * adds match_histories + MatchmakingLog so the new dealer sees the session.
     */
    public function test_ensure_matches_for_user_adds_new_dealer_to_existing_inquiry(): void
    {
        Config::set('matchmaking.engine_version', 'v2');
        Config::set('matchmaking.auto_match_on_login', true);
        Config::set('matchmaking.max_lazy_evaluations', 50);
        Config::set('matchmaking.use_location_in_matching', true);
        Config::set('matchmaking.normal_radius_km', 50);

        // 1. Create inquiry first (poster = dealer 1)
        $posterUser = User::factory()->create(['primary_role' => 'dealer']);
        $posterDealer = Dealer::create([
            'user_id' => $posterUser->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);
        DealerLocation::create([
            'dealer_id' => $posterDealer->id,
            'type' => 'warehouse',
            'latitude' => 28.5,
            'longitude' => 77.1,
        ]);

        $inquiry = Inquiry::create([
            'poster_id' => $posterDealer->id,
            'poster_type' => 'dealer',
            'title' => 'Existing Inquiry',
            'status' => InquiryStatus::MATCHING,
            'urgency' => 'normal',
            'visibility' => 'dealers',
            'quantity' => 100,
            'quantity_unit' => 'kg',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'locked_at' => null,
            'expires_at' => now()->addHours(24),
        ]);
        InquiryItem::create([
            'inquiry_id' => $inquiry->id,
            'thickness_gsm' => 200,
            'quantity' => 100,
            'quantity_unit' => 'kg',
        ]);
        MatchingSession::create([
            'inquiry_id' => $inquiry->id,
            'status' => SessionStatus::ACTIVE,
            'locked_at' => null,
            'expires_at' => now()->addHours(24),
            'is_visible_to_dealers' => true,
        ]);

        // 2. Register new dealer (after the inquiry exists) — no match yet
        $newUser = User::factory()->create(['primary_role' => 'dealer']);
        $newDealer = Dealer::create([
            'user_id' => $newUser->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);
        DealerLocation::create([
            'dealer_id' => $newDealer->id,
            'type' => 'warehouse',
            'latitude' => 28.62,
            'longitude' => 77.21,
        ]);

        $this->assertDatabaseMissing('match_histories', [
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $newUser->id,
        ]);
        $this->assertDatabaseMissing('matchmaking_logs', [
            'inquiry_id' => $inquiry->id,
            'dealer_id' => $newDealer->id,
        ]);

        // 3. Call ensureMatchesForUser (e.g. when user opens Sourcing Hub)
        $orchestrator = app(MatchEngineOrchestrator::class);
        $orchestrator->ensureMatchesForUser($newUser);

        // 4. Assert match_histories row exists
        $this->assertDatabaseHas('match_histories', [
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $newUser->id,
        ]);

        // 5. Assert MatchmakingLog exists (V1 compatibility)
        $this->assertDatabaseHas('matchmaking_logs', [
            'inquiry_id' => $inquiry->id,
            'dealer_id' => $newDealer->id,
            'is_visible' => true,
        ]);

        // 6. Assert user now sees session in Sourcing Hub
        $visibleSessions = MatchingSession::query()
            ->visibleToDealer($newDealer->id)
            ->where('inquiry_id', $inquiry->id)
            ->get();
        $this->assertCount(1, $visibleSessions, 'New dealer should see the session after ensureMatchesForUser');
    }

    public function test_ensure_matches_for_user_skipped_when_v1(): void
    {
        Config::set('matchmaking.engine_version', 'v1');
        Config::set('matchmaking.auto_match_on_login', true);

        $user = User::factory()->create(['primary_role' => 'dealer']);
        Dealer::create([
            'user_id' => $user->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        $orchestrator = app(MatchEngineOrchestrator::class);
        $orchestrator->ensureMatchesForUser($user);

        $this->assertDatabaseCount('match_histories', 0);
    }

    public function test_ensure_matches_for_user_skipped_when_auto_match_disabled(): void
    {
        Config::set('matchmaking.engine_version', 'v2');
        Config::set('matchmaking.auto_match_on_login', false);

        $user = User::factory()->create(['primary_role' => 'dealer']);
        Dealer::create([
            'user_id' => $user->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        $orchestrator = app(MatchEngineOrchestrator::class);
        $orchestrator->ensureMatchesForUser($user);

        $this->assertDatabaseCount('match_histories', 0);
    }
}
