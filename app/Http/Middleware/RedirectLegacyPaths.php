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
     * Region segment: any word of 3+ characters that is not one of the app's
     * own top-level paths. Languages are always two letters, so a URL that
     * already starts with a language can never be mistaken for a region.
     */
    protected const REGION = '(?!(?:admin|livewire|filament|storage|build|css|js|images|fonts|vendor|api|up)/)[a-z0-9-]{3,}';

    /** Team tabs that existed under the old /sport/football/... URLs. */
    protected const OLD_TABS = '(news|fixtures|euro-cups|transfers|standings)';

    /**
     * Ordered: the first matching rule wins. Each targets the FINAL URL where
     * it can, so most old links resolve in a single 301.
     *
     * The site is football-only now: courses, business listings, industries
     * and region homes all fold into the language home page (last rule).
     */
    protected static function rules(): array
    {
        $r = self::REGION;
        $t = self::OLD_TABS;

        return [
            // Old team pages, with or without region, with or without a tab.
            "#^{$r}/([a-z]{2})/sport/football/[^/]+/([^/]+)/{$t}$#i" => '$1/team/$2/$3',
            "#^{$r}/([a-z]{2})/sport/football/[^/]+/([^/]+)$#i"        => '$1/team/$2',
            "#^([a-z]{2})/sport/football/[^/]+/([^/]+)/{$t}$#i"        => '$1/team/$2/$3',
            "#^([a-z]{2})/sport/football/[^/]+/([^/]+)$#i"               => '$1/team/$2',

            // Country page: needs a database lookup (country -> its league),
            // so it only drops the region here and the route finishes the job.
            "#^{$r}/([a-z]{2})/sport/football/([^/]+)$#i" => '$1/sport/football/$2',

            // News moved from /sport/news to /news.
            "#^{$r}/([a-z]{2})/sport/news(/.*)?$#i" => '$1/news$2',
            "#^([a-z]{2})/sport/news(/.*)?$#i"        => '$1/news$2',

            // Study tools keep their language-only URL.
            "#^{$r}/([a-z]{2})/tools(/.*)?$#i" => '$1/tools$2',

            // Sport landing pages and other sports -> home.
            "#^{$r}/([a-z]{2})/sport(/.*)?$#i"            => '$1',
            "#^([a-z]{2})/sport(/football)?$#i"              => '$1',
            "#^([a-z]{2})/sport/(?!football/|news)[^/]+$#i" => '$1',

            // Everything else that was region-scoped: region homes, courses,
            // business listings, directory, industries, categories, companies.
            "#^{$r}/([a-z]{2})(/.*)?$#i" => '$1',
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        foreach (self::rules() as $pattern => $replacement) {
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
