<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\PrerenderForBots;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureIsAdmin::class,
        ]);
        $middleware->web([
            RedirectIfNotInstalled::class,
            PrerenderForBots::class,
            CheckMaintenanceMode::class,
            SecurityHeaders::class,
        ]);
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson()) {
                return null;
            }

            return route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
