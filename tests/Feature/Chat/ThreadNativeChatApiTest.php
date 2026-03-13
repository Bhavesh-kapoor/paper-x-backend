<?php

namespace Tests\Feature\Chat;

use App\Domain\MatchEngine\Models\InquiryResponse;
use App\Domain\MatchEngine\Models\MatchHistory;
use App\Enums\NavigationType;
use App\Enums\NotificationType;
use App\Enums\InquiryIntent;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\SessionStatus;
use App\Models\ChatThread;
use App\Models\Dealer;
use App\Models\Inquiry;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadNativeChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTokenExist::class);
    }

    public function test_open_endpoint_is_idempotent_for_same_inquiry_and_responder(): void
    {
        [$posterUser, $responderUser, $inquiry, $_session] = $this->seedInquiryWithEligibleResponder();

        Sanctum::actingAs($responderUser);

        $first = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/chat-threads/open"
        );
        $first->assertStatus(200);

        $second = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/chat-threads/open"
        );
        $second->assertStatus(200);

        $threadId1 = $first->json('data.thread_id');
        $threadId2 = $second->json('data.thread_id');

        $this->assertSame($threadId1, $threadId2);
        $this->assertDatabaseCount('chat_threads', 1);
        $this->assertDatabaseHas('chat_threads', [
            'id' => $threadId1,
            'inquiry_id' => $inquiry->id,
            'poster_user_id' => $posterUser->id,
            'responder_user_id' => $responderUser->id,
        ]);
    }

    public function test_open_endpoint_denies_user_without_eligibility(): void
    {
        [, $responderUser, $inquiry, $_session] = $this->seedInquiryWithEligibleResponder();
        $otherUser = User::factory()->create(['primary_role' => 'dealer']);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/chat-threads/open"
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('chat_threads', 0);
    }

    public function test_open_endpoint_handles_duplicate_open_calls_without_duplicate_threads(): void
    {
        [$posterUser, $responderUser, $inquiry, $session] = $this->seedInquiryWithEligibleResponder();

        $existing = ChatThread::create([
            'inquiry_id' => $inquiry->id,
            'session_id' => $session->id,
            'poster_user_id' => $posterUser->id,
            'responder_user_id' => $responderUser->id,
            'responder_role' => 'dealer',
            'thread_type' => 'one_to_one',
            'is_active' => true,
        ]);

        Sanctum::actingAs($responderUser);
        $response = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/chat-threads/open"
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.thread_id', $existing->id);
        $this->assertDatabaseCount('chat_threads', 1);
    }

    public function test_poster_can_list_threads_but_other_users_cannot(): void
    {
        [$posterUser, $responderUser, $inquiry, $_session] = $this->seedInquiryWithEligibleResponder();

        Sanctum::actingAs($responderUser);
        $open = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/chat-threads/open"
        );
        $open->assertStatus(200);
        $threadId = $open->json('data.thread_id');

        Sanctum::actingAs($posterUser);
        $inquiry->load('poster');
        $this->assertSame($posterUser->id, (int) ($inquiry->poster?->user_id ?? 0));
        $listOk = $this->getJson("/api/v1/inquiries/{$inquiry->id}/chat-threads");
        $listOk->assertStatus(200)
            ->assertJsonPath('data.0.thread_id', $threadId);

        $outsider = User::factory()->create(['primary_role' => 'converter']);
        Sanctum::actingAs($outsider);
        $listDenied = $this->getJson("/api/v1/inquiries/{$inquiry->id}/chat-threads");
        $listDenied->assertStatus(403);
    }

    public function test_messages_endpoint_supports_cursor_pagination_and_access_control(): void
    {
        [$posterUser, $responderUser, $inquiry, $session] = $this->seedInquiryWithEligibleResponder();
        $thread = ChatThread::create([
            'inquiry_id' => $inquiry->id,
            'session_id' => $session->id,
            'poster_user_id' => $posterUser->id,
            'responder_user_id' => $responderUser->id,
            'responder_role' => 'dealer',
            'thread_type' => 'one_to_one',
            'is_active' => true,
        ]);

        foreach (range(1, 5) as $i) {
            Message::create([
                'thread_id' => $thread->id,
                'sender_user_id' => $responderUser->id,
                'sender_role' => 'DEALER',
                'body' => 'Message ' . $i,
                'status' => 'SENT',
            ]);
        }

        Sanctum::actingAs($posterUser);
        $page1 = $this->getJson("/api/v1/chat-threads/{$thread->id}/messages?limit=2");

        $page1->assertStatus(200)
            ->assertJsonPath('meta.has_more', true);
        $this->assertCount(2, $page1->json('data'));

        $nextCursor = $page1->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        $page2 = $this->getJson("/api/v1/chat-threads/{$thread->id}/messages?limit=2&cursor={$nextCursor}");
        $page2->assertStatus(200);
        $this->assertCount(2, $page2->json('data'));

        $outsider = User::factory()->create(['primary_role' => 'brand']);
        $thread->refresh();
        $this->assertNotSame($outsider->id, (int) $thread->poster_user_id);
        $this->assertNotSame($outsider->id, (int) $thread->responder_user_id);
        Sanctum::actingAs($outsider);
        $denied = $this->getJson("/api/v1/chat-threads/{$thread->id}/messages");
        $denied->assertStatus(403);
    }

    public function test_send_message_updates_thread_metadata_correctly(): void
    {
        [$posterUser, $responderUser, $inquiry, $session] = $this->seedInquiryWithEligibleResponder();
        $thread = ChatThread::create([
            'inquiry_id' => $inquiry->id,
            'session_id' => $session->id,
            'poster_user_id' => $posterUser->id,
            'responder_user_id' => $responderUser->id,
            'responder_role' => 'dealer',
            'thread_type' => 'one_to_one',
            'is_active' => true,
        ]);

        Sanctum::actingAs($responderUser);
        $response = $this->postJson(
            "/api/v1/chat-threads/{$thread->id}/messages",
            ['body' => 'Hello structured chat']
        );

        $response->assertStatus(201);
        $messageId = $response->json('data.id');

        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'thread_id' => $thread->id,
            'sender_user_id' => $responderUser->id,
            'body' => 'Hello structured chat',
            'status' => 'SENT',
        ]);

        $thread->refresh();
        $this->assertSame((int) $messageId, (int) $thread->last_message_id);
        $this->assertNotNull($thread->last_message_at);
    }

    public function test_express_interest_creates_professional_first_message_and_notification(): void
    {
        config(['matchmaking.engine_version' => 'v1']);
        [$posterUser, $responderUser, $inquiry] = $this->seedInquiryForExpressInterestFlow();

        Sanctum::actingAs($responderUser);
        $response = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/express-interest",
            [
                'approx_price' => 1234.5,
                'description' => 'We can supply required paper with quick dispatch.',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.chat_message_created', true);

        $thread = ChatThread::query()
            ->where('inquiry_id', $inquiry->id)
            ->where('responder_user_id', $responderUser->id)
            ->first();

        $this->assertNotNull($thread);

        $message = Message::query()
            ->where('thread_id', $thread->id)
            ->where('sender_user_id', $responderUser->id)
            ->first();

        $this->assertNotNull($message);
        $this->assertStringContainsString('Approximate Price: ₹1,234.50', (string) $message->body);
        $this->assertStringContainsString('Details:', (string) $message->body);
        $this->assertStringContainsString('We can supply required paper with quick dispatch.', (string) $message->body);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $posterUser->id,
            'type' => NotificationType::FIRST_RESPONSE->value,
            'navigation_type' => NavigationType::CHAT_THREAD->value,
            'navigation_id' => (string) $thread->id,
            'dedupe_key' => sprintf('first_response_%s', $thread->id),
        ]);
    }

    public function test_express_interest_double_submit_keeps_single_message_and_single_first_response_notification(): void
    {
        config(['matchmaking.engine_version' => 'v1']);
        [$posterUser, $responderUser, $inquiry] = $this->seedInquiryForExpressInterestFlow();

        Sanctum::actingAs($responderUser);

        $first = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/express-interest",
            [
                'approx_price' => 999.0,
                'description' => 'Initial quote from responder.',
            ]
        );
        $first->assertStatus(200)
            ->assertJsonPath('data.chat_message_created', true);

        $second = $this->postJson(
            "/api/v1/inquiries/{$inquiry->id}/express-interest",
            [
                'approx_price' => 999.0,
                'description' => 'Initial quote from responder.',
            ]
        );
        $second->assertStatus(200)
            ->assertJsonPath('data.chat_message_created', false);

        $thread = ChatThread::query()
            ->where('inquiry_id', $inquiry->id)
            ->where('responder_user_id', $responderUser->id)
            ->first();

        $this->assertNotNull($thread);
        $this->assertSame(
            1,
            Message::query()
                ->where('thread_id', $thread->id)
                ->where('sender_user_id', $responderUser->id)
                ->count()
        );

        $this->assertSame(
            1,
            Notification::query()
                ->where('user_id', $posterUser->id)
                ->where('type', NotificationType::FIRST_RESPONSE->value)
                ->where('dedupe_key', sprintf('first_response_%s', $thread->id))
                ->count()
        );
    }

    /**
     * @return array{0: User, 1: User, 2: Inquiry, 3: MatchingSession}
     */
    private function seedInquiryWithEligibleResponder(): array
    {
        $posterUser = User::factory()->create([
            'primary_role' => 'dealer',
            'company_name' => 'Poster Dealer',
        ]);

        $posterDealer = Dealer::create([
            'user_id' => $posterUser->id,
            'status' => 'ACTIVE',
            'profile_complete' => true,
        ]);

        $responderUser = User::factory()->create([
            'primary_role' => 'dealer',
            'company_name' => 'Responder Dealer',
        ]);

        $inquiry = Inquiry::create([
            'poster_id' => $posterDealer->id,
            'poster_type' => 'dealer',
            'title' => 'Need coated paper',
            'description' => 'Structured chat test inquiry',
            'status' => InquiryStatus::MATCHING,
            'urgency' => 'normal',
            'inquiry_type' => InquiryType::MATERIAL,
            'intent' => InquiryIntent::BUY,
            'quantity' => 100,
            'quantity_unit' => 'kg',
            'location' => 'Delhi',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'visibility' => 'all',
            'is_visible_to_dealers' => true,
            'is_visible_to_brand' => false,
        ]);

        InquiryResponse::create([
            'inquiry_id' => $inquiry->id,
            'responder_id' => $responderUser->id,
            'responder_role' => 'dealer',
            'description' => 'I can supply this requirement.',
            'responded_at' => now(),
        ]);

        MatchHistory::create([
            'inquiry_id' => $inquiry->id,
            'matched_user_id' => $responderUser->id,
            'matched_role' => 'dealer',
            'match_score' => 90,
            'reason_json' => ['score' => 90],
        ]);

        $session = MatchingSession::create([
            'inquiry_id' => $inquiry->id,
            'status' => SessionStatus::ACTIVE,
            'locked_at' => null,
            'expires_at' => now()->addHours(24),
            'discovery_start' => now(),
            'active_session_start' => now(),
            'is_visible_to_dealers' => true,
            'is_visible_to_brand' => false,
        ]);

        return [$posterUser, $responderUser, $inquiry, $session];
    }

    /**
     * @return array{0: User, 1: User, 2: Inquiry}
     */
    private function seedInquiryForExpressInterestFlow(): array
    {
        [$posterUser, $responderUser, $inquiry, $session] = $this->seedInquiryWithEligibleResponder();

        $responderDealer = Dealer::query()->where('user_id', $responderUser->id)->first();
        if (!$responderDealer) {
            $responderDealer = Dealer::create([
                'user_id' => $responderUser->id,
                'status' => 'ACTIVE',
                'profile_complete' => true,
            ]);
        }

        MatchmakingLog::create([
            'inquiry_id' => $inquiry->id,
            'dealer_id' => $responderDealer->id,
            'session_id' => $session->id,
            'is_visible' => true,
        ]);

        return [$posterUser, $responderUser, $inquiry];
    }
}
