<?php

namespace App\Domain\MatchEngine;

use App\Domain\MatchEngine\Models\MatchHistory;
use App\Models\Inquiry;
use Illuminate\Support\Collection;

class MatchPersister
{
    /**
     * Persist the top-N scored candidates into match_histories.
     *
     * @param  Collection  $scored  Items shaped: ['user' => User, 'role' => string, 'final_score' => int, 'breakdown' => array]
     */
    public function persist(Inquiry $inquiry, Collection $scored): void
    {
        $topN = $this->topNForUrgency($inquiry->urgency);

        $top = $scored
            ->sortByDesc('final_score')
            ->take($topN);

        foreach ($top as $entry) {
            $this->persistOne($inquiry, $entry);
        }
    }

    /**
     * Persist a single user's match (for lazy/dynamic matching).
     *
     * @param  array  $entry  ['user' => User, 'role' => string, 'final_score' => int, 'breakdown' => array]
     */
    public function persistOne(Inquiry $inquiry, array $entry): void
    {
        MatchHistory::updateOrCreate(
            [
                'inquiry_id'      => $inquiry->id,
                'matched_user_id' => $entry['user']->id,
            ],
            [
                'matched_role' => $entry['role'],
                'match_score'  => $entry['final_score'],
                'reason_json'  => $entry['breakdown'] ?? null,
            ],
        );
    }

    private function topNForUrgency(?string $urgency): int
    {
        $isUrgent = strtolower((string) $urgency) === 'urgent';

        return $isUrgent
            ? (int) config('matchmaking.urgent_top_n', 50)
            : (int) config('matchmaking.normal_top_n', 10);
    }
}
