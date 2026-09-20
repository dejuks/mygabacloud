<?php

namespace App\Http\Middleware;

use App\Services\LicenseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class VerifyLicense
{
    public function handle(Request $request, Closure $next, LicenseManager $license): Response
    {
        // Only enforced once installed — during setup itself there's no
        // license yet, and the installer's own routes handle that state.
        if (! File::exists(storage_path('installed.lock'))) {
            return $next($request);
        }

        // Payment webhooks are never blocked by a license check, even an
        // invalid one — they're server-to-server, signature-verified
        // elsewhere, and carry real financial consequences (a paid order
        // failing to complete). The licensing feature is a soft deterrent
        // against casual copying, not worth risking a legitimate
        // customer's live orders over a routine infrastructure change that
        // shifted their fingerprint.
        if ($request->is('install*') || $request->is('webhooks/*')) {
            return $next($request);
        }

        // Recomputing the fingerprint hash on every request is cheap, but
        // there's no reason to do it more than once an hour.
        $valid = Cache::remember('license_valid', 3600, fn () => $license->isLicensed());

        if (! $valid) {
            return response()->view('install.license-invalid', [], 403);
        }

        return $next($request);
    }
}
