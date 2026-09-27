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
    // ->withMiddleware(function (Middleware $middleware): void {
    //     //
    // })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
                'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
                'permission' => \App\Http\Middleware\EnsurePermission::class,
                'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
