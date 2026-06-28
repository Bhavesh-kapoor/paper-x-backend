<?php

namespace App\Support\Notifications;

use App\Models\Inquiry;

/**
 * Builds clear, role- and intent-aware notification copy from an Inquiry, e.g.
 * "A dealer wants to buy Kappa Board" / "1,000 sheets · Normal. Tap to view & respond."
 *
 * Users are 50+ and the old generic "Matching Post Available" told them nothing about
 * what the post actually was. Used by every match-notification site so copy stays consistent.
 */
class InquiryNotificationCopy
{
    /** Responder-facing copy: what the matched user is being offered/asked. */
    public static function forResponder(Inquiry $inquiry): array
    {
        $inquiry->loadMissing(['items.material', 'materials', 'machines']);

        $type = self::enumValue($inquiry->inquiry_type) ?: 'material';
        $intent = self::enumValue($inquiry->intent) ?: 'buy';
        $role = self::posterRoleLabel($inquiry->poster_type);
        $urgency = self::urgencyLabel($inquiry->urgency);

        // Brand packaging requirements are stored as inquiry_type=job; handle by role first.
        if ($inquiry->poster_type === 'brand') {
            $packaging = trim((string) ($inquiry->packaging_type ?? ''));
            $requirement = trim((string) ($inquiry->requirement_type ?? ''));
            $title = 'A brand needs packaging' . ($packaging !== '' ? " — {$packaging}" : '');
            $detail = array_filter([
                $requirement !== '' ? $requirement : null,
                self::formatQty($inquiry->quantity, $inquiry->quantity_unit)
                    ?? (trim((string) $inquiry->quantity_range) !== '' ? self::formatRange($inquiry->quantity_range) . ' pcs' : null),
                $urgency,
            ]);
            return [
                'title' => $title,
                'body' => self::body($detail),
            ];
        }

        if ($type === 'machine') {
            $machine = self::machineName($inquiry);
            $verb = $intent === 'sell' ? 'sell' : 'buy';
            return [
                'title' => "{$role} wants to {$verb} a machine: {$machine}",
                'body' => self::body(array_filter([
                    self::stringOrNull($inquiry->machine_condition),
                    $urgency,
                ])),
            ];
        }

        if ($type === 'job' || $type === 'jobwork') {
            $jobType = self::jobType($inquiry);
            // intent buy = outsourcing work; intent sell = offering capacity
            $title = $intent === 'buy'
                ? "A converter needs {$jobType} job work"
                : "A converter is available for {$jobType} job work";
            return [
                'title' => $title,
                'body' => self::body(array_filter([
                    self::formatQty($inquiry->quantity, $inquiry->quantity_unit ?: 'pcs'),
                    $urgency,
                ])),
            ];
        }

        // Raw material
        $material = self::materialName($inquiry);
        $verb = $intent === 'sell' ? 'sell' : 'buy';
        return [
            'title' => "{$role} wants to {$verb} {$material}",
            'body' => self::body(array_filter([
                self::formatQty($inquiry->quantity, $inquiry->quantity_unit),
                $urgency,
            ])),
        ];
    }

    /** Poster-facing copy: matches available on their own post. */
    public static function forPoster(Inquiry $inquiry): array
    {
        $inquiry->loadMissing(['items.material', 'materials', 'machines']);
        $item = self::itemName($inquiry);

        return [
            'title' => "New matches for your {$item} post",
            'body' => 'Responders matching your post are available. Tap to view.',
        ];
    }

    // ---------------------------------------------------------------------------------

    private static function body(array $parts): string
    {
        $detail = implode(' · ', array_filter($parts, fn ($p) => $p !== null && $p !== ''));
        return trim($detail . ($detail !== '' ? '. ' : '') . 'Tap to view & respond.');
    }

    private static function posterRoleLabel(?string $posterType): string
    {
        return match ($posterType) {
            'dealer' => 'A dealer',
            'converter' => 'A converter',
            'brand' => 'A brand',
            'machine_dealer' => 'A machine dealer',
            default => 'Someone',
        };
    }

    private static function itemName(Inquiry $inquiry): string
    {
        $type = self::enumValue($inquiry->inquiry_type) ?: 'material';
        if ($type === 'machine') {
            return self::machineName($inquiry);
        }
        if (($type === 'job' || $type === 'jobwork') && $inquiry->poster_type !== 'brand') {
            return self::jobType($inquiry);
        }
        if ($inquiry->poster_type === 'brand') {
            $packaging = trim((string) ($inquiry->packaging_type ?? ''));
            return $packaging !== '' ? $packaging : 'packaging';
        }
        return self::materialName($inquiry);
    }

    private static function materialName(Inquiry $inquiry): string
    {
        $name = $inquiry->items->first()?->material?->name
            ?? $inquiry->materials->first()?->name;
        return $name ?: ($inquiry->title ?: 'material');
    }

    private static function machineName(Inquiry $inquiry): string
    {
        return $inquiry->machines->first()?->name
            ?? optional($inquiry->machineListing?->machine)->name
            ?? 'a machine';
    }

    private static function jobType(Inquiry $inquiry): string
    {
        $jobType = $inquiry->job_type
            ?? (is_array($inquiry->specs) ? ($inquiry->specs['jobwork_type'] ?? null) : null);
        return $jobType ? (string) $jobType : 'job';
    }

    private static function formatQty($qty, $unit): ?string
    {
        if ($qty === null) {
            return null;
        }
        $q = (float) $qty;
        if ($q <= 0) {
            return null;
        }
        $n = number_format($q, $q == floor($q) ? 0 : 2);
        $u = trim((string) $unit);
        return trim($n . ($u !== '' ? " {$u}" : ''));
    }

    /** "1000-5000" → "1,000–5,000" */
    private static function formatRange(?string $range): string
    {
        $parts = array_map('trim', explode('-', (string) $range));
        $parts = array_map(
            fn ($p) => is_numeric($p) ? number_format((float) $p, 0) : $p,
            array_filter($parts, fn ($p) => $p !== ''),
        );
        return implode('–', $parts);
    }

    private static function urgencyLabel(?string $urgency): string
    {
        return str_contains(strtolower((string) $urgency), 'urgent') ? 'Urgent' : 'Normal';
    }

    private static function stringOrNull($value): ?string
    {
        $s = trim((string) ($value ?? ''));
        return $s !== '' ? $s : null;
    }

    private static function enumValue($value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        return $value !== null ? (string) $value : null;
    }
}
