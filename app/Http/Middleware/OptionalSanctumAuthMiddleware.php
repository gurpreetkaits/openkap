<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Middleware that attempts to authenticate with Sanctum if credentials are present,
 * but allows the request to continue even if authentication fails.
 *
 * This is useful for endpoints that should work for both authenticated and
 * unauthenticated users, but want to identify authenticated users when possible.
 *
 * Security notes:
 * - Only attempts auth if credentials are present (bearer token or session cookie)
 * - Silently fails on invalid/expired tokens - does not expose auth errors
 * - Does not modify or bypass any existing security measures
 * - The endpoint must still handle unauthenticated users appropriately
 */
class OptionalSanctumAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only attempt authentication if credentials are present
        if ($request->bearerToken() || $request->cookie(config('session.cookie'))) {
            try {
                Auth::shouldUse('sanctum');
                // Attempt to resolve the user - this validates the token
                // but won't throw if invalid (just returns null)
                Auth::user();
            } catch (Throwable) {
                // Silently ignore any auth errors - treat as unauthenticated
                // This prevents information leakage about token validity
            }
        }

        return $next($request);
    }
}
