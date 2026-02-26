<?php

namespace App\Domain\MatchEngine;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CandidateResolver
{
    /**
     * Return eligible candidate users for a given inquiry.
     *
     * Pipeline: visibility → self-exclusion → expiry → lock → material → radius.
     * No scoring happens here — only hard pass/fail filtering.
     */
    public function resolve(Inquiry $inquiry): Collection
    {
        if ($this->isExpired($inquiry)) {
            return collect();
        }

        if ($this->isLocked($inquiry)) {
            return collect();
        }

        $eligibleRoles = $this->rolesForVisibility($inquiry->visibility);

        if (empty($eligibleRoles)) {
            return collect();
        }

        $inquiryMaterialIds = $inquiry->materials()->pluck('materials.id')->toArray();

        $query = User::query()
            ->where('id', '!=', $inquiry->poster_id)
            ->whereIn('primary_role', $eligibleRoles);

        // Eager-load role relations needed for location resolution to avoid N+1 queries
        $relations = [];
        if (in_array('dealer', $eligibleRoles, true)) {
            $relations[] = 'dealer.locations';
        }
        if (in_array('converter', $eligibleRoles, true)) {
            $relations[] = 'converter';
        }
        if (in_array('machine_dealer', $eligibleRoles, true)) {
            $relations[] = 'machineDealer';
        }
        if (!empty($relations)) {
            $query->with($relations);
        }

        if (!empty($inquiryMaterialIds)) {
            $this->applyMaterialFilter($query, $eligibleRoles, $inquiryMaterialIds);
        }

        $candidates = $query->get();

        if ($this->shouldApplyRadius()) {
            $radiusKm = $this->radiusForUrgency($inquiry->urgency);
            $inquiryLat = $inquiry->latitude;
            $inquiryLng = $inquiry->longitude;

            if ($inquiryLat && $inquiryLng) {
                $candidates = $candidates->filter(function (User $user) use ($inquiryLat, $inquiryLng, $radiusKm) {
                    $location = $this->getOperationalLocation($user);
                    if (!$location) {
                        return false;
                    }
                    [$lat, $lng] = $location;
                    if (!$lat || !$lng) {
                        return false;
                    }
                    return $this->haversineKm($inquiryLat, $inquiryLng, $lat, $lng) <= $radiusKm;
                });
            }
        }

        return $candidates->values();
    }

    /**
     * Compute the Haversine distance (km) between the inquiry and a candidate.
     * Returns null when either side has no coordinates.
     */
    public function distanceBetween(Inquiry $inquiry, User $user): ?float
    {
        $inquiryLat = $inquiry->latitude;
        $inquiryLng = $inquiry->longitude;

        if (!$inquiryLat || !$inquiryLng) {
            return null;
        }

        $location = $this->getOperationalLocation($user);
        if (!$location) {
            return null;
        }

        [$lat, $lng] = $location;
        if (!$lat || !$lng) {
            return null;
        }

        return $this->haversineKm($inquiryLat, $inquiryLng, $lat, $lng);
    }

    // ---------------------------------------------------------------
    //  Guard clauses
    // ---------------------------------------------------------------

    private function isExpired(Inquiry $inquiry): bool
    {
        return $inquiry->expires_at !== null && $inquiry->expires_at->isPast();
    }

    private function isLocked(Inquiry $inquiry): bool
    {
        if ($inquiry->status === InquiryStatus::LOCKED) {
            return true;
        }

        if ($inquiry->locked_at !== null) {
            return true;
        }

        return false;
    }

    // ---------------------------------------------------------------
    //  Visibility → role mapping
    // ---------------------------------------------------------------

    private function rolesForVisibility(?string $visibility): array
    {
        return match ($visibility) {
            'dealers'         => ['dealer'],
            'converters'      => ['converter'],
            // Machine dealer visibility: support both snake_case and camelCase role strings
            'machine_dealers' => ['machine_dealer', 'machineDealer', 'machine-dealer'],
            // When visible to "all" in machine flows, include both variants as well
            'all'             => ['dealer', 'converter', 'machine_dealer', 'machineDealer', 'machine-dealer'],
            default           => [],
        };
    }

    // ---------------------------------------------------------------
    //  Material pre-filter (SQL-based to avoid N+1)
    // ---------------------------------------------------------------

    /**
     * Apply material filter in SQL to avoid N+1.
     * Dealers must have at least one matching material; converters via rawMaterials.
     * Machine_dealers have no material relation, so they are excluded when the
     * inquiry has materials (they do not satisfy either branch).
     */
    private function applyMaterialFilter(Builder $query, array $eligibleRoles, array $inquiryMaterialIds): void
    {
        $query->where(function ($q) use ($eligibleRoles, $inquiryMaterialIds) {
            if (in_array('dealer', $eligibleRoles)) {
                $q->orWhere(function ($q) use ($inquiryMaterialIds) {
                    $q->where('primary_role', 'dealer')
                        ->whereHas('dealer', fn ($sub) => $sub->whereHas(
                            'materials',
                            fn ($m) => $m->whereIn('materials.id', $inquiryMaterialIds)
                        ));
                });
            }
            if (in_array('converter', $eligibleRoles)) {
                $q->orWhere(function ($q) use ($inquiryMaterialIds) {
                    $q->where('primary_role', 'converter')
                        ->whereHas('converter', fn ($sub) => $sub->whereHas(
                            'rawMaterials',
                            fn ($m) => $m->whereIn('materials.id', $inquiryMaterialIds)
                        ));
                });
            }
            if (! in_array('dealer', $eligibleRoles) && ! in_array('converter', $eligibleRoles)) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    // ---------------------------------------------------------------
    //  Radius helpers
    // ---------------------------------------------------------------

    private function shouldApplyRadius(): bool
    {
        return (bool) config('matchmaking.use_location_in_matching', true);
    }

    private function radiusForUrgency(?string $urgency): float
    {
        $isUrgent = strtolower((string) $urgency) === 'urgent';

        return $isUrgent
            ? (float) config('matchmaking.urgent_radius_km', 100)
            : (float) config('matchmaking.normal_radius_km', 50);
    }

    // ---------------------------------------------------------------
    //  Location resolution
    // ---------------------------------------------------------------

    /**
     * Resolve the operational [lat, lng] for a user based on their role.
     *
     * Users without any location are excluded from radius filtering (and thus
     * from matching when use_location_in_matching is true). Ensure location is
     * required at role registration, or that profiles have a fallback location.
     *
     * - Dealer → warehouse location (first warehouse; fallback: first location of any type)
     * - Converter → factory_latitude / factory_longitude
     * - Brand → latitude / longitude on brand profile
     * - MachineDealer → latitude / longitude on profile
     *
     * Note: Dealer currently uses first warehouse. Post-level selected warehouse
     * (when dealer is poster) to be aligned in a later phase.
     */
    private function getOperationalLocation(User $user): ?array
    {
        return match ($user->primary_role) {
            'dealer'         => $this->dealerWarehouseLocation($user),
            'converter'      => $this->converterLocation($user),
            'brand'          => $this->brandLocation($user),
            // Support both legacy snake_case and current camelCase / hyphenated variants
            'machine_dealer',
            'machineDealer',
            'machine-dealer' => $this->machineDealerLocation($user),
            default          => null,
        };
    }

    /**
     * Dealer: prefer first warehouse; if none, fallback to first location of any type
     * so users with a location but not typed as warehouse are not silently dropped.
     */
    private function dealerWarehouseLocation(User $user): ?array
    {
        $dealer = $user->dealer;
        if (!$dealer) {
            return null;
        }

        // Use already eager-loaded locations when available to avoid per-user queries
        $locations = $dealer->relationLoaded('locations')
            ? $dealer->locations
            : $dealer->locations()->get();

        $location = $locations->firstWhere('type', 'warehouse')
            ?? $locations->first();

        if (!$location || $location->latitude === null || $location->longitude === null) {
            return null;
        }

        return [(float) $location->latitude, (float) $location->longitude];
    }

    private function converterLocation(User $user): ?array
    {
        $converter = $user->converter;
        if (!$converter) {
            return null;
        }
        return [$converter->factory_latitude, $converter->factory_longitude];
    }

    private function brandLocation(User $user): ?array
    {
        $brand = $user->brand;
        if (!$brand) {
            return null;
        }
        return [$brand->latitude, $brand->longitude];
    }

    private function machineDealerLocation(User $user): ?array
    {
        $md = $user->machineDealer;
        if (!$md) {
            return null;
        }
        return [$md->latitude, $md->longitude];
    }

    // ---------------------------------------------------------------
    //  Haversine
    // ---------------------------------------------------------------

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
