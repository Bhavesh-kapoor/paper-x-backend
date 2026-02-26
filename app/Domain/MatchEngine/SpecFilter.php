<?php

namespace App\Domain\MatchEngine;

/**
 * Pure calculation class — no DB queries.
 *
 * Evaluates whether a seller's specs fall within the buyer's tolerance
 * window based on urgency level, returning one of:
 *   HARD_FAIL | PASS | PASS_WITH_VARIANCE
 */
class SpecFilter
{
    // Tolerance config keyed by urgency → dimension
    private const TOLERANCES = [
        'normal' => [
            'gsm_percent'       => 0.10,   // ±10 %
            'thickness_mm'      => 0.2,    // ±0.2 mm
            'sheet_size_inches' => 2.0,    // ±2″ per side
            'reel_width_inches' => 1.0,    // ±1″
            'quantity_ratio'    => 0.60,   // seller >= 60 % of buyer qty
        ],
        'urgent' => [
            'gsm_percent'       => 0.20,   // ±20 %
            'thickness_mm'      => 0.3,    // ±0.3 mm
            'sheet_size_inches' => 4.0,    // ±4″ per side
            'reel_width_inches' => 2.0,    // ±2″
            'quantity_ratio'    => 0.20,   // seller >= 20 % of buyer qty
        ],
    ];

    public const STATUS_PASS              = 'PASS';
    public const STATUS_PASS_WITH_VARIANCE = 'PASS_WITH_VARIANCE';
    public const STATUS_HARD_FAIL          = 'HARD_FAIL';

    /**
     * Evaluate a single candidate against the inquiry's spec.
     *
     * Both $buyerSpec and $sellerSpec are plain arrays with nullable keys:
     *   thickness_gsm, thickness_mm, sheet_width, sheet_length,
     *   reel_width, quantity
     *
     * @return array{status: string, details: array<string, array>}
     */
    public function evaluate(array $buyerSpec, array $sellerSpec, string $urgency): array
    {
        $tolerances = $this->tolerancesFor($urgency);
        $details    = [];
        $hasVariance = false;
        $hasFail     = false;

        // --- GSM ---
        $gsmResult = $this->evaluatePercentTolerance(
            $buyerSpec['thickness_gsm'] ?? null,
            $sellerSpec['thickness_gsm'] ?? null,
            $tolerances['gsm_percent'],
            'gsm'
        );
        $details['gsm'] = $gsmResult;
        $this->accumulateStatus($gsmResult['status'], $hasFail, $hasVariance);

        // --- Thickness MM ---
        $mmResult = $this->evaluateAbsoluteTolerance(
            $buyerSpec['thickness_mm'] ?? null,
            $sellerSpec['thickness_mm'] ?? null,
            $tolerances['thickness_mm'],
            'thickness_mm'
        );
        $details['thickness_mm'] = $mmResult;
        $this->accumulateStatus($mmResult['status'], $hasFail, $hasVariance);

        // --- Sheet width ---
        $sheetWResult = $this->evaluateAbsoluteTolerance(
            $buyerSpec['sheet_width'] ?? null,
            $sellerSpec['sheet_width'] ?? null,
            $tolerances['sheet_size_inches'],
            'sheet_width'
        );
        $details['sheet_width'] = $sheetWResult;
        $this->accumulateStatus($sheetWResult['status'], $hasFail, $hasVariance);

        // --- Sheet length ---
        $sheetLResult = $this->evaluateAbsoluteTolerance(
            $buyerSpec['sheet_length'] ?? null,
            $sellerSpec['sheet_length'] ?? null,
            $tolerances['sheet_size_inches'],
            'sheet_length'
        );
        $details['sheet_length'] = $sheetLResult;
        $this->accumulateStatus($sheetLResult['status'], $hasFail, $hasVariance);

        // --- Reel width ---
        $reelResult = $this->evaluateAbsoluteTolerance(
            $buyerSpec['reel_width'] ?? null,
            $sellerSpec['reel_width'] ?? null,
            $tolerances['reel_width_inches'],
            'reel_width'
        );
        $details['reel_width'] = $reelResult;
        $this->accumulateStatus($reelResult['status'], $hasFail, $hasVariance);

        // --- Quantity ---
        $qtyResult = $this->evaluateQuantity(
            $buyerSpec['quantity'] ?? null,
            $sellerSpec['quantity'] ?? null,
            $tolerances['quantity_ratio']
        );
        $details['quantity'] = $qtyResult;
        $this->accumulateStatus($qtyResult['status'], $hasFail, $hasVariance);

        // Aggregate
        if ($hasFail) {
            $status = self::STATUS_HARD_FAIL;
        } elseif ($hasVariance) {
            $status = self::STATUS_PASS_WITH_VARIANCE;
        } else {
            $status = self::STATUS_PASS;
        }

        return [
            'status'  => $status,
            'details' => $details,
        ];
    }

    /**
     * Compute a 0.0–1.0 similarity ratio from a completed evaluate() result.
     * Counts each dimension that was evaluated and weights PASS = 1.0,
     * PASS_WITH_VARIANCE = scaled by how close the value is to exact,
     * and skipped dimensions (both sides null) = 1.0 (neutral).
     */
    public function similarityRatio(array $evaluateResult): float
    {
        $details = $evaluateResult['details'] ?? [];
        if (empty($details)) {
            return 1.0;
        }

        $sum   = 0.0;
        $count = 0;

        foreach ($details as $dim) {
            if ($dim['status'] === 'SKIPPED') {
                continue;
            }
            $count++;
            $sum += $dim['similarity'] ?? ($dim['status'] === self::STATUS_PASS ? 1.0 : 0.0);
        }

        return $count > 0 ? $sum / $count : 1.0;
    }

    // ---------------------------------------------------------------
    //  Tolerance evaluation helpers
    // ---------------------------------------------------------------

    private function evaluatePercentTolerance(?float $buyer, ?float $seller, float $tolerancePercent, string $label): array
    {
        if ($buyer === null || $seller === null) {
            return ['status' => 'SKIPPED', 'similarity' => 1.0, 'label' => $label];
        }

        if ($buyer == 0) {
            return $seller == 0
                ? ['status' => self::STATUS_PASS, 'similarity' => 1.0, 'label' => $label]
                : ['status' => self::STATUS_HARD_FAIL, 'similarity' => 0.0, 'label' => $label];
        }

        $deviation = abs($seller - $buyer) / abs($buyer);

        if ($deviation == 0) {
            return ['status' => self::STATUS_PASS, 'similarity' => 1.0, 'label' => $label];
        }

        if ($deviation <= $tolerancePercent) {
            $similarity = 1.0 - ($deviation / $tolerancePercent);
            return ['status' => self::STATUS_PASS_WITH_VARIANCE, 'similarity' => $similarity, 'label' => $label];
        }

        return ['status' => self::STATUS_HARD_FAIL, 'similarity' => 0.0, 'label' => $label];
    }

    private function evaluateAbsoluteTolerance(?float $buyer, ?float $seller, float $toleranceAbs, string $label): array
    {
        if ($buyer === null || $seller === null) {
            return ['status' => 'SKIPPED', 'similarity' => 1.0, 'label' => $label];
        }

        $deviation = abs($seller - $buyer);

        if ($deviation == 0) {
            return ['status' => self::STATUS_PASS, 'similarity' => 1.0, 'label' => $label];
        }

        if ($deviation <= $toleranceAbs) {
            $similarity = 1.0 - ($deviation / $toleranceAbs);
            return ['status' => self::STATUS_PASS_WITH_VARIANCE, 'similarity' => $similarity, 'label' => $label];
        }

        return ['status' => self::STATUS_HARD_FAIL, 'similarity' => 0.0, 'label' => $label];
    }

    private function evaluateQuantity(?float $buyerQty, ?float $sellerQty, float $minRatio): array
    {
        if ($buyerQty === null || $sellerQty === null) {
            return ['status' => 'SKIPPED', 'similarity' => 1.0, 'label' => 'quantity'];
        }

        if ($buyerQty == 0) {
            return ['status' => self::STATUS_PASS, 'similarity' => 1.0, 'label' => 'quantity'];
        }

        $ratio = $sellerQty / $buyerQty;

        if ($ratio >= 1.0) {
            return ['status' => self::STATUS_PASS, 'similarity' => 1.0, 'label' => 'quantity'];
        }

        if ($ratio >= $minRatio) {
            $similarity = ($ratio - $minRatio) / (1.0 - $minRatio);
            return ['status' => self::STATUS_PASS_WITH_VARIANCE, 'similarity' => max(0.0, $similarity), 'label' => 'quantity'];
        }

        return ['status' => self::STATUS_HARD_FAIL, 'similarity' => 0.0, 'label' => 'quantity'];
    }

    // ---------------------------------------------------------------
    //  Internals
    // ---------------------------------------------------------------

    private function tolerancesFor(string $urgency): array
    {
        $key = strtolower($urgency) === 'urgent' ? 'urgent' : 'normal';
        return self::TOLERANCES[$key];
    }

    private function accumulateStatus(string $status, bool &$hasFail, bool &$hasVariance): void
    {
        if ($status === self::STATUS_HARD_FAIL) {
            $hasFail = true;
        } elseif ($status === self::STATUS_PASS_WITH_VARIANCE) {
            $hasVariance = true;
        }
    }
}
