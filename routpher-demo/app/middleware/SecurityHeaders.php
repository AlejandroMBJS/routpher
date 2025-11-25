<?php

namespace App\Middleware;

use App\Core\Request;

class SecurityHeaders
{
    public static function handle(Request $req, callable $next): mixed
    {
        // Security headers
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // CSP (adjust as needed)
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' unpkg.com; style-src 'self' 'unsafe-inline';";
        header("Content-Security-Policy: $csp");

        // HSTS (uncomment for production with HTTPS)
        if (env('SECURE_COOKIES', false)) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }

        return $next($req);
    }
}
