<?php

namespace App\Models;

use App\Core\DB;
use PDO;

class User
{
    /**
     * Find user by ID
     */
    public static function find(int|string $id): ?array
    {
        $stmt = DB::pdo()->prepare(
            'SELECT id, email, name, role, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find user by email (includes password for authentication)
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Get all users
     */
    public static function all(): array
    {
        return DB::pdo()
            ->query('SELECT id, email, name, role, created_at FROM users ORDER BY created_at DESC')
            ->fetchAll();
    }

    /**
     * Create a new user
     */
    public static function create(array $data): int
    {
        $stmt = DB::pdo()->prepare(
            'INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['name'] ?? '',
            $data['role'] ?? 'user',
            time()
        ]);

        return (int)DB::pdo()->lastInsertId();
    }

    /**
     * Update user
     */
    public static function update(int|string $id, array $data): bool
    {
        $stmt = DB::pdo()->prepare(
            'UPDATE users SET email = ?, name = ?, role = ? WHERE id = ?'
        );

        return $stmt->execute([
            $data['email'],
            $data['name'],
            $data['role'],
            $id
        ]);
    }

    /**
     * Delete user
     */
    public static function delete(int|string $id): bool
    {
        $stmt = DB::pdo()->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
