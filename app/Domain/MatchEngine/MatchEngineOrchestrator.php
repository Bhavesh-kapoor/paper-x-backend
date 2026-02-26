<?php

namespace App\Domain\MatchEngine;

use App\Domain\MatchEngine\Models\MatchHistory;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\MatchmakingLog;
use App\Models\User;
use App\Services\MatchmakingService;
use Illuminate\Support\Collection;

/**
 * Bridge between V1 (MatchmakingService) and V2 (MatchEngine pipeline).
 *
 * When engine_version === 'v2':
 *   - Runs CandidateResolver → SpecFilter → ScoreCalculator
 *   - Persists to match_histories via MatchPersister
 *   - Creates V1-compatible MatchmakingLog entries (dealer_id, converter_id, etc.)
 *   - Returns ['dealer_ids' => [...], 'converter_ids' => [...]]
 *
 * When engine_version === 'v1':
 *   - Delegates to MatchmakingService::findMatchingDealers / findMatchingConverters
 */
class MatchEngineOrchestrator
{
    public function __construct(
        private readonly MatchEngine $matchEngine,
        private readonly CandidateResolver $candidateResolver,
        private readonly MatchPersister $matchPersister,
        private readonly MatchmakingService $matchmakingService,
    ) {}

    /**
     * Run matchmaking for an inquiry.
     *
     * @return array{dealer_ids: int[], converter_ids: int[]}
     */
    public function runMatchmaking(Inquiry $inquiry): array
    {
        if (config('matchmaking.engine_version') === 'v2') {
            return $this->runV2($inquiry);
        }

        return $this->runV1($inquiry);
    }

    /**
     * Run matchmaking for dealers only (V1 interface compatibility).
     *
     * @return int[] Dealer IDs
     */
    public function runMatchmakingForDealers(Inquiry $inquiry, int $maxDealers = 10): array
    {
        if (config('matchmaking.engine_version') === 'v2') {
            $result = $this->runV2($inquiry);
            return $result['dealer_ids'];
        }

        return $this->matchmakingService->findMatchingDealers($inquiry, $maxDealers);
    }

    /**
     * Run matchmaking for converters only (V1 interface compatibility).
     *
     * @return int[] Converter IDs
     */
    public function runMatchmakingForConverters(Inquiry $inquiry, int $maxConverters = 10): array
    {
        if (config('matchmaking.engine_version') === 'v2') {
            $result = $this->runV2($inquiry);
            return $result['converter_ids'];
        }

        return $this->matchmakingService->findMatchingConverters($inquiry, $maxConverters);
    }

    /**
     * Lazy matching for V2: ensure the user is matched to all active inquiries they qualify for,
     * even if the inquiry was posted before they registered. Call before listing sessions (e.g. Sourcing Hub).
     */
    public function ensureMatchesForUser(User $user): void
    {
        if (config('matchmaking.engine_version') !== 'v2') {
            return;
        }

        if (!config('matchmaking.auto_match_on_login', true)) {
            return;
        }

        $maxEvaluations = (int) config('matchmaking.max_lazy_evaluations', 50);
        if ($maxEvaluations <= 0) {
            return;
        }

        $alreadyMatchedInquiryIds = MatchHistory::where('matched_user_id', $user->id)
            ->pluck('inquiry_id')
            ->toArray();

        $inquiries = Inquiry::query()
            ->whereIn('status', [InquiryStatus::POSTED, InquiryStatus::MATCHING])
            ->whereNull('locked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereNotIn('id', $alreadyMatchedInquiryIds)
            ->where(function ($q) use ($user) {
                $this->excludeOwnInquiries($q, $user);
            })
            ->limit($maxEvaluations)
            ->get();

        foreach ($inquiries as $inquiry) {
            $inquiry->load(['items', 'materials', 'session']);

            $effectiveVisibility = $inquiry->visibility ?? ($inquiry->poster_type === 'brand' ? 'converters' : 'dealers');
            $originalVisibility = $inquiry->visibility;
            $inquiry->visibility = $effectiveVisibility;

            try {
                $result = $this->matchEngine->evaluateForUser($inquiry, $user);
                if ($result !== null) {
                    $this->matchPersister->persistOne($inquiry, $result);
                    $this->createCompatibilityLogForEntry($inquiry, $result);
                }
            } finally {
                $inquiry->visibility = $originalVisibility;
            }
        }
    }

    private function excludeOwnInquiries($query, User $user): void
    {
        $query->where(function ($q) use ($user) {
            if ($user->dealer) {
                $q->whereNot(function ($sub) use ($user) {
                    $sub->where('poster_type', 'dealer')->where('poster_id', $user->dealer->id);
                });
            }
            if ($user->converter) {
                $q->whereNot(function ($sub) use ($user) {
                    $sub->where('poster_type', 'converter')->where('poster_id', $user->converter->id);
                });
            }
            if ($user->brand) {
                $q->whereNot(function ($sub) use ($user) {
                    $sub->where('poster_type', 'brand')->where('poster_id', $user->brand->id);
                });
            }
            if ($user->machineDealer) {
                $q->whereNot(function ($sub) use ($user) {
                    $sub->where('poster_type', 'machine_dealer')->where('poster_id', $user->machineDealer->id);
                });
            }
        });
    }

    private function createCompatibilityLogForEntry(Inquiry $inquiry, array $entry): void
    {
        $user = $entry['user'];
        $role = $this->normalizeRole((string) ($entry['role'] ?? ''));
        $breakdown = $entry['breakdown'] ?? [];

        $logData = [
            'session_id' => $inquiry->session?->id,
            'is_visible' => true,
            'visible_to_dealer_at' => now(),
            'material_match' => $breakdown['material_match'] ?? false,
            'finish_match' => $breakdown['finish_match'] ?? false,
            'thickness_match' => $breakdown['thickness_match'] ?? false,
            'location_match' => $breakdown['location_match'] ?? false,
            'priority_score' => $entry['final_score'],
            'score_breakdown' => $breakdown,
        ];

        $key = match ($role) {
            'dealer' => ['inquiry_id' => $inquiry->id, 'dealer_id' => $user->dealer?->id],
            'converter' => ['inquiry_id' => $inquiry->id, 'converter_id' => $user->converter?->id],
            'machine_dealer' => ['inquiry_id' => $inquiry->id, 'machine_dealer_id' => $user->machineDealer?->id],
            default => null,
        };

        if ($key === null) {
            return;
        }

        if (collect($key)->filter()->count() !== 2) {
            return;
        }

        MatchmakingLog::updateOrCreate(array_filter($key), $logData);
    }

    private function runV1(Inquiry $inquiry): array
    {
        $dealerIds = [];
        $converterIds = [];

        // Brand inquiries often have no visibility; infer from poster_type
        $visibility = $inquiry->visibility ?? ($inquiry->poster_type === 'brand' ? 'converters' : 'dealers');

        if (in_array($visibility, ['dealers', 'all'], true)) {
            $dealerIds = $this->matchmakingService->findMatchingDealers($inquiry);
        }

        if (in_array($visibility, ['converters', 'all'], true)) {
            $converterIds = $this->matchmakingService->findMatchingConverters($inquiry);
        }

        return [
            'dealer_ids' => $dealerIds,
            'converter_ids' => $converterIds,
        ];
    }

    private function runV2(Inquiry $inquiry): array
    {
        $inquiry->load(['items', 'materials', 'session']);

        // Ensure visibility for CandidateResolver (brand inquiries often have null)
        $effectiveVisibility = $inquiry->visibility ?? ($inquiry->poster_type === 'brand' ? 'converters' : 'dealers');
        $originalVisibility = $inquiry->visibility;
        $inquiry->visibility = $effectiveVisibility;

        try {
            $candidates = $this->candidateResolver->resolve($inquiry);
            $scored = $this->scoreCandidates($inquiry, $candidates);

            $this->matchPersister->persist($inquiry, $scored);
            $this->createCompatibilityLogs($inquiry, $scored);

            $inquiry->update([
                'is_visible_to_dealers' => true,
                'matched_dealers_count' => $scored->count(),
                'matching_started_at' => $inquiry->matching_started_at ?? now(),
            ]);

            return $this->extractIdsByRole($scored);
        } finally {
            $inquiry->visibility = $originalVisibility;
        }
    }

    private function scoreCandidates(Inquiry $inquiry, Collection $candidates): Collection
    {
        $scored = collect();

        foreach ($candidates as $user) {
            $result = $this->matchEngine->evaluateForUser($inquiry, $user);
            if ($result !== null) {
                $scored->push($result);
            }
        }

        return $scored
            ->sortByDesc('final_score')
            ->values();
    }

    private function createCompatibilityLogs(Inquiry $inquiry, Collection $scored): void
    {
        $topN = $this->topNForUrgency($inquiry->urgency);
        $top = $scored->take($topN);

        foreach ($top as $entry) {
            $user = $entry['user'];
            $role = $this->normalizeRole((string) ($entry['role'] ?? ''));
            $breakdown = $entry['breakdown'] ?? [];

            $logData = [
                'session_id' => $inquiry->session?->id,
                'is_visible' => true,
                'visible_to_dealer_at' => now(),
                'material_match' => $breakdown['material_match'] ?? false,
                'finish_match' => $breakdown['finish_match'] ?? false,
                'thickness_match' => $breakdown['thickness_match'] ?? false,
                'location_match' => $breakdown['location_match'] ?? false,
                'priority_score' => $entry['final_score'],
                'score_breakdown' => $breakdown,
            ];

            $key = match ($role) {
                'dealer' => ['inquiry_id' => $inquiry->id, 'dealer_id' => $user->dealer?->id],
                'converter' => ['inquiry_id' => $inquiry->id, 'converter_id' => $user->converter?->id],
                'machine_dealer' => ['inquiry_id' => $inquiry->id, 'machine_dealer_id' => $user->machineDealer?->id],
                default => null,
            };

            if ($key === null) {
                continue;
            }

            // Ensure we have the role-specific ID
            $hasId = collect($key)->filter()->count() === 2;
            if (!$hasId) {
                continue;
            }

            MatchmakingLog::updateOrCreate(
                array_filter($key),
                $logData,
            );
        }
    }

    /**
     * Normalize role strings across legacy and current naming conventions.
     */
    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'machineDealer', 'machine-dealer' => 'machine_dealer',
            default => $role,
        };
    }

    private function extractIdsByRole(Collection $scored): array
    {
        $topN = (int) config('matchmaking.normal_top_n', 10);
        $top = $scored->take($topN);

        $dealerIds = [];
        $converterIds = [];

        foreach ($top as $entry) {
            $user = $entry['user'];
            $role = $entry['role'];

            if ($role === 'dealer' && $user->dealer) {
                $dealerIds[] = $user->dealer->id;
            } elseif ($role === 'converter' && $user->converter) {
                $converterIds[] = $user->converter->id;
            }
        }

        return [
            'dealer_ids' => $dealerIds,
            'converter_ids' => $converterIds,
        ];
    }

    private function topNForUrgency(?string $urgency): int
    {
        $isUrgent = strtolower((string) $urgency) === 'urgent';

        return $isUrgent
            ? (int) config('matchmaking.urgent_top_n', 50)
            : (int) config('matchmaking.normal_top_n', 10);
    }
}
