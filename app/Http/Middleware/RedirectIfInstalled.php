<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Once installed.lock exists, the wizard is permanently retired —
        // it can rewrite the .env (database credentials, mail settings,
        // everything), so leaving it reachable after go-live would be a
        // serious open door for anyone who finds the URL.
        if (File::exists(storage_path('installed.lock'))) {
            return redirect('/')->with('error', 'This site is already installed.');
        }

        return $next($request);
    }
}
