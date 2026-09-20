# Register the middleware aliases

## Laravel 11 / 12 — edit `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'seller'    => \App\Http\Middleware\EnsureUserIsSeller::class,
        'admin'     => \App\Http\Middleware\EnsureUserIsAdmin::class,
        'installed' => \App\Http\Middleware\RedirectIfInstalled::class,
    ]);

    // These two run on EVERY request, in this order:
    // 1. Force the visitor into the install wizard until it's actually run.
    // 2. Once installed, re-verify the server-locked license on every request.
    $middleware->append(\App\Http\Middleware\RedirectIfNotInstalled::class);
    $middleware->append(\App\Http\Middleware\VerifyLicense::class);

    // Payment webhooks are signed by the gateway, not CSRF-protected
    $middleware->validateCsrfTokens(except: [
        'webhooks/*',
    ]);
})
```

## Laravel 10 — edit `app/Http/Kernel.php`

```php
protected $middleware = [
    // ...existing global middleware...
    \App\Http\Middleware\RedirectIfNotInstalled::class,
    \App\Http\Middleware\VerifyLicense::class,
];

protected $middlewareAliases = [
    // ...existing...
    'seller'    => \App\Http\Middleware\EnsureUserIsSeller::class,
    'admin'     => \App\Http\Middleware\EnsureUserIsAdmin::class,
    'installed' => \App\Http\Middleware\RedirectIfInstalled::class,
];
```

And add `'webhooks/*'` to `$except` in `app/Http/Middleware/VerifyCsrfToken.php`.

## Before your very first deploy

Make sure `storage/installed.lock` does **not** exist in whatever you upload —
if it does, the wizard will refuse to run (it thinks setup already happened).
If you've been testing locally with this same codebase, delete that file
before handing the code to someone else / deploying fresh.
