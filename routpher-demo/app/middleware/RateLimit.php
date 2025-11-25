<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RateLimit
{
    private static array $attempts = [];

    /**
     * Rate limit middleware
     *
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decayMinutes Time window in minutes
     */
    public static function limit(int $maxAttempts = 5, int $decayMinutes = 1): callable
    {
        return function(Request $req, callable $next) use ($maxAttempts, $decayMinutes) {
            $key = self::getKey($req);
            $now = time();

            // Clean old attempts
            self::$attempts[$key] = array_filter(
                self::$attempts[$key] ?? [],
                fn($timestamp) => $timestamp > $now - ($decayMinutes * 60)
            );

            if (count(self::$attempts[$key] ?? []) >= $maxAttempts) {
                logger()->warning('Rate limit exceeded', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'path' => $req->path
                ]);

                if ($req->isJson()) {
                    Response::json(['error' => 'Too many requests'], 429);
                } else {
                    http_response_code(429);
                    echo 'Too many requests. Please try again later.';
                    exit;
                }
            }

            self::$attempts[$key][] = $now;

            return $next($req);
        };
    }

    private static function getKey(Request $req): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return md5($ip . $req->path);
    }
}
