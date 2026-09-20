<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (File::exists(storage_path('installed.lock'))) {
            return $next($request);
        }

        // Let the installer's own routes, and static build assets, through —
        // everything else forces the visitor into the setup wizard.
        if ($request->is('install*') || $request->is('build/*') || $request->is('storage/*')) {
            return $next($request);
        }

        return redirect('/install');
    }
}
