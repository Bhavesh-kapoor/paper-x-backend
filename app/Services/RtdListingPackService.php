<?php

namespace App\Services;

use App\Exceptions\RTDDomainException;
use App\Models\RtdConverterEntitlement;
use App\Models\RtdListingPack;
use App\Models\Wallet;
use Illuminate\Support\Carbon;

class RtdListingPackService
{
    public function getAvailablePacks(): array
    {
        return RtdListingPack::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn (RtdListingPack $pack) => [
                'slug'           => $pack->slug,
                'name'           => $pack->name,
                'product_limit'  => $pack->product_limit,
                'validity_days'  => $pack->validity_days,
                'price'          => (float) $pack->price,
                'sort_order'     => $pack->sort_order,
            ])
            ->values()
            ->all();
    }

    public function getActiveEntitlement(int $userId): ?RtdConverterEntitlement
    {
        return RtdConverterEntitlement::where('user_id', $userId)
            ->where('validity_ends_at', '>=', now())
            ->whereRaw('used_count < product_limit')
            ->orderBy('validity_ends_at', 'desc')
            ->first();
    }

    public function canAddProduct(int $userId): bool
    {
        return $this->getActiveEntitlement($userId) !== null;
    }

    /**
     * Purchase a listing pack for the user. Deducts from wallet and creates entitlement.
     *
     * @throws RTDDomainException when pack not found, inactive, or insufficient balance
     */
    public function purchasePack(int $userId, string $packSlug): RtdConverterEntitlement
    {
        $pack = RtdListingPack::where('slug', $packSlug)->where('is_active', true)->first();
        if (!$pack) {
            throw new RTDDomainException('Invalid or inactive listing pack.', 422);
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'status' => 'ACTIVE']
        );

        $amount = (float) $pack->price;
        if ($wallet->balance < $amount) {
            throw new RTDDomainException('Insufficient wallet balance. Please add credits to purchase a listing pack.', 400);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $pack, $wallet, $amount) {
            $validityEndsAt = now()->addDays($pack->validity_days);
            $entitlement = RtdConverterEntitlement::create([
                'user_id'         => $userId,
                'pack_slug'       => $pack->slug,
                'product_limit'   => $pack->product_limit,
                'used_count'      => 0,
                'validity_ends_at' => $validityEndsAt,
                'purchased_at'    => now(),
            ]);

            $tx = $wallet->deductCredits(
                $amount,
                "RTD listing pack: {$pack->name}",
                'RTD_LISTING_PACK',
                (string) $entitlement->id,
                'rtd_listing_entitlement',
                ['pack_slug' => $pack->slug]
            );

            if ($tx === null) {
                throw new RTDDomainException('Insufficient wallet balance.', 400);
            }

            return $entitlement->fresh();
        });
    }

    /**
     * Increment used_count on the active entitlement for this user.
     * Call this after a product is successfully created.
     *
     * @throws RTDDomainException when no active entitlement
     */
    public function incrementUsedCount(int $userId): void
    {
        $entitlement = $this->getActiveEntitlement($userId);
        if (!$entitlement) {
            throw new RTDDomainException('No active listing pack entitlement found.', 422);
        }
        $entitlement->increment('used_count');
    }
}
