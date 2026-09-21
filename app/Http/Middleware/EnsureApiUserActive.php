<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * users.status can be active / suspended / banned. Blocks the last two on API calls
 * (login checks it too, this covers tokens issued before the account was suspended).
 */
class EnsureApiUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $status = $user->status ?? 'active';
            $status = $status instanceof \BackedEnum ? $status->value : $status;

            if ($status !== 'active') {
                return response()->json([
                    'message' => 'Your account is not active. Please contact support.',
                    'code'    => 'account_inactive',
                ], 403);
            }
        }

        return $next($request);
    }
}
