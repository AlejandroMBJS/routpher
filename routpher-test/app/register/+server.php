<?php

use App\Core\Auth;
use App\Models\User;

return [
    'post' => function($req) {
        $name = $req->input('name');
        $email = $req->input('email');
        $password = $req->input('password');

        // Check if user exists
        if (User::findByEmail($email)) {
            $_SESSION['error'] = 'Email already registered';
            redirect('/register');
        }

        // Validate
        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters';
            redirect('/register');
        }

        // Create user
        $userId = User::create([
            'email' => $email,
            'password' => $password,
            'name' => $name,
            'role' => 'user'
        ]);

        logger()->info('User registered', ['user_id' => $userId]);

        // Auto-login
        $tokens = Auth::issueTokens($userId);

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
            'httponly' => false,
            'samesite' => 'Lax'
        ]);

        redirect('/profile');
    }
];
