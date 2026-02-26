<?php

namespace Tests\Feature\MatchEngine;

use App\Domain\MatchEngine\Models\InquiryResponse;
use App\Domain\MatchEngine\Models\MatchHistory;
use App\Domain\MatchEngine\ResponseService;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('matchmaking.response_limit', 10);
        Config::set('matchmaking.auto_expiry_days', 2);
        $this->service = app(ResponseService::class);
    }

    public function test_cannot_respond_twice(): void
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $responder = User::factory()->create(['primary_role' => 'dealer']);
        $inquiry = Inquiry::factory()->forPoster($poster)->create();
        MatchHistory::create([
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $responder->id,
            'matched_role' => 'dealer',
            'match_score' => 80,
        ]);
        $this->service->respond($inquiry, $responder, 'I can supply', 100.0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already responded');
        $this->service->respond($inquiry, $responder, 'Second response', null);
    }

    public function test_cannot_respond_to_own_inquiry(): void
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $inquiry = Inquiry::factory()->forPoster($poster)->create();
        MatchHistory::create([
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $poster->id,
            'matched_role' => 'brand',
            'match_score' => 80,
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('own');
        $this->service->respond($inquiry, $poster, 'My own response', null);
    }

    public function test_expired_inquiry_blocks_response(): void
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $responder = User::factory()->create(['primary_role' => 'dealer']);
        $inquiry = Inquiry::factory()
            ->forPoster($poster)
            ->create(['expires_at' => now()->subHour()]);
        MatchHistory::create([
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $responder->id,
            'matched_role' => 'dealer',
            'match_score' => 80,
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expired');
        $this->service->respond($inquiry, $responder, 'Response text', null);
    }

    public function test_lock_after_10_responses(): void
    {
        $limit = (int) config('matchmaking.response_limit', 10);
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $inquiry = Inquiry::factory()->forPoster($poster)->create();
        $responders = [];
        for ($i = 0; $i < $limit; $i++) {
            $user = User::factory()->create(['primary_role' => 'dealer']);
            MatchHistory::create([
                'inquiry_id' => $inquiry->id,
                'matched_user_id' => $user->id,
                'matched_role' => 'dealer',
                'match_score' => 80,
            ]);
            $responders[] = $user;
        }
        for ($i = 0; $i < $limit; $i++) {
            $this->service->respond($inquiry, $responders[$i], 'Response ' . $i, null);
        }
        $inquiry->refresh();
        $this->assertSame(InquiryStatus::LOCKED, $inquiry->status);
        $this->assertNotNull($inquiry->locked_at);
    }

    public function test_only_match_history_user_can_respond(): void
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);
        $eligibleUser = User::factory()->create(['primary_role' => 'dealer']);
        $notEligibleUser = User::factory()->create(['primary_role' => 'dealer']);
        $inquiry = Inquiry::factory()->forPoster($poster)->create();
        MatchHistory::create([
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $eligibleUser->id,
            'matched_role' => 'dealer',
            'match_score' => 80,
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not eligible');
        $this->service->respond($inquiry, $notEligibleUser, 'Response', null);
    }
}
