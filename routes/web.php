<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\RegionLanguageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
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
        Route::get('/directory/{vertical}/{listing:slug}', [DirectoryController::class, 'show'])->name('directory.show');

        Route::get('/businesses', [BusinessController::class, 'index'])->name('business.index');
        Route::get('/businesses/{business:slug}', [BusinessController::class, 'show'])->name('business.show');

        Route::get('/{industry:slug}', [IndustryController::class, 'show'])->name('industry.show');
        Route::get('/{industry:slug}/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
    });
