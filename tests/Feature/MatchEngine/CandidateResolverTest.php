<?php

namespace Tests\Feature\MatchEngine;

use App\Domain\MatchEngine\CandidateResolver;
use App\Enums\InquiryStatus;
use App\Models\Dealer;
use App\Models\DealerLocation;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CandidateResolverTest extends TestCase
{
    use RefreshDatabase;

    private CandidateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('matchmaking.use_location_in_matching', false);
        $this->resolver = app(CandidateResolver::class);
    }

    public function test_expired_inquiry_returns_no_candidates(): void
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->create([
                'status' => InquiryStatus::POSTED,
                'expires_at' => now()->subHour(),
            ]);
        $candidates = $this->resolver->resolve($inquiry);
        $this->assertCount(0, $candidates);
    }

    public function test_locked_inquiry_returns_no_candidates(): void
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->locked()
            ->create();
        $candidates = $this->resolver->resolve($inquiry);
        $this->assertCount(0, $candidates);
    }

    public function test_poster_not_in_candidate_list(): void
    {
        Config::set('matchmaking.use_location_in_matching', false);
        $poster = User::factory()->create(['primary_role' => 'dealer']);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->visibilityDealers()
            ->create();
        $dealer = Dealer::create(['user_id' => $poster->id, 'status' => 'ACTIVE', 'profile_complete' => true]);
        DealerLocation::create([
            'dealer_id' => $dealer->id,
            'type' => 'warehouse',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);
        $candidates = $this->resolver->resolve($inquiry);
        $ids = $candidates->pluck('id')->toArray();
        $this->assertNotContains($poster->id, $ids);
    }

    public function test_visibility_dealers_returns_only_dealer_role(): void
    {
        Config::set('matchmaking.use_location_in_matching', false);
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $dealerUser = User::factory()->create(['primary_role' => 'dealer']);
        $converterUser = User::factory()->create(['primary_role' => 'converter']);
        Dealer::create(['user_id' => $dealerUser->id, 'status' => 'ACTIVE', 'profile_complete' => true]);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->visibilityDealers()
            ->create();
        $candidates = $this->resolver->resolve($inquiry);
        $roles = $candidates->pluck('primary_role')->unique()->values()->toArray();
        $this->assertContains('dealer', $roles);
        $this->assertNotContains('converter', $roles);
    }

    public function test_radius_filter_excludes_user_outside_radius(): void
    {
        Config::set('matchmaking.use_location_in_matching', true);
        Config::set('matchmaking.normal_radius_km', 50);
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->visibilityDealers()
            ->create([
                'latitude' => 28.6139,
                'longitude' => 77.2090,
            ]);
        $dealerInside = User::factory()->create(['primary_role' => 'dealer']);
        $dealerOutside = User::factory()->create(['primary_role' => 'dealer']);
        $insideDealer = Dealer::create(['user_id' => $dealerInside->id, 'status' => 'ACTIVE', 'profile_complete' => true]);
        $outsideDealer = Dealer::create(['user_id' => $dealerOutside->id, 'status' => 'ACTIVE', 'profile_complete' => true]);
        DealerLocation::create([
            'dealer_id' => $insideDealer->id,
            'type' => 'warehouse',
            'latitude' => 28.65,
            'longitude' => 77.25,
        ]);
        DealerLocation::create([
            'dealer_id' => $outsideDealer->id,
            'type' => 'warehouse',
            'latitude' => 10.0,
            'longitude' => 76.0,
        ]);
        $candidates = $this->resolver->resolve($inquiry);
        $ids = $candidates->pluck('id')->toArray();
        $this->assertContains($dealerInside->id, $ids);
        $this->assertNotContains($dealerOutside->id, $ids);
    }
}
