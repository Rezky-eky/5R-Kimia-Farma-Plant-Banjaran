<?php

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
        // Trust all proxies (untuk server di balik AWS/Nginx reverse proxy)
        // Ini memastikan paginator generate URL https:// yang benar
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Aliases for common route middleware
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
           'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'go_check.management' => \App\Http\Middleware\EnsureGoCheckManagement::class,
            'five_r.team' => \App\Http\Middleware\EnsureFiveRTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
