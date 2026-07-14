<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Models\Region;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetRegionAndLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $region   = $request->route('region');
        $language = $request->route('language');

        // Defensive: resolve if bindings arrived as raw strings.
        if (! $region instanceof Region) {
            $region = Region::where('slug', $region)->firstOrFail();
            $request->route()->setParameter('region', $region);
        }
        if (! $language instanceof Language) {
            $language = Language::where('code', $language)->firstOrFail();
            $request->route()->setParameter('language', $language);
        }

        abort_unless($region->is_active && $language->is_active, 404);

        // The language must actually be offered in this region (e.g. Kazakhstan
        // is Russian-only) — otherwise /kazakhstan/uk would silently "work"
        // despite there being no Ukrainian content there.
        abort_unless($region->languages()->whereKey($language->id)->exists(), 404);

        app()->setLocale($language->code);

        // Share globals every localized view/partial needs.
        View::share([
            'currentRegion'    => $region,
            'currentLanguage'  => $language,
            'activeRegions'    => Region::active()->ordered()->with('languages')->get(),
            'activeLanguages'  => $region->languages()->active()->ordered()->get(),
        ]);

        return $next($request);
    }
}
