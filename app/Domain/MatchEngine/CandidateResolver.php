<?php

namespace App\Domain\MatchEngine;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
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
        $query = $this->buildCandidateQuery($inquiry);
        if ($query === null) {
            return collect();
        }

        $candidates = $query->get();

        return $this->applyRadius($inquiry, $candidates)->values();
    }

    /**
     * True if the single given user passes the same hard filters resolve() applies,
     * WITHOUT loading the entire candidate set. Used for per-user evaluation so we
     * don't resolve the whole universe just to test one user.
     */
    public function isEligible(Inquiry $inquiry, User $user): bool
    {
        $query = $this->buildCandidateQuery($inquiry);
        if ($query === null) {
            return false;
        }

        // Same SQL hard filters, scoped to this one user.
        if (! (clone $query)->where('users.id', $user->id)->exists()) {
            return false;
        }

        // Same radius rule as resolve(), scoped to this one user.
        return $this->applyRadius($inquiry, collect([$user]))->isNotEmpty();
    }

    /**
     * Build the candidate User query with all hard filters applied (visibility,
     * poster-exclusion, material/brand/jobwork). Returns null when the inquiry is
     * expired/locked or has no eligible roles. Radius is applied separately via
     * applyRadius() so the query can be reused for single-user eligibility checks.
     */
    private function buildCandidateQuery(Inquiry $inquiry): ?Builder
    {
        if ($this->isExpired($inquiry)) {
            return null;
        }

        if ($this->isLocked($inquiry)) {
            return null;
        }

        $eligibleRoles = $this->rolesForVisibility($inquiry->visibility);

        if (empty($eligibleRoles)) {
            return null;
        }

        // Exclude the poster: poster_id is the role entity id (e.g. converter_id), not user_id
        $inquiry->loadMissing('poster');
        $posterUserId = $inquiry->poster?->user_id ?? null;
        $query = User::query()
            ->whereIn('primary_role', $eligibleRoles);
        if ($posterUserId !== null) {
            $query->where('id', '!=', $posterUserId);
        } else {
            $query->where('id', '!=', $inquiry->poster_id);
        }

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

        $inquiryMaterialIds = $inquiry->materials()->pluck('materials.id')->toArray();
        if (!empty($inquiryMaterialIds)) {
            $this->applyMaterialFilter($query, $eligibleRoles, $inquiryMaterialIds);
        }

        // Brand → Converter: apply capability filter when there are no material-driven specs
        if (empty($inquiryMaterialIds) && $inquiry->poster_type === 'brand' && in_array('converter', $eligibleRoles, true)) {
            $this->applyBrandRequirementFilter($query, $inquiry);
        }

        // Converter → Converter jobwork: narrow to converters with relevant job-work capabilities.
        if (
            $inquiry->inquiry_type === InquiryType::JOB
            && $inquiry->poster_type === 'converter'
            && in_array('converter', $eligibleRoles, true)
        ) {
            $this->applyJobworkFilter($query, $inquiry);
        }

        return $query;
    }

    /**
     * Apply the radius (Haversine) filter to a candidate collection — identical
     * semantics to the original resolve(): skipped for converter→converter jobwork,
     * when location matching is disabled, or when the inquiry has no coordinates.
     */
    private function applyRadius(Inquiry $inquiry, Collection $candidates): Collection
    {
        // Skip radius filter for converter-to-converter JOB (jobwork) so converters without
        // factory location or in different regions can still see find/give jobwork posts.
        $isJobworkInquiry = $inquiry->inquiry_type === InquiryType::JOB
            && $inquiry->poster_type === 'converter';

        if (!$this->shouldApplyRadius() || $isJobworkInquiry) {
            return $candidates;
        }

        $inquiryLat = $inquiry->latitude;
        $inquiryLng = $inquiry->longitude;

        if (!$inquiryLat || !$inquiryLng) {
            return $candidates;
        }

        $radiusKm = $this->radiusForUrgency($inquiry->urgency);

        return $candidates->filter(function (User $user) use ($inquiryLat, $inquiryLng, $radiusKm) {
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

    /**
     * Brand inquiries: narrow converters by their capabilities when no material specs exist.
     *
     * Uses converter_types.category and finished_products.category to approximate
     * whether a converter is relevant for the brand's requirement_type/packaging_type.
     */
    private function applyBrandRequirementFilter(Builder $query, Inquiry $inquiry): void
    {
        $requirementType = (string) $inquiry->requirement_type;
        $packagingType = (string) ($inquiry->packaging_type ?? '');

        $converterCategories = $this->mapRequirementTypeToConverterCategories($requirementType);
        $productCategories = $this->mapPackagingTypeToFinishedProductCategories($packagingType);

        // If we have no mapping at all, do not restrict converters.
        if (empty($converterCategories) && empty($productCategories)) {
            return;
        }

        $query->where(function (Builder $q) use ($converterCategories, $productCategories) {
            $q->where('primary_role', 'converter')
                ->whereHas('converter', function (Builder $sub) use ($converterCategories, $productCategories) {
                    $sub->where(function (Builder $inner) use ($converterCategories, $productCategories) {
                        // Match on converter_types.category
                        if (!empty($converterCategories)) {
                            $inner->orWhereHas('converterTypes', function (Builder $ct) use ($converterCategories) {
                                $ct->whereIn('category', $converterCategories);
                            });
                        }

                        // Match on finished_products.category
                        if (!empty($productCategories)) {
                            $inner->orWhereHas('finishedProducts', function (Builder $fp) use ($productCategories) {
                                $fp->whereIn('category', $productCategories);
                            });
                        }
                    });
                });
        });
    }

    private function mapRequirementTypeToConverterCategories(string $requirementType): array
    {
        $normalized = trim(strtolower($requirementType));

        return match ($normalized) {
            'packaging' => [
                'corrugated',
                'rigid',
                'folding_carton',
                'paper_bags',
                'food_service',
                'industrial',
                'tubes_cores',
            ],
            'printing' => [
                'printing',
                'books_stationery',
            ],
            'packaging + printing', 'packaging+printing' => [
                'corrugated',
                'rigid',
                'folding_carton',
                'paper_bags',
                'food_service',
                'printing',
            ],
            'corporate gifting / stationery',
            'corporate gifting/stationery',
            'corporate gifting & stationery' => [
                'rigid',
                'books_stationery',
                'labels',
                'premium',
            ],
            default => [],
        };
    }

    private function mapPackagingTypeToFinishedProductCategories(string $packagingType): array
    {
        $normalized = trim(strtolower($packagingType));

        return match ($normalized) {
            'boxes' => ['packaging', 'premium', 'food_beverage'],
            'bags', 'paper bags' => ['paper_bags', 'food_beverage'],
            'pouches' => ['paper_bags', 'food_beverage'],
            'cartons', 'mono cartons', 'folding cartons' => ['packaging', 'food_beverage'],
            'containers' => ['food_beverage', 'industrial'],
            default => [],
        };
    }

    /**
     * Converter → Converter jobwork matching.
     *
     * Any active converter with a complete profile is a valid candidate for
     * jobwork find/give posts. The frontend now sends the actual converter
     * type name as job_type (not a fixed enum), so hard-filtering by category
     * would exclude valid candidates. Scoring handles relevance ranking.
     */
    private function applyJobworkFilter(Builder $query, Inquiry $inquiry): void
    {
        $query->where(function (Builder $q) {
            $q->where('primary_role', 'converter')
                ->whereHas('converter', function (Builder $sub) {
                    $sub->where('profile_complete', true)
                        ->where('status', \App\Enums\ConverterStatus::ACTIVE);
                });
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
