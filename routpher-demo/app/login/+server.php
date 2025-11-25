<?php

use App\Core\Auth;
use App\Core\Response;
use App\Models\User;

return [
    'post' => function($req) {
        $email = $req->input('email');
        $password = $req->input('password');

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            logger()->warning('Failed login attempt', ['email' => $email]);

            $_SESSION['error'] = 'Invalid credentials';
            redirect('/login');
        }

        logger()->info('User logged in', ['user_id' => $user['id']]);

        $tokens = Auth::issueTokens($user['id']);

        $cookieOptions = [
            'expires' => time() + (int)env('JWT_REFRESH_EXP', 604800),
            'path' => '/',
            'secure' => env('SECURE_COOKIES', false),
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        setcookie('refresh', $tokens['refresh'], $cookieOptions);
        setcookie('access', $tokens['access'], [
            'expires' => $tokens['expires'],
            'path' => '/',
            'secure' => env('SECURE_COOKIES', false),
            'httponly' => false, // Accessible to JS for API calls
            'samesite' => 'Lax'
        ]);

        redirect('/profile');
    }
];
