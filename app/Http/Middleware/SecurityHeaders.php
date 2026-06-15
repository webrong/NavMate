<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Generate a per-request CSP nonce
        $nonce = base64_encode(random_bytes(16));
        // Share nonce with Blade views so they can add nonce="{{ $cspNonce }}" to script/style tags
        View::share('cspNonce', $nonce);

        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable browser XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (nonce-based — blocks injected inline scripts)
        // Use an explicit env flag instead of app()->environment(). The latter
        // can disagree with reality when `artisan serve` inherits a stale
        // APP_ENV from the parent shell (e.g. on Windows where `set` vars
        // leak across processes), which leads to CSP being sent on a dev box
        // and breaking the Vite-built admin shell.
        $cspDisabled = env('CSP_DISABLED', app()->environment('local'));

        if (! $cspDisabled) {
            $csp = "default-src 'self'; ";
            $csp .= "script-src 'self' 'nonce-{$nonce}'; ";
            $csp .= "style-src 'self' 'unsafe-inline'; ";
            $csp .= "img-src 'self' data: https: http:; ";
            $csp .= "font-src 'self' data:; ";
            $csp .= "connect-src 'self'; ";
            $csp .= "frame-ancestors 'none';";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
