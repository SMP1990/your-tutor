<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers for every page.
 */
class SecurityHeaders
{
    public const CSP = "default-src 'self'; "
        ."script-src 'self'; "
        ."style-src 'self' https://fonts.googleapis.com; "
        ."font-src 'self' https://fonts.gstatic.com; "
        ."img-src 'self' data:; "
        ."connect-src 'self'; "
        ."frame-ancestors 'self'; "
        ."form-action 'self'; "
        ."base-uri 'self'; "
        ."object-src 'none'";

    public const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            $response->headers->set($name, $value);
        }

        // Vite's dev server serves scripts from another port, so only enforce the CSP on built assets.
        if (! app()->environment('local') || ! is_file(public_path('hot'))) {
            $response->headers->set('Content-Security-Policy', self::CSP);
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
