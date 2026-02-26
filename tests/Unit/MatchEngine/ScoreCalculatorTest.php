<?php

namespace Tests\Unit\MatchEngine;

use App\Domain\MatchEngine\ActivityScoreProvider;
use App\Domain\MatchEngine\ScoreCalculator;
use App\Domain\MatchEngine\SpecFilter;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ScoreCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('matchmaking.weights', [
            'spec'      => 45,
            'distance'  => 20,
            'activity'  => 15,
            'freshness' => 10,
            'capacity'  => 10,
        ]);
        Config::set('matchmaking.normal_radius_km', 50);
        Config::set('matchmaking.urgent_radius_km', 100);
        $this->calculator = new ScoreCalculator(
            new ActivityScoreProvider,
            new SpecFilter,
        );
    }

    public function test_perfect_match_scores_near_max_with_real_activity_provider(): void
    {
        $inquiry = Inquiry::factory()->create(['urgency' => 'normal']);
        $user = User::factory()->create();
        $specResult = [
            'status' => SpecFilter::STATUS_PASS,
            'details' => [
                'gsm' => ['status' => SpecFilter::STATUS_PASS, 'similarity' => 1.0],
                'thickness_mm' => ['status' => SpecFilter::STATUS_PASS, 'similarity' => 1.0],
                'sheet_width' => ['status' => 'SKIPPED'],
                'sheet_length' => ['status' => 'SKIPPED'],
                'reel_width' => ['status' => 'SKIPPED'],
                'quantity' => ['status' => SpecFilter::STATUS_PASS, 'similarity' => 1.0],
            ],
        ];
        $result = $this->calculator->score($inquiry, $user, $specResult, 0.0);
        $this->assertGreaterThanOrEqual(92, $result['final_score']);
        $this->assertLessThanOrEqual(93, $result['final_score']);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('spec_score', $result['breakdown']);
        $this->assertArrayHasKey('distance_score', $result['breakdown']);
        $this->assertArrayHasKey('activity_score', $result['breakdown']);
    }

    public function test_distance_at_radius_limit_gives_zero_distance_score(): void
    {
        Config::set('matchmaking.normal_radius_km', 50);
        $inquiry = Inquiry::factory()->create(['urgency' => 'normal']);
        $user = User::factory()->create();
        $specResult = [
            'status' => SpecFilter::STATUS_PASS,
            'details' => [
                'gsm' => ['status' => 'SKIPPED'],
                'thickness_mm' => ['status' => 'SKIPPED'],
                'sheet_width' => ['status' => 'SKIPPED'],
                'sheet_length' => ['status' => 'SKIPPED'],
                'reel_width' => ['status' => 'SKIPPED'],
                'quantity' => ['status' => 'SKIPPED'],
            ],
        ];
        $result = $this->calculator->score($inquiry, $user, $specResult, 50.0);
        $this->assertSame(0.0, $result['breakdown']['distance_score']);
        $this->assertSame(50.0, $result['breakdown']['distance_km']);
    }

    public function test_activity_score_reflects_half_weight_with_real_provider(): void
    {
        $inquiry = Inquiry::factory()->create();
        $user = User::factory()->create();
        $specResult = [
            'status' => SpecFilter::STATUS_PASS,
            'details' => [
                'gsm' => ['status' => 'SKIPPED'],
                'thickness_mm' => ['status' => 'SKIPPED'],
                'sheet_width' => ['status' => 'SKIPPED'],
                'sheet_length' => ['status' => 'SKIPPED'],
                'reel_width' => ['status' => 'SKIPPED'],
                'quantity' => ['status' => 'SKIPPED'],
            ],
        ];
        $result = $this->calculator->score($inquiry, $user, $specResult, null);
        $this->assertSame(7.5, $result['breakdown']['activity_score']);
    }

    public function test_weights_sum_to_100(): void
    {
        $weights = config('matchmaking.weights', [
            'spec'      => 45,
            'distance'  => 20,
            'activity'  => 15,
            'freshness' => 10,
            'capacity'  => 10,
        ]);
        $this->assertSame(100, (int) array_sum($weights));
    }

    public function test_final_score_is_between_0_and_100(): void
    {
        $inquiry = Inquiry::factory()->create();
        $user = User::factory()->create();
        $specResult = [
            'status' => SpecFilter::STATUS_HARD_FAIL,
            'details' => [
                'gsm' => ['status' => SpecFilter::STATUS_HARD_FAIL, 'similarity' => 0.0],
                'thickness_mm' => ['status' => 'SKIPPED'],
                'sheet_width' => ['status' => 'SKIPPED'],
                'sheet_length' => ['status' => 'SKIPPED'],
                'reel_width' => ['status' => 'SKIPPED'],
                'quantity' => ['status' => 'SKIPPED'],
            ],
        ];
        $result = $this->calculator->score($inquiry, $user, $specResult, 50.0);
        $this->assertGreaterThanOrEqual(0, $result['final_score']);
        $this->assertLessThanOrEqual(100, $result['final_score']);
    }
}
