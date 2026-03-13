<?php

namespace Tests\Feature\Notifications;

use App\Enums\NavigationType;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTokenExist::class);
    }

    public function test_list_endpoint_supports_cursor_pagination_without_overlap(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);

        foreach (range(1, 4) as $i) {
            $service->create(
                $user->id,
                NotificationType::MATCH_FOUND,
                "Title {$i}",
                "Body {$i}",
                NavigationType::SESSION,
                (string) (100 + $i),
                [
                    'inquiry_id' => 200 + $i,
                    'material_name' => "Material {$i}",
                    'counterparty_name' => "Counterparty {$i}",
                ],
                Str::uuid()->toString()
            );
            usleep(1000);
        }

        Sanctum::actingAs($user);

        $pageOne = $this->getJson('/api/v1/notifications?limit=2');
        $pageOne->assertStatus(200);

        $pageOneIds = array_column($pageOne->json('data') ?? [], 'id');
        $nextCursor = $pageOne->json('meta.next_cursor');

        $this->assertCount(2, $pageOneIds);
        $this->assertNotEmpty($nextCursor);
        $this->assertTrue((bool) $pageOne->json('meta.has_more'));

        $pageTwo = $this->getJson('/api/v1/notifications?limit=2&cursor='.urlencode((string) $nextCursor));
        $pageTwo->assertStatus(200);
        $pageTwoIds = array_column($pageTwo->json('data') ?? [], 'id');

        $this->assertCount(2, $pageTwoIds);
        $this->assertEmpty(array_intersect($pageOneIds, $pageTwoIds));
    }

    public function test_unread_count_endpoint_returns_only_unread_rows(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::MATCH_FOUND,
            'title' => 'Unread 1',
            'body' => 'Body',
            'meta' => ['inquiry_id' => 1, 'material_name' => 'Mat', 'counterparty_name' => 'A'],
            'navigation_type' => NavigationType::SESSION,
            'navigation_id' => '111',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::MATCH_FOUND,
            'title' => 'Unread 2',
            'body' => 'Body',
            'meta' => ['inquiry_id' => 2, 'material_name' => 'Mat', 'counterparty_name' => 'B'],
            'navigation_type' => NavigationType::SESSION,
            'navigation_id' => '112',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::MATCH_FOUND,
            'title' => 'Read',
            'body' => 'Body',
            'meta' => ['inquiry_id' => 3, 'material_name' => 'Mat', 'counterparty_name' => 'C'],
            'navigation_type' => NavigationType::SESSION,
            'navigation_id' => '113',
            'read_at' => now(),
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/notifications/unread-count');
        $response->assertStatus(200);
        $this->assertSame(2, (int) $response->json('data.unread_count'));
    }

    public function test_mark_single_read_sets_read_at_and_decrements_unread_count(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::MATCH_FOUND,
            'title' => 'Unread',
            'body' => 'Body',
            'meta' => ['inquiry_id' => 10, 'material_name' => 'Mat', 'counterparty_name' => 'X'],
            'navigation_type' => NavigationType::SESSION,
            'navigation_id' => '210',
            'read_at' => null,
        ]);

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/notifications/{$notification->id}/read")->assertStatus(200);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);

        $unread = $this->getJson('/api/v1/notifications/unread-count');
        $unread->assertStatus(200);
        $this->assertSame(0, (int) $unread->json('data.unread_count'));
    }

    public function test_mark_all_read_updates_all_unread_rows_in_single_call(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 3) as $i) {
            Notification::create([
                'user_id' => $user->id,
                'type' => NotificationType::MATCH_FOUND,
                'title' => "Unread {$i}",
                'body' => 'Body',
                'meta' => ['inquiry_id' => 99 + $i, 'material_name' => 'Mat', 'counterparty_name' => 'Y'],
                'navigation_type' => NavigationType::SESSION,
                'navigation_id' => (string) (300 + $i),
                'read_at' => null,
            ]);
        }

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/notifications/read-all');
        $response->assertStatus(200);
        $this->assertSame(3, (int) $response->json('data.count'));

        $this->assertSame(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_meta_schema_rejects_invalid_payload_for_notification_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = User::factory()->create();
        $service = app(NotificationService::class);

        $service->create(
            $user->id,
            NotificationType::FIRST_RESPONSE,
            'Invalid Meta',
            'Body',
            NavigationType::CHAT_THREAD,
            'thread-1',
            ['inquiry_id' => 123]
        );
    }

    public function test_dedupe_key_returns_existing_row_instead_of_duplicate_insert(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $dedupeKey = 'first_response_thread_999';

        $first = $service->create(
            $user->id,
            NotificationType::FIRST_RESPONSE,
            'Response',
            'Body',
            NavigationType::CHAT_THREAD,
            '999',
            ['inquiry_id' => 88, 'responder_name' => 'Converter A'],
            $dedupeKey
        );

        $second = $service->create(
            $user->id,
            NotificationType::FIRST_RESPONSE,
            'Response Duplicate',
            'Body',
            NavigationType::CHAT_THREAD,
            '999',
            ['inquiry_id' => 88, 'responder_name' => 'Converter A'],
            $dedupeKey
        );

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(
            1,
            Notification::where('user_id', $user->id)->where('dedupe_key', $dedupeKey)->count()
        );
    }
}

