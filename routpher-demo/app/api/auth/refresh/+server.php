<?php

use App\Core\Auth;
use App\Core\Response;

return [
    'post' => function($req) {
        $refreshToken = $req->cookies['refresh'] ?? null;

        if (!$refreshToken) {
            Response::json(['error' => 'No refresh token'], 401);
        }

        $decoded = Auth::validate($refreshToken);

        if (!$decoded || ($decoded->type ?? '') !== 'refresh') {
            Response::json(['error' => 'Invalid refresh token'], 401);
        }

        // Issue new tokens
        $tokens = Auth::issueTokens($decoded->sub);

        // Set new refresh cookie
        setcookie('refresh', $tokens['refresh'], [
            'expires' => time() + (int)env('JWT_REFRESH_EXP', 604800),
            'path' => '/',
            'secure' => env('SECURE_COOKIES', false),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        Response::json([
            'access' => $tokens['access'],
            'expires' => $tokens['expires']
        ]);
    }
];
