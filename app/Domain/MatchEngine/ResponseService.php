<?php

namespace App\Domain\MatchEngine;

use App\Domain\MatchEngine\Models\InquiryResponse;
use App\Domain\MatchEngine\Models\MatchHistory;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResponseService
{
    /**
     * Record a response to an inquiry.
     *
     * Guard order:
     *   1. Self-response
     *   2. Expired (hard expires_at + soft auto-expiry)
     *   3. Locked / at response limit
     *   4. Duplicate
     *   5. Eligibility (must be in match_histories)
     *
     * Inside the transaction:
     *   - Insert the response first
     *   - Count with lockForUpdate to prevent race conditions
     *   - Lock inquiry if threshold reached
     *
     * @throws \InvalidArgumentException
     */
    public function respond(
        Inquiry $inquiry,
        User $responder,
        string $description,
        ?float $approxPrice = null,
    ): InquiryResponse {
        $this->guardSelfResponse($inquiry, $responder);
        $this->guardExpired($inquiry);
        $this->guardLocked($inquiry);
        $this->guardDuplicate($inquiry, $responder);
        $this->guardEligibility($inquiry, $responder);

        return DB::transaction(function () use ($inquiry, $responder, $description, $approxPrice) {
            $response = InquiryResponse::create([
                'inquiry_id'     => $inquiry->id,
                'responder_id'   => $responder->id,
                'responder_role' => $responder->primary_role,
                'description'    => $description,
                'approx_price'   => $approxPrice,
                'responded_at'   => now(),
            ]);

            $this->lockIfThresholdReached($inquiry);
            $this->createChatThread($inquiry, $responder->id);

            return $response;
        });
    }

    // ---------------------------------------------------------------
    //  Guards
    // ---------------------------------------------------------------

    private function guardSelfResponse(Inquiry $inquiry, User $responder): void
    {
        if ((int) $inquiry->poster_id === (int) $responder->id) {
            throw new \InvalidArgumentException('Cannot respond to your own inquiry.');
        }
    }

    private function guardExpired(Inquiry $inquiry): void
    {
        if ($inquiry->expires_at !== null && $inquiry->expires_at->isPast()) {
            throw new \InvalidArgumentException('This inquiry has expired.');
        }

        $autoExpiryDays = (int) config('matchmaking.auto_expiry_days', 2);
        $threshold = now()->subDays($autoExpiryDays);

        if ($inquiry->created_at->lt($threshold)) {
            throw new \InvalidArgumentException('This inquiry has expired due to inactivity.');
        }
    }

    private function guardLocked(Inquiry $inquiry): void
    {
        if ($inquiry->status === InquiryStatus::LOCKED) {
            throw new \InvalidArgumentException('This inquiry is locked.');
        }

        $limit = (int) config('matchmaking.response_limit', 10);
        $count = InquiryResponse::where('inquiry_id', $inquiry->id)->count();

        if ($count >= $limit) {
            throw new \InvalidArgumentException('This inquiry has reached its response limit.');
        }
    }

    private function guardDuplicate(Inquiry $inquiry, User $responder): void
    {
        $exists = InquiryResponse::where('inquiry_id', $inquiry->id)
            ->where('responder_id', $responder->id)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('You have already responded to this inquiry.');
        }
    }

    private function guardEligibility(Inquiry $inquiry, User $responder): void
    {
        $matched = MatchHistory::where('inquiry_id', $inquiry->id)
            ->where('matched_user_id', $responder->id)
            ->exists();

        if (!$matched) {
            throw new \InvalidArgumentException('You are not eligible to respond to this inquiry.');
        }
    }

    // ---------------------------------------------------------------
    //  Lock logic
    // ---------------------------------------------------------------

    /**
     * Atomically count responses using lockForUpdate and lock the
     * inquiry when the configured response limit is reached.
     */
    private function lockIfThresholdReached(Inquiry $inquiry): void
    {
        $limit = (int) config('matchmaking.response_limit', 10);

        $count = InquiryResponse::where('inquiry_id', $inquiry->id)
            ->lockForUpdate()
            ->count();

        if ($count >= $limit) {
            $inquiry->update([
                'status'    => InquiryStatus::LOCKED,
                'locked_at' => now(),
            ]);
        }
    }

    // ---------------------------------------------------------------
    //  Chat thread stub
    // ---------------------------------------------------------------

    /**
     * Placeholder for chat thread creation.
     * Will use existing chat_threads table once wired to routes.
     */
    private function createChatThread(Inquiry $inquiry, int $responderId): void
    {
        // Stub — deferred until v2 route integration.
        // Will create a ChatThread record linking poster <-> responder.
    }
}
