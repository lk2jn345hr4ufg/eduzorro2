<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\ListingReviewController;
use App\Http\Controllers\RegionLanguageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SportNewsController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// Global entry point: choose a region & language.
Route::get('/', [HomeController::class, 'index'])->name('home');

// XML sitemap (SEO).
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
 * Localized area.  URL shape:  /{region-slug}/{language-code}/...
 * The region.locale middleware resolves both models, sets the app locale,
 * and shares them (plus active regions/languages) with every view.
 *
 * NOTE: literal-prefixed routes (/search, /company) are registered BEFORE the
 * /{industry} wildcard so they win route matching.
 *
 * withoutScopedBindings() is required: because {language:code} uses a custom
 * key, Laravel would otherwise try to auto-scope it through a guessed
 * relationship on the previous model (Region::languages()), which doesn't
 * exist — region and language are independent, not parent/child.
 */
Route::prefix('{region:slug}/{language:code}')
    ->middleware('region.locale')
    ->withoutScopedBindings()
    ->group(function () {

        Route::get('/', [RegionLanguageController::class, 'index'])->name('region.home');

        Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
        Route::get('/search', [SearchController::class, 'results'])->name('search.results');

        Route::get('/company/{company:slug}', [CompanyController::class, 'show'])->name('company.show');
        Route::post('/company/{company:slug}/reviews', [ReviewController::class, 'store'])->name('review.store');

        // WordPress-imported content: 5 review-driven verticals + the
        // business registry. Registered before the /{industry} wildcard
        // below so these literal prefixes win route matching.
        Route::get('/directory/{vertical}', [DirectoryController::class, 'index'])->name('directory.index');
        Route::get('/directory/{vertical}/category/{categorySlug}', [DirectoryController::class, 'category'])->name('directory.category');
        Route::get('/directory/{vertical}/{listing:slug}', [DirectoryController::class, 'show'])->name('directory.show');
        Route::post('/directory/{vertical}/{listing:slug}/reviews', [ListingReviewController::class, 'store'])->name('directory.review.store');

        Route::get('/businesses', [BusinessController::class, 'index'])->name('business.index');
        Route::get('/businesses/{business:slug}', [BusinessController::class, 'show'])->name('business.show');

        /*
         * Sport section. Football has a deep hierarchy
         * (countries → teams → team hub with tabs); other sports are just
         * listed. Registered before the /{industry} wildcard so the literal
         * "sport" prefix wins route matching. Team tabs are constrained to a
         * known set so slugs can't leak into the {tab} segment.
         */
        Route::get('/sport', [SportController::class, 'index'])->name('sport.index');
        Route::get('/sport/news', [SportNewsController::class, 'index'])->name('sport.news.index');
        Route::get('/sport/news/{news:slug}', [SportNewsController::class, 'show'])->name('sport.news.show');
        Route::get('/sport/football', [FootballController::class, 'countries'])->name('sport.football.countries');
        Route::get('/sport/football/{country:slug}', [FootballController::class, 'teams'])->name('sport.football.country');
        Route::get('/sport/football/{country:slug}/{team:slug}', [TeamController::class, 'show'])->name('sport.team');
        Route::get('/sport/football/{country:slug}/{team:slug}/{tab}', [TeamController::class, 'show'])
            ->whereIn('tab', TeamController::TABS)
            ->name('sport.team.tab');
        Route::get('/sport/{sport:slug}', [SportController::class, 'show'])->name('sport.show');

        Route::get('/{industry:slug}', [IndustryController::class, 'show'])->name('industry.show');
        Route::get('/{industry:slug}/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
    });
