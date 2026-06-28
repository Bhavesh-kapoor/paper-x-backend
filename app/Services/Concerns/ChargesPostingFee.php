<?php

namespace App\Services\Concerns;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Wallet;
use App\Services\PricingService;

/**
 * Shared server-side posting-fee charge. Computes the fee from the pricing model
 * (config/pricing.php via PricingService) and deducts it from the user's wallet ONCE,
 * so every posting flow charges identically and the client cannot dictate the amount.
 *
 * Call inside the posting DB::transaction, after the inquiry is created, so an
 * insufficient balance rolls the whole post back.
 */
trait ChargesPostingFee
{
    /**
     * @param array $specs  PricingService::quote() spec payload (role, inquiry_type, material, etc.)
     * @return array        The full quote (base_fee, gst, total, breakdown).
     * @throws InsufficientBalanceException
     */
    protected function chargePostingFee(int $userId, array $specs, ?int $inquiryId = null, array $metadata = []): array
    {
        $quote = app(PricingService::class)->quote($specs);
        $total = (int) $quote['total'];

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'status' => 'ACTIVE']
        );

        if ((float) $wallet->balance < $total) {
            throw new InsufficientBalanceException($total, (float) $wallet->balance);
        }

        $wallet->deductCredits(
            $total,
            'Post requirement fee',
            'REQUIREMENT_POSTED',
            $inquiryId,
            'inquiry',
            array_merge($metadata, ['pricing' => $quote['breakdown']])
        );

        return $quote;
    }
}
