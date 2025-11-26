<?php

use App\Core\DB;

$pdo = DB::pdo();

// Check if admin already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute(['admin@example.com']);

if (!$stmt->fetch()) {
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        'admin@example.com',
        password_hash('password', PASSWORD_DEFAULT),
        'Admin User',
        'admin',
        time()
    ]);

    echo "✓ Created admin user (admin@example.com / password)\n";
} else {
    echo "• Admin user already exists\n";
}

// Create demo user
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute(['user@example.com']);

if (!$stmt->fetch()) {
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        'user@example.com',
        password_hash('password', PASSWORD_DEFAULT),
        'Demo User',
        'user',
        time()
    ]);

    echo "✓ Created demo user (user@example.com / password)\n";
} else {
    echo "• Demo user already exists\n";
}
