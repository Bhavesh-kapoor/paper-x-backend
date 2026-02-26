<?php

namespace Tests\Feature\MatchEngine;

use App\Domain\MatchEngine\MatchEngine;
use App\Models\Dealer;
use App\Models\DealerLocation;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MatchEngineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_for_user_ranks_by_score_and_excludes_poor_candidates(): void
    {
        Config::set('matchmaking.use_location_in_matching', true);
        Config::set('matchmaking.normal_radius_km', 50);
        Config::set('matchmaking.auto_expiry_days', 2);
        Config::set('matchmaking.max_responses', 10);

        $poster = User::factory()->create(['primary_role' => 'brand']);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->create([
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'visibility' => 'dealers',
                'urgency' => 'normal',
            ]);
        InquiryItem::create([
            'inquiry_id' => $inquiry->id,
            'thickness_gsm' => 200,
            'quantity' => 100,
            'quantity_unit' => 'kg',
        ]);

        $userA = User::factory()->create(['primary_role' => 'dealer']);
        $userB = User::factory()->create(['primary_role' => 'dealer']);
        $userC = User::factory()->create(['primary_role' => 'dealer']);

        $dealerA = Dealer::create(['user_id' => $userA->id, 'status' => \App\Enums\DealerStatus::ACTIVE, 'profile_complete' => true]);
        $dealerB = Dealer::create(['user_id' => $userB->id, 'status' => \App\Enums\DealerStatus::ACTIVE, 'profile_complete' => true]);
        $dealerC = Dealer::create(['user_id' => $userC->id, 'status' => \App\Enums\DealerStatus::ACTIVE, 'profile_complete' => true]);

        DealerLocation::create(['dealer_id' => $dealerA->id, 'type' => 'warehouse', 'latitude' => 28.62, 'longitude' => 77.21]);
        DealerLocation::create(['dealer_id' => $dealerB->id, 'type' => 'warehouse', 'latitude' => 28.70, 'longitude' => 77.30]);
        DealerLocation::create(['dealer_id' => $dealerC->id, 'type' => 'warehouse', 'latitude' => 10.0, 'longitude' => 76.0]);

        $engine = app(MatchEngine::class);

        $resultA = $engine->eligibleForUser($userA);
        $resultC = $engine->eligibleForUser($userC);

        $this->assertNotEmpty($resultA, 'User A (inside radius) should see at least one inquiry');
        $scores = $resultA->pluck('final_score')->toArray();
        $sortedDesc = $resultA->pluck('final_score')->sortDesc()->values()->toArray();
        $this->assertSame($sortedDesc, $scores, 'Scores should be descending');
        $first = $resultA->first();
        $this->assertArrayHasKey('inquiry', $first);
        $this->assertArrayHasKey('final_score', $first);
        $this->assertArrayHasKey('breakdown', $first);
        $this->assertSame($inquiry->id, $first['inquiry']->id);

        $this->assertEmpty($resultC, 'User C (outside radius) should see no inquiries');
    }
}
