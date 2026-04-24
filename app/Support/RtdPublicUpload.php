<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * RTD uploads under public/ (no storage symlink). DB stores relative paths like uploads/rtd/products/….
 */
final class RtdPublicUpload
{
    public const BASE = 'uploads/rtd';

    public const DIR_PRODUCTS = 'products';

    public const DIR_LOGOS = 'logos';

    public const DIR_DISPATCH = 'dispatch';

    public static function store(UploadedFile $file, string $directory): string
    {
        $allowed = [self::DIR_PRODUCTS, self::DIR_LOGOS, self::DIR_DISPATCH];
        if (! in_array($directory, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid RTD upload directory');
        }

        $relativeDir = self::BASE.'/'.$directory;
        $absoluteDir = public_path($relativeDir);
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $name = Str::uuid()->toString().'.'.$ext;
        $file->move($absoluteDir, $name);

        return $relativeDir.'/'.$name;
    }

    public static function publicUrl(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $stored = trim(str_replace('\\', '/', $stored));
        if (str_contains($stored, '..')) {
            return null;
        }

        if (preg_match('#^https?://#i', $stored)) {
            return self::publicUrlFromAbsolute($stored);
        }

        return self::publicUrlFromRelative($stored);
    }

    /**
     * Normalize client or legacy values to a relative path for DB, or null if invalid / unmappable.
     */
    public static function normalizeProductImagePathForDb(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim(str_replace('\\', '/', $value));
        if (str_contains($value, '..')) {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            if ($parts === false) {
                return null;
            }
            $path = urldecode($parts['path'] ?? '');

            if (preg_match('#/storage/(.+)$#', $path, $m)) {
                $value = $m[1];
            } elseif (preg_match('#/('.self::BASE.'/.+)$#', $path, $m)) {
                $value = $m[1];
            } elseif (preg_match('#/(uploads/images/.+)$#', $path, $m)) {
                $value = $m[1];
            } else {
                return null;
            }
        }

        $path = ltrim($value, '/');

        return self::isAllowedProductImagePath($path) ? $path : null;
    }

    public static function normalizeDispatchFilePathForDb(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim(str_replace('\\', '/', $value));
        if (str_contains($value, '..')) {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            if ($parts === false) {
                return null;
            }
            $path = urldecode($parts['path'] ?? '');
            if (preg_match('#/storage/(.+)$#', $path, $m)) {
                $value = $m[1];
            } elseif (preg_match('#/('.self::BASE.'/'.self::DIR_DISPATCH.'/.+)$#', $path, $m)) {
                return $m[1];
            } else {
                return null;
            }
        }

        $path = ltrim($value, '/');

        return self::isAllowedDispatchPath($path) ? $path : null;
    }

    public static function isAllowedProductImagePath(string $path): bool
    {
        return str_starts_with($path, self::BASE.'/'.self::DIR_PRODUCTS.'/')
            || str_starts_with($path, 'uploads/images/');
    }

    public static function isAllowedDispatchPath(string $path): bool
    {
        return str_starts_with($path, self::BASE.'/'.self::DIR_DISPATCH.'/');
    }

    private static function publicUrlFromAbsolute(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $path = urldecode($parts['path'] ?? '');

        if (preg_match('#/storage/(.+)$#', $path, $m)) {
            return asset($m[1]);
        }

        if (preg_match('#/('.self::BASE.'/.+)$#', $path, $m)) {
            return asset($m[1]);
        }

        if (preg_match('#/(uploads/images/.+)$#', $path, $m)) {
            return asset($m[1]);
        }

        return $url;
    }

    private static function publicUrlFromRelative(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (str_starts_with($path, self::BASE.'/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'uploads/images/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'rtd/logos/')) {
            return asset(self::BASE.'/'.self::DIR_LOGOS.'/'.basename($path));
        }

        if (str_starts_with($path, 'storage/')) {
            return asset(substr($path, strlen('storage/')));
        }

        return null;
    }
}
