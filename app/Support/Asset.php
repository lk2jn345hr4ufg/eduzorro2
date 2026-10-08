<?php

namespace App\Support;

/**
 * Cache-busting asset URLs: asset('css/sport.css') + "?v=<mtime>".
 *
 * Cloudflare and browsers cache CSS aggressively; appending the file's
 * modification time makes every deploy fetch the new file automatically.
 * The public web root on the server may be a copy of public/ (see APPLY.md),
 * so both locations are checked; if neither is readable the app version is
 * used so the URL still changes between releases.
 */
class Asset
{
    public static function version(string $path): string
    {
        $path = ltrim($path, '/');

        $candidates = [public_path($path)];

        // Optional separate web root (config/app.php → public_docroot).
        if ($docroot = config('app.public_docroot')) {
            $candidates[] = rtrim($docroot, '/').'/'.$path;
        }

        $version = null;

        foreach ($candidates as $file) {
            if (is_file($file)) {
                $version = max((int) $version, (int) @filemtime($file));
            }
        }

        $version = $version ?: (string) config('app.asset_version', '1');

        return asset($path).'?v='.$version;
    }
}
