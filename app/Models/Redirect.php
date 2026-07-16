<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    /** Cache key the HandleRedirects middleware reads from on every request. */
    public const CACHE_KEY = 'redirects.active';

    protected $guarded = [];

    protected $casts = [
        'status_code' => 'integer',
        'is_active'   => 'boolean',
        'hits'        => 'integer',
        'last_hit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Keep the middleware's cached lookup table in sync with edits.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Normalize an incoming ("from") path: strip a scheme+host if one was
     * pasted by mistake, force a single leading slash, collapse repeated
     * slashes, and drop any trailing slash (except the root itself).
     */
    public static function normalizePath(string $path): string
    {
        $path = trim($path);

        if (preg_match('#^https?://[^/]+(/.*)?$#i', $path, $m)) {
            $path = $m[1] ?? '/';
        }

        // Old WordPress slugs percent-encode Cyrillic (%d1%94 = "є");
        // browsers send them encoded. Compare everything decoded.
        $path = rawurldecode($path);

        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/{2,}#', '/', $path);

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Normalize a "to" target: just trims whitespace. Deliberately not as
     * strict as normalizePath() because targets may legitimately be
     * absolute external URLs (https://example.com/...).
     */
    public static function normalizeTarget(string $target): string
    {
        return trim($target);
    }
}
