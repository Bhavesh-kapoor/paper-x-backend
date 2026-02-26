<?php

namespace App\Domain\MatchEngine;

use App\Domain\MatchEngine\Models\InquiryResponse;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * V2 Match Engine orchestrator.
 *
 * Wires CandidateResolver → SpecFilter → ScoreCalculator into a single
 * pipeline. Does NOT persist results — call MatchPersister separately
 * when you need to write to match_histories.
 */
class MatchEngine
{
    public function __construct(
        private readonly CandidateResolver $candidateResolver,
        private readonly SpecFilter $specFilter,
        private readonly ScoreCalculator $scoreCalculator,
    ) {}

    /**
     * Return scored inquiries that are eligible for the given user.
     *
     * Expiry and lock filtering happens here at the query level so the
     * CandidateResolver doesn't silently swallow the entire pipeline.
     *
     * @return Collection  Each item: ['inquiry' => Inquiry, 'final_score' => int, 'breakdown' => array]
     */
    public function eligibleForUser(User $user): Collection
    {
        $inquiries = $this->activeInquiriesExcluding($user);

        return $inquiries
            ->map(fn (Inquiry $inquiry) => $this->evaluateForUser($inquiry, $user))
            ->filter()
            ->sortByDesc('final_score')
            ->values();
    }

    /**
     * Run the full pipeline for a single inquiry and a single candidate user.
     * Returns null when the user is not eligible or fails hard filters.
     */
    public function evaluateForUser(Inquiry $inquiry, User $user): ?array
    {
        $candidates = $this->candidateResolver->resolve($inquiry);

        if (!$candidates->contains('id', $user->id)) {
            return null;
        }

        $buyerSpec  = $this->extractBuyerSpec($inquiry);
        $sellerSpec = $this->extractSellerSpec($user);

        $specResult = $this->specFilter->evaluate(
            $buyerSpec,
            $sellerSpec,
            $inquiry->urgency ?? 'normal',
        );

        if ($specResult['status'] === SpecFilter::STATUS_HARD_FAIL) {
            return null;
        }

        $distanceKm = $this->candidateResolver->distanceBetween($inquiry, $user);

        $scoreResult = $this->scoreCalculator->score(
            $inquiry,
            $user,
            $specResult,
            $distanceKm,
        );

        return [
            'inquiry'     => $inquiry,
            'user'        => $user,
            'role'        => $user->primary_role,
            'final_score' => $scoreResult['final_score'],
            'breakdown'   => $scoreResult['breakdown'],
        ];
    }

    // ---------------------------------------------------------------
    //  Query helpers
    // ---------------------------------------------------------------

    private function activeInquiriesExcluding(User $user): Collection
    {
        $autoExpiryDays = (int) config('matchmaking.auto_expiry_days', 2);
        $maxResponses   = (int) config('matchmaking.max_responses', 10);
        $expiryThreshold = now()->subDays($autoExpiryDays);

        return Inquiry::query()
            ->where('status', InquiryStatus::POSTED)
            ->where('poster_id', '!=', $user->id)
            ->whereNull('locked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($expiryThreshold, $maxResponses) {
                // Keep if newer than threshold OR already has enough responses
                $q->where('created_at', '>=', $expiryThreshold)
                  ->orWhere(function ($sub) use ($maxResponses) {
                      $sub->whereRaw(
                          '(select count(*) from inquiry_responses where inquiry_responses.inquiry_id = inquiries.id) >= ?',
                          [$maxResponses]
                      );
                  });
            })
            ->with(['materials', 'items'])
            ->get();
    }

    // ---------------------------------------------------------------
    //  Spec extraction (model → plain array for SpecFilter)
    // ---------------------------------------------------------------

    /**
     * Aggregate buyer specs from the first inquiry item.
     * Extend to multi-item matching when needed.
     */
    private function extractBuyerSpec(Inquiry $inquiry): array
    {
        $item = $inquiry->items->first();

        if (!$item) {
            return $this->emptySpec();
        }

        return [
            'thickness_gsm' => $item->thickness_gsm ? (float) $item->thickness_gsm : null,
            'thickness_mm'  => $item->thickness_mm  ? (float) $item->thickness_mm  : null,
            'sheet_width'   => $item->sheet_width    ? (float) $item->sheet_width   : null,
            'sheet_length'  => $item->sheet_length   ? (float) $item->sheet_length  : null,
            'reel_width'    => $item->reel_width     ? (float) $item->reel_width    : null,
            'quantity'      => $item->quantity        ? (float) $item->quantity      : null,
        ];
    }

    /**
     * Extract seller spec from the user's inventory.
     *
     * Currently returns an empty spec (all nulls) because v2 matching
     * initially compares inquiry→inquiry. When dealer/converter inventory
     * tables are formalised, this method will pull real data.
     */
    private function extractSellerSpec(User $user): array
    {
        return $this->emptySpec();
    }

    private function emptySpec(): array
    {
        return [
            'thickness_gsm' => null,
            'thickness_mm'  => null,
            'sheet_width'   => null,
            'sheet_length'  => null,
            'reel_width'    => null,
            'quantity'      => null,
        ];
    }
}
