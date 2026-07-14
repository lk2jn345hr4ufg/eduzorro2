<?php

use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\SetRegionAndLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs before routing, so it also catches retired URLs that no
        // longer match any route (the main 301-redirect use case).
        $middleware->prepend(HandleRedirects::class);

        // Alias used by the localized route group in routes/web.php.
        $middleware->alias([
            'region.locale' => SetRegionAndLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
