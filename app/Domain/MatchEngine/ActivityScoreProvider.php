<?php

namespace App\Domain\MatchEngine;

use App\Models\User;

/**
 * Pluggable activity scorer.
 *
 * Returns a normalised 0.0 – 1.0 value representing how "active" a user is.
 * Currently a placeholder (0.5). Future implementation can factor in:
 *   - response rate
 *   - acceptance rate
 *   - average reply speed
 *   - dispute count
 */
class ActivityScoreProvider
{
    public function score(User $user): float
    {
        return 0.5;
    }
}
