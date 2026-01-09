<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Models\Dealer;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function submitQuote(int $inquiryId, int $userId, array $data): Quotation
    {
        return DB::transaction(function () use ($inquiryId, $userId, $data) {
            $dealer = Dealer::where('user_id', $userId)->firstOrFail();
            $session = \App\Models\MatchingSession::whereHas('inquiry', function ($query) use ($inquiryId) {
                $query->where('id', $inquiryId);
            })->firstOrFail();

            // Verify dealer is part of session
            $isParticipant = $session->inquiry->acceptances()
                ->where('dealer_id', $dealer->id)
                ->exists();

            if (!$isParticipant) {
                throw new \Exception('You are not part of this session', 403);
            }

            return Quotation::updateOrCreate(
                [
                    'dealer_id' => $dealer->id,
                    'inquiry_id' => $inquiryId,
                ],
                [
                    'session_id' => $session->id,
                    'quoted_price' => $data['quoted_price'],
                    'currency' => $data['currency'] ?? 'INR',
                    'delivery_days' => $data['delivery_days'],
                    'notes' => $data['notes'] ?? null,
                    'deal_status' => DealStatus::PENDING,
                ]
            );
        });
    }

    public function acceptDeal(int $quotationId, int $userId): array
    {
        // This would typically be called by the brand, but included for completeness
        $quotation = Quotation::findOrFail($quotationId);
        $quotation->update(['deal_status' => DealStatus::ACCEPTED]);

        return ['message' => 'Deal accepted'];
    }

    public function rejectDeal(int $quotationId, int $userId): array
    {
        // This would typically be called by the brand, but included for completeness
        $quotation = Quotation::findOrFail($quotationId);
        $quotation->update(['deal_status' => DealStatus::REJECTED]);

        return ['message' => 'Deal rejected'];
    }
}




