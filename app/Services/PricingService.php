<?php

namespace App\Services;

use App\Models\Material;

/**
 * Computes the posting fee for an inquiry from the pricing model in config/pricing.php.
 *
 * Single source of truth for both the "pricing quote" endpoint (display) and the posting
 * flows (actual charge), so what the user sees equals what they're charged. All amounts are
 * in credits (1 credit = ₹1); GST is added on top of the base fee.
 */
class PricingService
{
    /**
     * @param array $specs {
     *   role: dealer|converter|brand|machineDealer,
     *   inquiry_type: material|machine|job,
     *   material_id?: int, material_name?: string, material_category?: string,
     *   thickness?: float, thickness_unit?: string (GSM|MM|…),
     *   size?: string ("WxL"), size_unit?: string (inches|cm|mm),
     *   quantity?: float, quantity_unit?: string,
     *   urgency?: string (normal|urgent),
     *   machine_price_range?: string (bracket key, e.g. "5_15l"),
     * }
     * @return array { base_fee, gst, gst_percent, total, currency, breakdown }
     */
    public function quote(array $specs): array
    {
        $cfg = config('pricing');
        $urgency = $this->normalizeUrgency($specs['urgency'] ?? 'normal');
        $inquiryType = strtolower((string) ($specs['inquiry_type'] ?? 'material'));
        $role = strtolower((string) ($specs['role'] ?? ''));

        // ---- Flow detection ----
        if ($inquiryType === 'machine') {
            return $this->finalize($this->machineFee($specs, $urgency, $cfg), $urgency, $cfg);
        }

        // Brand checked before jobwork: brand requirements are stored as inquiry_type=job,
        // but must be priced as brand packaging, not converter jobwork.
        if ($role === 'brand') {
            $base = $cfg['brand_flat'][$urgency];
            return $this->finalize([
                'base' => $base,
                'breakdown' => ['flow' => 'brand', 'urgency' => $urgency, 'reason' => 'Flat brand packaging fee'],
            ], $urgency, $cfg);
        }

        if ($inquiryType === 'job' || $inquiryType === 'jobwork') {
            $base = $cfg['jobwork_flat'][$urgency];
            return $this->finalize([
                'base' => $base,
                'breakdown' => ['flow' => 'jobwork', 'urgency' => $urgency, 'reason' => 'Flat jobwork fee'],
            ], $urgency, $cfg);
        }

        // ---- Raw material (dealer/converter buy/sell) ----
        return $this->finalize($this->rawMaterialFee($specs, $urgency, $cfg), $urgency, $cfg);
    }

    // --------------------------------------------------------------------------------------

    private function rawMaterialFee(array $specs, string $urgency, array $cfg): array
    {
        [$name, $category] = $this->resolveMaterial($specs);

        // Ancillary materials are flat, no formula.
        if ($category !== null && strcasecmp($category, $cfg['ancillary_category']) === 0) {
            return [
                'base' => $cfg['ancillary_flat'][$urgency],
                'breakdown' => ['flow' => 'ancillary', 'urgency' => $urgency, 'reason' => 'Flat ancillary fee'],
            ];
        }

        $band = $this->resolveValueBand($name, $cfg);
        $kg = $this->computeKg($specs, $cfg); // null when not derivable
        $bucketKey = $kg === null ? $cfg['fallback_bucket'] : $this->bucketForKg($kg, $cfg);
        $idx = $urgency === 'urgent' ? 1 : 0;
        $base = $cfg['value_band_grids'][$band][$bucketKey][$idx]
            ?? $cfg['value_band_grids'][$cfg['default_value_band']][$bucketKey][$idx];

        return [
            'base' => $base,
            'breakdown' => [
                'flow' => 'raw_material',
                'value_band' => $band,
                'kg' => $kg === null ? null : round($kg, 2),
                'bucket' => $bucketKey,
                'bucket_label' => $this->bucketLabel($bucketKey, $cfg),
                'kg_estimated' => $kg !== null,
                'urgency' => $urgency,
                'reason' => $kg === null
                    ? 'Weight not derivable from inputs; default bucket applied'
                    : 'Value band × quantity bucket',
            ],
        ];
    }

    private function machineFee(array $specs, string $urgency, array $cfg): array
    {
        $brackets = collect($cfg['machine_brackets'])->keyBy('key');
        $key = $specs['machine_price_range'] ?? null;
        $defaultApplied = false;

        if (!$key || !$brackets->has($key)) {
            $key = $cfg['machine_default_bracket'];
            $defaultApplied = true;
        }

        $bracket = $brackets->get($key);

        return [
            'base' => $bracket[$urgency],
            'breakdown' => [
                'flow' => 'machine',
                'machine_price_range' => $key,
                'price_range_label' => $bracket['label'],
                'default_applied' => $defaultApplied,
                'urgency' => $urgency,
                'reason' => $defaultApplied
                    ? 'No price range selected; defaulted to ' . $bracket['label']
                    : 'Machine price-range bracket',
            ],
        ];
    }

    // --------------------------------------------------------------------------------------

    /** Resolve [name, category] from explicit specs or by loading the Material. */
    private function resolveMaterial(array $specs): array
    {
        $name = $specs['material_name'] ?? null;
        $category = $specs['material_category'] ?? null;

        if ((!$name || !$category) && !empty($specs['material_id'])) {
            $material = Material::find($specs['material_id']);
            if ($material) {
                $name = $name ?: $material->name;
                $category = $category ?: $material->category;
            }
        }

        return [$name, $category];
    }

    private function resolveValueBand(?string $name, array $cfg): string
    {
        $key = strtolower(trim((string) $name));
        return $cfg['value_band_map'][$key] ?? $cfg['default_value_band'];
    }

    /** Returns total kg or null when it cannot be derived from the given units. */
    private function computeKg(array $specs, array $cfg): ?float
    {
        $qty = (float) ($specs['quantity'] ?? 0);
        if ($qty <= 0) {
            return null;
        }
        $unit = $this->normalizeUnit($specs['quantity_unit'] ?? '');

        // Direct-weight units.
        if (in_array($unit, ['kg', 'kgs'], true)) {
            return $qty;
        }
        if (in_array($unit, ['tonne', 'tonnes', 'ton', 'tons', 'mt'], true)) {
            return $qty * 1000;
        }
        // Mill board / Indian stiff board posted in bundles.
        if ($unit === 'bundles' || $unit === 'bundle') {
            return $qty * $cfg['kg_per_bundle'];
        }

        // Sheet-derived units need gsm + size.
        $sheets = null;
        if ($unit === 'sheets' || $unit === 'sheet') {
            $sheets = $qty;
        } elseif ($unit === 'reams' || $unit === 'ream') {
            $sheets = $qty * $cfg['sheets_per_ream'];
        } else {
            // reels / rolls / unknown → not weight-derivable.
            return null;
        }

        $gsm = $this->resolveGsm($specs, $cfg);
        $dims = $this->parseSizeInches($specs['size'] ?? null, $specs['size_unit'] ?? 'inches');
        if ($gsm === null || $dims === null) {
            return null;
        }
        [$wIn, $lIn] = $dims;

        // kg = gsm × W(in) × L(in) / 1550 / 1000 × sheets
        return $gsm * $wIn * $lIn / 1550 / 1000 * $sheets;
    }

    private function resolveGsm(array $specs, array $cfg): ?float
    {
        $thickness = (float) ($specs['thickness'] ?? 0);
        if ($thickness <= 0) {
            return null;
        }
        $unit = strtoupper(trim((string) ($specs['thickness_unit'] ?? 'GSM')));
        if ($unit === 'GSM') {
            return $thickness;
        }
        if ($unit === 'MM') {
            return $thickness * (float) $cfg['mm_to_gsm'];
        }
        // OUNCE / BF / MICRON not covered by the formula.
        return null;
    }

    /** Parse "WxL" and convert to inches. Returns [w, l] or null. */
    private function parseSizeInches(?string $size, ?string $sizeUnit): ?array
    {
        if (!$size) {
            return null;
        }
        $parts = preg_split('/x/i', trim($size));
        if (!$parts || count($parts) < 2) {
            return null;
        }
        $w = (float) trim($parts[0]);
        $l = (float) trim($parts[1]);
        if ($w <= 0 || $l <= 0) {
            return null;
        }
        $unit = strtolower(trim((string) ($sizeUnit ?: 'inches')));
        $factor = match ($unit) {
            'cm' => 1 / 2.54,
            'mm' => 1 / 25.4,
            default => 1.0, // inches
        };
        return [$w * $factor, $l * $factor];
    }

    private function bucketForKg(float $kg, array $cfg): string
    {
        foreach ($cfg['buckets'] as $bucket) {
            if ($bucket['max_kg'] === null || $kg <= $bucket['max_kg']) {
                return $bucket['key'];
            }
        }
        return end($cfg['buckets'])['key'];
    }

    private function bucketLabel(string $key, array $cfg): ?string
    {
        foreach ($cfg['buckets'] as $bucket) {
            if ($bucket['key'] === $key) {
                return $bucket['label'];
            }
        }
        return null;
    }

    private function normalizeUrgency(?string $urgency): string
    {
        return str_contains(strtolower((string) $urgency), 'urgent') ? 'urgent' : 'normal';
    }

    private function normalizeUnit(?string $unit): string
    {
        $u = strtolower(trim((string) $unit));
        // strip apostrophes/spaces e.g. "kg's" → "kgs"
        return str_replace(["'", '’', ' '], '', $u);
    }

    /** Apply GST and shape the final response. */
    private function finalize(array $result, string $urgency, array $cfg): array
    {
        $base = (int) round($result['base']);
        $gstPercent = (int) $cfg['gst_percent'];
        $gst = (int) round($base * $gstPercent / 100);
        $total = $base + $gst;

        return [
            'base_fee' => $base,
            'gst' => $gst,
            'gst_percent' => $gstPercent,
            'total' => $total,
            'currency' => 'credits',
            'breakdown' => $result['breakdown'],
        ];
    }
}
