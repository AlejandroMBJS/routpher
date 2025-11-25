<?php

namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $pdo = null;

    /**
     * Get PDO instance
     */
    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $connection = env('DB_CONNECTION', 'sqlite');

        try {
            if ($connection === 'sqlite') {
                $path = __DIR__ . '/../../' . env('DB_PATH', 'storage/db/app.db');
                $dsn = "sqlite:$path";
                self::$pdo = new PDO($dsn);
            } elseif ($connection === 'mysql') {
                $host = env('DB_HOST', '127.0.0.1');
                $port = env('DB_PORT', '3306');
                $name = env('DB_DATABASE', 'app');
                $user = env('DB_USERNAME', 'root');
                $pass = env('DB_PASSWORD', '');
                $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
                self::$pdo = new PDO($dsn, $user, $pass);
            } else {
                throw new \RuntimeException("Unsupported database connection: $connection");
            }

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            logger()->error("Database connection failed: " . $e->getMessage());
            throw $e;
        }

        return self::$pdo;
    }

    /**
     * Simple query builder
     */
    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }
}

/**
 * Simple query builder
 */
class QueryBuilder
{
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function where(string $column, mixed $value): self
    {
        $this->wheres[] = "$column = ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)DB::pdo()->lastInsertId();
    }
}
