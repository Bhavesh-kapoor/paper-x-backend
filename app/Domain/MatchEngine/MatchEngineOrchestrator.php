<?php

namespace App\Domain\MatchEngine;

use App\Domain\MatchEngine\Models\MatchHistory;
use App\Enums\InquiryStatus;
use App\Enums\SessionStatus;
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
 *   - Returns ['dealer_ids' => [...], 'converter_ids' => [...], 'machine_dealer_ids' => [...]]
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
     * @return array{dealer_ids: int[], converter_ids: int[], machine_dealer_ids: int[]}
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

        // Terminal / closed session states — a session in any of these is no
        // longer open to new responders, so a newly registered user must NOT be
        // matched to (or notified about) it.
        $closedSessionStatuses = [
            SessionStatus::LOCKED->value,
            SessionStatus::CHAT_ACTIVE->value,
            SessionStatus::DEAL_SUCCESS->value,
            SessionStatus::DEAL_FAILED->value,
            SessionStatus::DEAL_WON->value,
            SessionStatus::DEAL_LOST->value,
            SessionStatus::EXPIRED->value,
            SessionStatus::CANCELLED->value,
        ];

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
            // The 24h window + lock/close state live on the MatchingSession, which
            // is the source of truth. inquiry.expires_at is usually NULL, so without
            // this gate expired/closed sessions slip through and new users get
            // notified about sessions that already ended.
            ->whereHas('session', function ($q) use ($closedSessionStatuses) {
                $q->where('expires_at', '>', now())
                    ->whereNull('locked_at')
                    ->whereNotIn('status', $closedSessionStatuses);
            })
            ->with(['items', 'materials', 'session'])
            ->limit($maxEvaluations)
            ->get();

        foreach ($inquiries as $inquiry) {
            $effectiveVisibility = $inquiry->visibility ?? ($inquiry->poster_type === 'brand' ? 'converters' : 'dealers');
            $originalVisibility = $inquiry->visibility;
            $inquiry->visibility = $effectiveVisibility;

            try {
                $result = $this->matchEngine->evaluateForUser($inquiry, $user);
                if ($result !== null) {
                    $this->matchPersister->persistOne($inquiry, $result);
                    $this->createCompatibilityLogForEntry($inquiry, $result);
                    $this->notifyLazyMatch($inquiry, $user, $this->normalizeRole((string) ($result['role'] ?? '')));
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
        $machineDealerIds = [];

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
            'machine_dealer_ids' => $machineDealerIds,
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
            // Candidates are already resolved here, so score directly and skip the
            // per-user candidate re-resolution that evaluateForUser would do.
            $result = $this->matchEngine->scoreFor($inquiry, $user);
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
        $machineDealerIds = [];

        foreach ($top as $entry) {
            $user = $entry['user'];
            $role = $entry['role'];

            if ($role === 'dealer' && $user->dealer) {
                $dealerIds[] = $user->dealer->id;
            } elseif ($role === 'converter' && $user->converter) {
                $converterIds[] = $user->converter->id;
            } elseif (($role === 'machine_dealer' || $role === 'machineDealer') && $user->machineDealer) {
                $machineDealerIds[] = $user->machineDealer->id;
            }
        }

        return [
            'dealer_ids' => $dealerIds,
            'converter_ids' => $converterIds,
            'machine_dealer_ids' => $machineDealerIds,
        ];
    }

    /**
     * Emit notification when a late-joining user is matched via lazy matching.
     * Dedupe is handled by NotificationService unique (user_id, dedupe_key).
     */
    private function notifyLazyMatch(Inquiry $inquiry, User $user, string $role): void
    {
        // Fallback: some older users may not have primary_role set correctly yet.
        if ($role === '') {
            if ($user->dealer) {
                $role = 'dealer';
            } elseif ($user->converter) {
                $role = 'converter';
            } elseif ($user->machineDealer) {
                $role = 'machine_dealer';
            }
        }

        if ($role === 'dealer') {
            $dealerId = (int) ($user->dealer?->id ?? 0);
            if ($dealerId > 0) {
                $this->matchmakingService->notifyMatchedRecipients($inquiry, [$dealerId], [], []);
            }

            return;
        }

        if ($role === 'converter') {
            $converterId = (int) ($user->converter?->id ?? 0);
            if ($converterId > 0) {
                $this->matchmakingService->notifyMatchedRecipients($inquiry, [], [$converterId], []);
            }

            return;
        }

        if ($role === 'machine_dealer') {
            $machineDealerId = (int) ($user->machineDealer?->id ?? 0);
            if ($machineDealerId > 0) {
                $this->matchmakingService->notifyMatchedRecipients($inquiry, [], [], [$machineDealerId]);
            }
        }
    }

    private function topNForUrgency(?string $urgency): int
    {
        $isUrgent = strtolower((string) $urgency) === 'urgent';

        return $isUrgent
            ? (int) config('matchmaking.urgent_top_n', 50)
            : (int) config('matchmaking.normal_top_n', 10);
    }
}
