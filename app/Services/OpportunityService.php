<?php

namespace App\Services;

use App\Enums\AcceptanceStatus;
use App\Enums\InquiryStatus;
use App\Enums\SessionStatus;
use App\Models\Dealer;
use App\Models\DealerAcceptance;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class OpportunityService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    public function getOpportunities(int $userId, array $filters = []): array
    {
        $dealer = Dealer::where('user_id', $userId)->with(['materials', 'machines'])->firstOrFail();

        if (!$dealer->profile_complete || $dealer->status->value !== 'ACTIVE') {
            return [
                'opportunities' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => $filters['per_page'] ?? 15,
                    'last_page' => 1,
                ],
            ];
        }

        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $inquiries = Inquiry::where('status', InquiryStatus::MATCHING)
            ->with(['materials', 'machines', 'brand'])
            ->get();

        $matchedOpportunities = [];

        foreach ($inquiries as $inquiry) {
            if ($this->matchesDealer($inquiry, $dealer)) {
                $matchedOpportunities[] = [
                    'inquiry_id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'quantity' => $inquiry->quantity,
                    'quantity_unit' => $inquiry->quantity_unit,
                    'urgency' => $inquiry->urgency,
                    'location' => $inquiry->location,
                    'time_left_to_accept' => $this->calculateTimeLeft($inquiry),
                    'match_score' => $this->calculateMatchScore($inquiry, $dealer),
                ];
            }
        }

        // Sort by match score descending
        usort($matchedOpportunities, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        // Manual pagination
        $total = count($matchedOpportunities);
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedOpportunities = array_slice($matchedOpportunities, $offset, $perPage);

        return [
            'opportunities' => $paginatedOpportunities,
            'pagination' => [
                'current_page' => (int) $page,
                'total' => $total,
                'per_page' => (int) $perPage,
                'last_page' => $lastPage,
            ],
        ];
    }

    public function getOpportunityDetails(int $inquiryId, int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();
        $inquiry = Inquiry::with(['materials', 'machines', 'brand'])->findOrFail($inquiryId);

        return [
            'inquiry_id' => $inquiry->id,
            'title' => $inquiry->title,
            'description' => $inquiry->description,
            'quantity' => $inquiry->quantity,
            'quantity_unit' => $inquiry->quantity_unit,
            'urgency' => $inquiry->urgency,
            'location' => $inquiry->location,
            'specs' => $inquiry->specs,
            'attachments' => $inquiry->attachments ? array_map(fn($path) => url($path), $inquiry->attachments) : [],
            'timeline' => [
                'deadline' => $inquiry->deadline?->toIso8601String(),
            ],
            'brand_name' => null, // Hidden before session lock
            'materials' => $inquiry->materials->pluck('name'),
            'machines' => $inquiry->machines->pluck('name'),
        ];
    }

    public function acceptOpportunity(int $inquiryId, int $userId): array
    {
        return DB::transaction(function () use ($inquiryId, $userId) {
            $dealer = Dealer::where('user_id', $userId)->firstOrFail();
            $inquiry = Inquiry::findOrFail($inquiryId);

            if ($inquiry->status !== InquiryStatus::MATCHING) {
                throw new \Exception('Inquiry is not available for acceptance', 400);
            }

            // Check if already accepted
            $existingAcceptance = DealerAcceptance::where('dealer_id', $dealer->id)
                ->where('inquiry_id', $inquiryId)
                ->first();

            if ($existingAcceptance) {
                throw new \Exception('Already accepted or declined this opportunity', 400);
            }

            // Count current acceptances
            $acceptanceCount = DealerAcceptance::where('inquiry_id', $inquiryId)
                ->where('status', AcceptanceStatus::ACCEPTED)
                ->count();

            if ($acceptanceCount >= 10) {
                throw new \Exception('Maximum acceptances reached', 400);
            }

            // Create acceptance
            DealerAcceptance::create([
                'dealer_id' => $dealer->id,
                'inquiry_id' => $inquiryId,
                'status' => AcceptanceStatus::ACCEPTED,
            ]);

            // Check if we've reached 10 acceptances
            $newCount = DealerAcceptance::where('inquiry_id', $inquiryId)
                ->where('status', AcceptanceStatus::ACCEPTED)
                ->count();

            if ($newCount === 10) {
                $this->lockSession($inquiryId);
            }

            return [
                'message' => 'Opportunity accepted successfully',
                'accepted_count' => $newCount,
            ];
        });
    }

    public function declineOpportunity(int $inquiryId, int $userId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($inquiryId, $userId, $reason) {
            $dealer = Dealer::where('user_id', $userId)->firstOrFail();
            $inquiry = Inquiry::findOrFail($inquiryId);

            if ($inquiry->status !== InquiryStatus::MATCHING) {
                throw new \Exception('Inquiry is not available for decline', 400);
            }

            DealerAcceptance::updateOrCreate(
                [
                    'dealer_id' => $dealer->id,
                    'inquiry_id' => $inquiryId,
                ],
                [
                    'status' => AcceptanceStatus::DECLINED,
                    'decline_reason' => $reason,
                ]
            );

            return ['message' => 'Opportunity declined successfully'];
        });
    }

    private function lockSession(int $inquiryId): void
    {
        $inquiry = Inquiry::findOrFail($inquiryId);

        $inquiry->update(['status' => InquiryStatus::SESSION_LOCKED]);

        $session = MatchingSession::create([
            'inquiry_id' => $inquiryId,
            'status' => SessionStatus::ACTIVE,
            'locked_at' => now(),
            'expires_at' => now()->addMinutes(30), // 30 minutes countdown
        ]);

        // Notify all accepted dealers
        $acceptances = DealerAcceptance::where('inquiry_id', $inquiryId)
            ->where('status', AcceptanceStatus::ACCEPTED)
            ->with('dealer.user')
            ->get();

        foreach ($acceptances as $acceptance) {
            $this->notificationService->create(
                $acceptance->dealer->user_id,
                'SESSION_LOCKED',
                'Session Locked',
                "Session for inquiry #{$inquiryId} has been locked. You can now view brand details and start chatting.",
                $session
            );
        }
    }

    private function matchesDealer(Inquiry $inquiry, Dealer $dealer): bool
    {
        // Check materials match
        $inquiryMaterialIds = $inquiry->materials->pluck('id')->toArray();
        $dealerMaterialIds = $dealer->materials->pluck('id')->toArray();
        if (count(array_intersect($inquiryMaterialIds, $dealerMaterialIds)) !== count($inquiryMaterialIds)) {
            return false;
        }

        // Check machines match
        $inquiryMachineIds = $inquiry->machines->pluck('id')->toArray();
        $dealerMachineIds = $dealer->machines->pluck('id')->toArray();
        if (count(array_intersect($inquiryMachineIds, $dealerMachineIds)) !== count($inquiryMachineIds)) {
            return false;
        }

        // Check capacity
        if ($inquiry->quantity > $dealer->capacity_monthly) {
            return false;
        }

        // Check geography (simplified - can be enhanced with distance calculation)
        // For now, we'll just check if dealer has locations
        if ($dealer->locations()->count() === 0) {
            return false;
        }

        return true;
    }

    private function calculateMatchScore(Inquiry $inquiry, Dealer $dealer): int
    {
        $score = 0;

        // Material match (30 points)
        $materialMatch = count(array_intersect(
            $inquiry->materials->pluck('id')->toArray(),
            $dealer->materials->pluck('id')->toArray()
        )) / max($inquiry->materials->count(), 1);
        $score += (int) ($materialMatch * 30);

        // Machine match (30 points)
        $machineMatch = count(array_intersect(
            $inquiry->machines->pluck('id')->toArray(),
            $dealer->machines->pluck('id')->toArray()
        )) / max($inquiry->machines->count(), 1);
        $score += (int) ($machineMatch * 30);

        // Capacity match (20 points)
        $capacityMatch = min(1, $dealer->capacity_monthly / max($inquiry->quantity, 1));
        $score += (int) ($capacityMatch * 20);

        // Geography match (20 points) - simplified
        $score += 20;

        return min(100, $score);
    }

    private function calculateTimeLeft(Inquiry $inquiry): ?int
    {
        if (!$inquiry->deadline) {
            return null;
        }

        return max(0, now()->diffInSeconds($inquiry->deadline));
    }
}

