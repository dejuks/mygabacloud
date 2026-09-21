<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'seller' => \App\Http\Middleware\EnsureUserIsSeller::class,
        'admin'  => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);

    // Payment webhooks are signed by the gateway, not CSRF-protected —
    // Stripe/PayPal/NOWPayments can't send a CSRF token.
    $middleware->validateCsrfTokens(except: [
        'webhooks/*',
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
