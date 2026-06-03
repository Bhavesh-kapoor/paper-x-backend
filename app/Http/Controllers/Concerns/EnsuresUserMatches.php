<?php

namespace App\Http\Controllers\Concerns;

use App\Jobs\EnsureUserMatchesJob;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Triggers lazy/retroactive matchmaking for the current user so that users who
 * registered after an inquiry was posted (or who ranked below the post-time
 * top-N cap) still see the inquiries they qualify for.
 *
 * Performance: ensureMatchesForUser() can run up to matchmaking.max_lazy_evaluations
 * (50) inquiry evaluations and hundreds of DB queries, so it must NOT run inline on
 * the request. This trait:
 *   1. Debounces — runs at most once per user per $lazyMatchTtl seconds.
 *   2. Defers — EnsureUserMatchesJob runs after the HTTP response is flushed
 *      (dispatchAfterResponse), so the user never waits for it. No queue worker
 *      or Redis required.
 *
 * MatchEngineOrchestrator::ensureMatchesForUser() is self-guarded (no-ops unless
 * engine_version === 'v2' and auto_match_on_login is enabled).
 */
trait EnsuresUserMatches
{
    /** How long to skip re-running lazy matching for the same user (seconds). */
    protected int $lazyMatchTtl = 600; // 10 minutes

    protected function ensureUserMatches(?User $user): void
    {
        if ($user === null) {
            return;
        }

        // Debounce: Cache::add() returns false if the marker is still present,
        // so the heavy path runs at most once per user per TTL window.
        if (!Cache::add('lazy_match:' . $user->id, true, $this->lazyMatchTtl)) {
            return;
        }

        // Defer until after the response is sent so the request returns immediately.
        EnsureUserMatchesJob::dispatchAfterResponse($user->id);
    }
}
