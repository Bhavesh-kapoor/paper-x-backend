<?php

namespace App\Jobs;

use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Runs lazy/retroactive matchmaking for a user off the request path.
 *
 * Dispatched via dispatchAfterResponse() from EnsuresUserMatches so the heavy
 * work (up to matchmaking.max_lazy_evaluations inquiry evaluations) runs after
 * the HTTP response is flushed — no queue worker or Redis required.
 *
 * Takes only a scalar userId so it is trivially serializable and re-fetches the
 * User inside handle().
 */
class EnsureUserMatchesJob
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly int $userId,
    ) {
    }

    public function handle(MatchEngineOrchestrator $orchestrator): void
    {
        try {
            $user = User::find($this->userId);
            if ($user !== null) {
                $orchestrator->ensureMatchesForUser($user);
            }
        } catch (\Throwable $e) {
            Log::warning('Lazy matchmaking job failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
