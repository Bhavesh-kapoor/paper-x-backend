<?php

namespace App\Domain\MatchEngine;

use App\Models\Inquiry;
use App\Models\User;

/**
 * Weighted 0–100 scoring.
 *
 * All weights read from config('matchmaking.weights') at runtime.
 * No magic numbers. Activity scoring is delegated to ActivityScoreProvider.
 */
class ScoreCalculator
{
    public function __construct(
        private readonly ActivityScoreProvider $activityProvider,
        private readonly SpecFilter $specFilter,
    ) {}

    /**
     * @param  array       $specResult  Output of SpecFilter::evaluate()
     * @param  float|null  $distanceKm  Haversine km (null = unknown)
     *
     * @return array{final_score: int, breakdown: array}
     */
    public function score(
        Inquiry $inquiry,
        User $candidate,
        array $specResult,
        ?float $distanceKm,
    ): array {
        $weights = $this->weights();

        $specRaw      = $this->specScore($specResult);
        $distanceRaw  = $this->distanceScore($distanceKm, $inquiry->urgency);
        $activityRaw  = $this->activityProvider->score($candidate);
        $freshnessRaw = $this->freshnessScore($inquiry);
        $capacityRaw  = $this->capacityScore($specResult);

        $weighted = [
            'spec'      => round($specRaw      * $weights['spec'], 2),
            'distance'  => round($distanceRaw  * $weights['distance'], 2),
            'activity'  => round($activityRaw  * $weights['activity'], 2),
            'freshness' => round($freshnessRaw * $weights['freshness'], 2),
            'capacity'  => round($capacityRaw  * $weights['capacity'], 2),
        ];

        $finalScore = (int) round(array_sum($weighted));

        return [
            'final_score' => min(100, max(0, $finalScore)),
            'breakdown'   => [
                'material_match'   => $specResult['status'] !== SpecFilter::STATUS_HARD_FAIL,
                'distance_km'      => $distanceKm !== null ? round($distanceKm, 1) : null,
                'spec_score'       => $weighted['spec'],
                'distance_score'   => $weighted['distance'],
                'activity_score'   => $weighted['activity'],
                'freshness_score'  => $weighted['freshness'],
                'capacity_score'   => $weighted['capacity'],
            ],
        ];
    }

    // ---------------------------------------------------------------
    //  Dimension scorers (each returns 0.0 – 1.0)
    // ---------------------------------------------------------------

    /**
     * Spec similarity: exact = 1.0, within tolerance = scaled, hard fail = 0.0.
     */
    private function specScore(array $specResult): float
    {
        if ($specResult['status'] === SpecFilter::STATUS_HARD_FAIL) {
            return 0.0;
        }

        return $this->specFilter->similarityRatio($specResult);
    }

    /**
     * Distance: 0 km = 1.0, at radius limit = 0.0, linear decay.
     * When distance is unknown, return a neutral 0.5.
     */
    private function distanceScore(?float $distanceKm, ?string $urgency): float
    {
        if ($distanceKm === null) {
            return 0.5;
        }

        $radiusKm = $this->radiusForUrgency($urgency);

        if ($distanceKm <= 0) {
            return 1.0;
        }

        if ($distanceKm >= $radiusKm) {
            return 0.0;
        }

        return 1.0 - ($distanceKm / $radiusKm);
    }

    /**
     * Freshness: newer inquiries score higher.
     * Age decays linearly over 30 days.
     */
    private function freshnessScore(Inquiry $inquiry): float
    {
        $maxAgeDays = 30.0;
        $ageDays    = now()->diffInDays($inquiry->created_at, true);

        if ($ageDays >= $maxAgeDays) {
            return 0.0;
        }

        return 1.0 - ($ageDays / $maxAgeDays);
    }

    /**
     * Capacity: ratio of seller quantity to buyer quantity.
     * Extracted from SpecFilter details if available.
     */
    private function capacityScore(array $specResult): float
    {
        $qtyDetail = $specResult['details']['quantity'] ?? null;
        if (!$qtyDetail || ($qtyDetail['status'] ?? '') === 'SKIPPED') {
            return 0.5;
        }

        return $qtyDetail['similarity'] ?? 0.5;
    }

    // ---------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------

    private function weights(): array
    {
        return config('matchmaking.weights', [
            'spec'      => 45,
            'distance'  => 20,
            'activity'  => 15,
            'freshness' => 10,
            'capacity'  => 10,
        ]);
    }

    private function radiusForUrgency(?string $urgency): float
    {
        $isUrgent = strtolower((string) $urgency) === 'urgent';

        return $isUrgent
            ? (float) config('matchmaking.urgent_radius_km', 100)
            : (float) config('matchmaking.normal_radius_km', 50);
    }
}
