<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Structural 301s for sections that moved out of the /{region}/{language}
 * prefix (sport, tools).
 *
 * The route-level legacy redirects only fire when the region segment still
 * resolves to a live Region model. Anything pointing at a region that was
 * renamed, deactivated or deleted would 404 instead — losing the link. This
 * runs before routing and works on the raw path, so every old URL is covered
 * regardless of what happened to the region.
 *
 * Rules are patterns, not a table: one line covers every region/language/slug
 * combination, which is why this lives in code rather than the redirects admin.
 */
class RedirectLegacyPaths
{
    /**
     * Each rule: a regex over the path (no leading slash) and its replacement.
     *
     *   ukraine/ru/sport/football/england/liverpool/standings
     *   -> ru/sport/football/england/liverpool/standings
     *
     * The language group is two letters, so a path that already starts with a
     * language ("ru/sport/...") can never match and won't loop.
     */
    protected const RULES = [
        // /{region}/{language}/sport/... -> /{language}/sport/...
        '#^[a-z0-9-]+/([a-z]{2})/(sport)(/.*)?$#i' => '$1/$2$3',

        // /{region}/{language}/tools/... -> /{language}/tools/...
        '#^[a-z0-9-]+/([a-z]{2})/(tools)(/.*)?$#i' => '$1/$2$3',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        foreach (self::RULES as $pattern => $replacement) {
            if (! preg_match($pattern, $path)) {
                continue;
            }

            $target = preg_replace($pattern, $replacement, $path);

            // Guard against a rule that would redirect a path onto itself.
            if ($target === $path) {
                break;
            }

            $query = $request->getQueryString();

            return redirect('/'.$target.($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
