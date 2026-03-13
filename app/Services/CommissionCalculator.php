<?php

namespace App\Services;

use App\Exceptions\RTDDomainException;

class CommissionCalculator
{
    private const ORDER_CAP = 300000;

    private const SLABS = [
        ['max' => 25000,  'percent' => 9],
        ['max' => 75000,  'percent' => 8],
        ['max' => 200000, 'percent' => 6],
        ['max' => 300000, 'percent' => 5],
    ];

    public function calculateCommission(float $subtotal): array
    {
        $percent = $this->resolveCommissionPercent($subtotal);
        $amount  = round($subtotal * $percent / 100, 2);

        return [
            'percent' => $percent,
            'amount'  => $amount,
        ];
    }

    public function validateOrderCap(float $subtotal): void
    {
        if ($subtotal > self::ORDER_CAP) {
            throw new RTDDomainException(
                "Order subtotal ₹{$subtotal} exceeds maximum cap of ₹" . number_format(self::ORDER_CAP)
            );
        }
    }

    public function calculateGST(float $amount, float $gstPercent = 18.0): float
    {
        return round($amount * $gstPercent / 100, 2);
    }

    /**
     * @param bool $sellerGstRegistered When false, gst_percent and gst_amount are 0 (converter not GST registered).
     */
    public function calculateTotal(int $quantity, float $unitPrice, bool $sellerGstRegistered = true): array
    {
        $subtotal   = round($quantity * $unitPrice, 2);
        $commission = $this->calculateCommission($subtotal);
        $gstPercent = $sellerGstRegistered ? 18.0 : 0.0;
        $gstAmount  = $sellerGstRegistered ? $this->calculateGST($subtotal + $commission['amount']) : 0.0;
        $total      = round($subtotal + $commission['amount'] + $gstAmount, 2);

        return [
            'subtotal'           => $subtotal,
            'commission_percent' => $commission['percent'],
            'commission_amount'  => $commission['amount'],
            'gst_percent'        => $gstPercent,
            'gst_amount'         => $gstAmount,
            'total_amount'       => $total,
        ];
    }

    private function resolveCommissionPercent(float $subtotal): float
    {
        foreach (self::SLABS as $slab) {
            if ($subtotal <= $slab['max']) {
                return $slab['percent'];
            }
        }

        return self::SLABS[array_key_last(self::SLABS)]['percent'];
    }
}
