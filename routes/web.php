<?php

use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\FootballHomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SportNewsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ToolAiController;
use App\Http\Controllers\ToolController;
use App\Models\Competition;
use App\Models\SportCountry;
use Illuminate\Support\Facades\Route;

/*
 * Football-only site. URL shape:
 *
 *   /                                   -> redirect to the default language home
 *   /{language}                         home dashboard
 *   /{language}/news[/{news}]           team news feed / article
 *   /{language}/team/{team}[/{tab}]     team page
 *   /{language}/{competition}[/{tab}]   championship or tournament page
 *
 * The old region-scoped site (courses, business listings, directory,
 * industries) is retired: every such URL is 301-redirected to the language
 * home by App\Http\Middleware\RedirectLegacyPaths, which runs before routing.
 */

Route::get('/', [FootballHomeController::class, 'root'])->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
 * {language} is constrained to two letters so it can never swallow an old
 * region slug, and parameters after it carry no explicit key ({team}, not
 * {team:slug}): the models declare getRouteKeyName() = 'slug', and an explicit
 * key after another bound parameter would make Laravel try to scope the child
 * through a relationship that doesn't exist.
 */
Route::prefix('{language:code}')
    ->where(['language' => '[a-z]{2}'])
    ->middleware('locale')
    ->withoutScopedBindings()
    ->group(function () {
        Route::get('/', [FootballHomeController::class, 'index'])->name('football.home');

        Route::get('/news', [SportNewsController::class, 'index'])->name('sport.news.index');
        Route::get('/news/{news}', [SportNewsController::class, 'show'])->name('sport.news.show');

        Route::get('/team/{team}', [TeamController::class, 'show'])->name('sport.team');
        Route::get('/team/{team}/{tab}', [TeamController::class, 'show'])
            ->whereIn('tab', TeamController::TABS)
            ->name('sport.team.tab');

        // Study tools stay reachable at their existing URLs, but are no longer
        // linked from the football site.
        Route::get('/tools', [ToolController::class, 'index'])->name('tools.index');
        Route::get('/tools/{tool}', [ToolController::class, 'show'])->name('tools.show');
        Route::post('/tools/video-notes/generate', [ToolAiController::class, 'videoNotes'])
            ->name('tools.video-notes.generate');

        // Old country page -> that country's championship (needs a lookup,
        // so it can't be a plain pattern in the redirect middleware).
        Route::get('/sport/football/{countrySlug}', function ($language, string $countrySlug) {
            $country = SportCountry::where('slug', $countrySlug)->first();
            $league  = $country
                ? Competition::leagues()->active()->where('sport_country_id', $country->id)->first()
                : null;

            return $league
                ? redirect()->route('competition.show', [$language, $league], 301)
                : redirect()->route('football.home', [$language], 301);
        })->name('legacy.country');

        // Must stay last: a bare slug after the language is a competition.
        Route::get('/{competition}', [CompetitionController::class, 'show'])->name('competition.show');
        Route::get('/{competition}/{tab}', [CompetitionController::class, 'show'])
            ->whereIn('tab', CompetitionController::TABS)
            ->name('competition.tab');
    });
