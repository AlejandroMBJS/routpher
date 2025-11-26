<?php

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    /**
     * Issue access and refresh tokens
     */
    public static function issueTokens(int|string $userId): array
    {
        $now = time();
        $secret = env('JWT_SECRET');
        $accessExp = (int)env('JWT_ACCESS_EXP', 900);
        $refreshExp = (int)env('JWT_REFRESH_EXP', 604800);

        if (!$secret) {
            throw new \RuntimeException('JWT_SECRET not configured');
        }

        $accessPayload = [
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $accessExp,
            'type' => 'access'
        ];

        $refreshPayload = [
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $refreshExp,
            'type' => 'refresh'
        ];

        $accessToken = JWT::encode($accessPayload, $secret, 'HS256');
        $refreshToken = JWT::encode($refreshPayload, $secret, 'HS256');

        return [
            'access' => $accessToken,
            'refresh' => $refreshToken,
            'expires' => $accessPayload['exp']
        ];
    }

    /**
     * Validate and decode token
     */
    public static function validate(string $token): ?object
    {
        try {
            $secret = env('JWT_SECRET');
            if (!$secret) {
                return null;
            }

            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return $decoded;

        } catch (\Throwable $e) {
            logger()->debug("JWT validation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Middleware to load authenticated user
     */
    public static function loadUser(Request $req, callable $next): mixed
    {
        $token = null;

        // Check Authorization header first
        $authHeader = $req->headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // Fallback to cookie
        if (!$token && isset($req->cookies['access'])) {
            $token = $req->cookies['access'];
        }

        if ($token) {
            $decoded = self::validate($token);

            if ($decoded && isset($decoded->sub)) {
                // Load user from database
                $user = \App\Models\User::find($decoded->sub);
                $GLOBALS['auth_user'] = $user;
                $req->meta['user'] = $user;
            }
        }

        return $next($req);
    }

    /**
     * Require authentication middleware
     */
    public static function requireAuth(Request $req, callable $next): mixed
    {
        if (!isset($GLOBALS['auth_user'])) {
            if ($req->isJson()) {
                Response::json(['error' => 'Unauthorized'], 401);
            } else {
                redirect('/login');
            }
        }

        return $next($req);
    }
}
