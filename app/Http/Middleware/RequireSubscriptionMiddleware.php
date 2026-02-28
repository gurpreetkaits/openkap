<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSubscriptionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'Authentication required.',
            ], 401);
        }

        if (! $user->hasActiveSubscription()) {
            return response()->json([
                'error' => 'subscription_required',
                'message' => 'An active subscription is required to use integrations.',
                'upgrade_url' => config('services.frontend.url').'/subscription',
            ], 403);
        }

        return $next($request);
    }
}
