<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('seller.apply.form')
                ->with('error', 'You need a seller account to access that area.');
        }

        $profile = $user->sellerProfile;

        if (! $profile || $profile->application_status !== 'approved') {
            return redirect()->route('seller.apply.form')
                ->with('error', 'Your seller application is still under review.');
        }

        // Self-heal: is_seller is a denormalized convenience flag used
        // elsewhere for quick display checks, but the approved profile
        // above is the actual source of truth. If they've drifted apart —
        // approved directly in the database, a past bug, anything — fix
        // the flag here rather than blocking someone who is legitimately
        // approved.
        if (! $user->is_seller) {
            $user->update(['is_seller' => true]);
        }

        if ($user->status !== 'active') {
            abort(403, 'Your account has been suspended.');
        }

        return $next($request);
    }
}
