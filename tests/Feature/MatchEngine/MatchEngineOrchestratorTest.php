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

class MatchEngineOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_v2_orchestrator_creates_matchmaking_logs_and_returns_dealer_ids(): void
    {
        Config::set('matchmaking.engine_version', 'v2');
        Config::set('matchmaking.use_location_in_matching', true);
        Config::set('matchmaking.normal_radius_km', 50);

        $poster = User::factory()->create(['primary_role' => 'dealer']);
        $dealer = Dealer::create([
            'user_id' => $poster->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);
        DealerLocation::create(['dealer_id' => $dealer->id, 'type' => 'warehouse', 'latitude' => 28.5, 'longitude' => 77.1]);

        $inquiry = Inquiry::create([
            'poster_id' => $dealer->id,
            'poster_type' => 'dealer',
            'title' => 'Test Inquiry',
            'status' => InquiryStatus::MATCHING,
            'urgency' => 'normal',
            'visibility' => 'dealers',
            'quantity' => 100,
            'quantity_unit' => 'kg',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);
        InquiryItem::create([
            'inquiry_id' => $inquiry->id,
            'thickness_gsm' => 200,
            'quantity' => 100,
            'quantity_unit' => 'kg',
        ]);
        $session = MatchingSession::create([
            'inquiry_id' => $inquiry->id,
            'status' => SessionStatus::ACTIVE,
            'locked_at' => null,
            'expires_at' => now()->addHours(24),
        ]);

        $candidateUser = User::factory()->create(['primary_role' => 'dealer']);
        $candidateDealer = Dealer::create([
            'user_id' => $candidateUser->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);
        DealerLocation::create(['dealer_id' => $candidateDealer->id, 'type' => 'warehouse', 'latitude' => 28.62, 'longitude' => 77.21]);

        $orchestrator = app(MatchEngineOrchestrator::class);
        $result = $orchestrator->runMatchmaking($inquiry);

        $this->assertArrayHasKey('dealer_ids', $result);
        $this->assertArrayHasKey('converter_ids', $result);
        $this->assertContains($candidateDealer->id, $result['dealer_ids']);

        $logs = MatchmakingLog::where('inquiry_id', $inquiry->id)->get();
        $this->assertGreaterThan(0, $logs->count(), 'Should create V1-compatible MatchmakingLog entries');
        $this->assertTrue($logs->first()->is_visible);
    }

    public function test_v1_orchestrator_delegates_to_matchmaking_service(): void
    {
        Config::set('matchmaking.engine_version', 'v1');

        $poster = User::factory()->create(['primary_role' => 'dealer']);
        $dealer = Dealer::create([
            'user_id' => $poster->id,
            'status' => DealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        $inquiry = Inquiry::create([
            'poster_id' => $dealer->id,
            'poster_type' => 'dealer',
            'title' => 'Test',
            'status' => InquiryStatus::MATCHING,
            'visibility' => 'dealers',
            'urgency' => 'normal',
            'quantity' => 100,
            'quantity_unit' => 'kg',
        ]);
        InquiryItem::create(['inquiry_id' => $inquiry->id, 'quantity' => 100, 'quantity_unit' => 'kg']);
        MatchingSession::create([
            'inquiry_id' => $inquiry->id,
            'status' => SessionStatus::ACTIVE,
            'expires_at' => now()->addHours(24),
        ]);

        $orchestrator = app(MatchEngineOrchestrator::class);
        $result = $orchestrator->runMatchmaking($inquiry);

        $this->assertArrayHasKey('dealer_ids', $result);
        $this->assertArrayHasKey('converter_ids', $result);
    }
}
