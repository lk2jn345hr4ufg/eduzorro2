<?php

use App\Http\Controllers\Admin\ApiFootballController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FixtureController as AdminFixtureController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/* ---------------- Публичная часть ---------------- */
Route::get('/', [PublicController::class, 'home'])->name('home');

Route::get('/articles', [PublicController::class, 'posts'])->name('posts.index');
Route::get('/articles/{post}', [PublicController::class, 'postShow'])->name('posts.show');

Route::get('/news/{slug}', [PublicController::class, 'show'])->name('article.show');
Route::get('/category/{slug}', [PublicController::class, 'category'])->name('category');

Route::get('/fixtures/international', [PublicController::class, 'internationalFixtures'])
    ->name('fixtures.international');
Route::get('/fixtures', [PublicController::class, 'fixtures'])->name('fixtures');
Route::get('/table', [PublicController::class, 'table'])->name('table');
Route::get('/transfers', [PublicController::class, 'transfers'])->name('transfers');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/* ---------------- Админка ---------------- */
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('run/scrape-news', [DashboardController::class, 'scrapeNews'])->name('run.scrapeNews');
    Route::post('run/edit-pending', [DashboardController::class, 'editPending'])->name('run.editPending');
    Route::post('run/scrape-fixtures', [DashboardController::class, 'scrapeFixtures'])->name('run.scrapeFixtures');
    Route::post('run/sync-standings', [DashboardController::class, 'syncStandings'])->name('run.syncStandings');
    Route::post('run/sync-transfers', [DashboardController::class, 'syncTransfers'])->name('run.syncTransfers');
    Route::post('run/sync-euro', [DashboardController::class, 'syncEuro'])->name('run.syncEuro');

    Route::get('articles', [ArticleController::class, 'index'])->name('articles');
    Route::get('articles/{article:id}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::post('articles/{article:id}/ai-edit', [ArticleController::class, 'aiEdit'])->name('articles.aiEdit');
    Route::put('articles/{article:id}', [ArticleController::class, 'update'])->name('articles.update');
    Route::post('articles/{article:id}/publish', [ArticleController::class, 'publish'])->name('articles.publish');
    Route::post('articles/{article:id}/unpublish', [ArticleController::class, 'unpublish'])->name('articles.unpublish');
    Route::delete('articles/{article:id}', [ArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('posts', [PostController::class, 'index'])->name('posts');
    Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
    Route::post('posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('posts.unpublish');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('fixtures', [AdminFixtureController::class, 'index'])->name('fixtures');
    Route::get('fixtures/create', [AdminFixtureController::class, 'create'])->name('fixtures.create');
    Route::post('fixtures', [AdminFixtureController::class, 'store'])->name('fixtures.store');
    Route::get('fixtures/{fixture}/edit', [AdminFixtureController::class, 'edit'])->name('fixtures.edit');
    Route::put('fixtures/{fixture}', [AdminFixtureController::class, 'update'])->name('fixtures.update');
    Route::delete('fixtures/{fixture}', [AdminFixtureController::class, 'destroy'])->name('fixtures.destroy');

    Route::get('api-football', [ApiFootballController::class, 'index'])->name('apifootball');
    Route::put('api-football', [ApiFootballController::class, 'update'])->name('apifootball.update');
    Route::post('api-football/test', [ApiFootballController::class, 'test'])->name('apifootball.test');

    // SEO / мета-теги
    Route::get('seo', [SeoController::class, 'edit'])->name('seo');
    Route::put('seo', [SeoController::class, 'update'])->name('seo.update');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

/* ---------------- Breeze auth ---------------- */
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
