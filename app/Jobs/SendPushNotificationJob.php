<?php

namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers a push notification off the request path.
 *
 * Dispatched from NotificationService::create() for every persisted
 * notification, so any feature that creates a notification gets push delivery
 * for free. Carries only scalars/arrays so it serializes cleanly onto the
 * database queue. Transient FCM/network errors are retried by the queue; after
 * $tries the job lands in failed_jobs for inspection.
 *
 * Implements ShouldQueueAfterCommit (not ShouldQueue) so the job is never
 * handed to a worker until the surrounding DB transaction commits — several
 * dispatch sites (chat messages, matchmaking) run inside DB::transaction, and
 * a push must not fire for rolled-back work.
 */
class SendPushNotificationJob implements ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $data  Routing payload for the app (see PushNotificationService).
     */
    public function __construct(
        private readonly int $userId,
        private readonly string $title,
        private readonly string $body,
        private readonly array $data = [],
    ) {
    }

    public function handle(PushNotificationService $push): void
    {
        $push->sendToUser($this->userId, $this->title, $this->body, $this->data);
    }
}
