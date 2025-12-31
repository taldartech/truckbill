<?php

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
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'checkblock' => \App\Http\Middleware\CheckBlockMiddleware::class,
            'single.device' => \App\Http\Middleware\CheckSingleDeviceLogin::class,
        ]);
        
        // Apply single device login check to web middleware group
        // The middleware itself checks if user is authenticated, so it's safe for all routes
        $middleware->web(append: [
            \App\Http\Middleware\CheckSingleDeviceLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
