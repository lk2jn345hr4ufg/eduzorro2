<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * For pages that are the same everywhere and therefore don't belong to a
 * region — e.g. the study tools at /{language}/tools.
 *
 * Deliberately does NOT share $currentRegion: the layout already guards with
 * @isset($currentRegion) and falls back to the global home, so the region
 * switcher is simply hidden on these pages.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $language = $request->route('language');

        if (! $language instanceof Language) {
            $language = Language::where('code', $language)->firstOrFail();
            $request->route()->setParameter('language', $language);
        }

        abort_unless($language->is_active, 404);

        app()->setLocale($language->code);

        View::share([
            'currentLanguage' => $language,
            'activeLanguages' => Language::active()->ordered()->get(),
        ]);

        return $next($request);
    }
}
