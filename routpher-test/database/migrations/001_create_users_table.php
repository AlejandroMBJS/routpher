<?php

use App\Core\DB;

$pdo = DB::pdo();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        name TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        created_at INTEGER NOT NULL
    )
");

echo "✓ Created users table\n";
