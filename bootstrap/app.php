<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/api-admin.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', \App\Http\Middleware\PreventSearchIndexing::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'auth.blocked' => \App\Http\Middleware\CheckBlockMiddleware::class,
            'company.context' => \App\Http\Middleware\EnsureCompanyContextMiddleware::class,
            'onboarding.signature' => \App\Http\Middleware\VerifyOnboardingSignature::class,
            'subscription.active' => \App\Http\Middleware\EnsureActiveSubscription::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/onboarding/create-user',
            'api/onboarding/update-user',
            'api/onboarding/status',
            'api/admin/subscriptions',
            'api/admin/subscriptions/*',
            'subscription/callback',
        ]);

        // Note: Single device login check is now included in CheckBlockMiddleware
        // No need to apply CheckSingleDeviceLogin globally anymore
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
