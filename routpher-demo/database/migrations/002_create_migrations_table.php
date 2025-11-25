<?php

use App\Core\DB;

$pdo = DB::pdo();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration TEXT UNIQUE NOT NULL,
        executed_at INTEGER NOT NULL
    )
");

echo "✓ Created migrations table\n";
