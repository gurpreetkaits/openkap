<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeadersMiddleware
{
    /**
     * Routes that should allow iframe embedding (e.g., video embeds for social media)
     */
    protected array $excludedPaths = [
        'embed/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't add X-Frame-Options for routes that need to be embeddable
        if (! $this->shouldExclude($request)) {
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        }

        // Always add these security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }

    /**
     * Check if the request path should be excluded from X-Frame-Options
     */
    protected function shouldExclude(Request $request): bool
    {
        foreach ($this->excludedPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
