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
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'auth.blocked' => \App\Http\Middleware\CheckBlockMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/public/leads',
            'api/onboarding/send-otp',
            'api/onboarding/verify-otp',
            'api/onboard',
        ]);

        // Note: Single device login check is now included in CheckBlockMiddleware
        // No need to apply CheckSingleDeviceLogin globally anymore
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
