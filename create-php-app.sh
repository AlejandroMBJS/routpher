#!/usr/bin/env bash
#==============================================================================
# ROUTPHER Framework Generator
# A lightweight file-based PHP framework with JWT auth, CSRF, and more
# Version: 2.0.0
#==============================================================================

set -euo pipefail

#------------------------------------------------------------------------------
# Color output helpers
#------------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

info() { echo -e "${BLUE}ℹ${NC} $1"; }
success() { echo -e "${GREEN}✓${NC} $1"; }
warn() { echo -e "${YELLOW}⚠${NC} $1"; }
error() { echo -e "${RED}✗${NC} $1"; exit 1; }

#------------------------------------------------------------------------------
# Parse arguments and show usage
#------------------------------------------------------------------------------
usage() {
    cat <<EOF
Usage: $0 <project-name> [options]

Options:
  --minimal      Create minimal structure without auth examples
  --with-auth    Include full authentication system (default)
  --with-api     API-focused structure with rate limiting
  --full-stack   Everything: auth, API, examples, admin panel
  --no-composer  Skip composer install
  --help         Show this help message

Examples:
  $0 myapp
  $0 myapp --minimal
  $0 api-project --with-api
EOF
    exit 0
}

# Default flags
MINIMAL=false
WITH_AUTH=true
WITH_API=false
FULL_STACK=false
NO_COMPOSER=false

if [ "$#" -lt 1 ]; then
    usage
fi

PROJECT="$1"
shift

while [ "$#" -gt 0 ]; do
    case "$1" in
        --minimal)     MINIMAL=true; WITH_AUTH=false; shift ;;
        --with-auth)   WITH_AUTH=true; shift ;;
        --with-api)    WITH_API=true; shift ;;
        --full-stack)  FULL_STACK=true; WITH_AUTH=true; WITH_API=true; shift ;;
        --no-composer) NO_COMPOSER=true; shift ;;
        --help)        usage ;;
        *)             error "Unknown option: $1. Use --help for usage." ;;
    esac
done

ROOT="$(pwd)/$PROJECT"

#------------------------------------------------------------------------------
# Pre-flight checks
#------------------------------------------------------------------------------
info "Running pre-flight checks..."

# Check if directory exists
if [ -e "$ROOT" ]; then
    error "Directory '$ROOT' already exists. Please choose a different name."
fi

# Check PHP version
if ! command -v php >/dev/null 2>&1; then
    error "PHP is not installed. Please install PHP 8.0 or higher."
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "0")
if [ "$(printf '%s\n' "8.0" "$PHP_VERSION" | sort -V | head -n1)" != "8.0" ]; then
    error "PHP 8.0+ required. Current version: $PHP_VERSION"
fi

# Check required PHP extensions
REQUIRED_EXTS="pdo pdo_sqlite json mbstring openssl"
for ext in $REQUIRED_EXTS; do
    if ! php -m 2>/dev/null | grep -qi "^$ext$"; then
        error "Required PHP extension '$ext' is not installed."
    fi
done

success "All pre-flight checks passed"

#------------------------------------------------------------------------------
# Generate secure random keys
#------------------------------------------------------------------------------
generate_key() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 32 | tr -d '\n'
    else
        head -c 32 /dev/urandom | base64 | tr -d '\n'
    fi
}

APP_KEY=$(generate_key)
JWT_SECRET=$(generate_key)

#------------------------------------------------------------------------------
# Create project structure
#------------------------------------------------------------------------------
info "Creating project: $PROJECT"
mkdir -p "$ROOT"
cd "$ROOT"

info "Creating directory structure..."

# Core directories
mkdir -p public app/core app/middleware app/models app/views/layouts app/views/errors
mkdir -p config database/migrations database/seeds storage/db storage/logs storage/cache
mkdir -p vendor

# Create .gitignore
cat > .gitignore <<'GITIGNORE'
/vendor/
/storage/db/*.db
/storage/logs/*.log
/storage/cache/*
!storage/cache/.gitkeep
.env
.env.local
composer.lock
.DS_Store
Thumbs.db
*.swp
*.swo
*~
GITIGNORE

# Create storage .gitkeep files
touch storage/cache/.gitkeep storage/logs/.gitkeep

success "Directory structure created"

#------------------------------------------------------------------------------
# Create .env and .env.example
#------------------------------------------------------------------------------
info "Generating environment configuration..."

cat > .env <<ENV
# Application
APP_NAME=ROUTPHER
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=$APP_KEY

# Database
DB_CONNECTION=sqlite
DB_PATH=storage/db/app.db

# JWT Authentication
JWT_SECRET=$JWT_SECRET
JWT_ACCESS_EXP=900
JWT_REFRESH_EXP=604800

# Security
CSRF_ENABLED=true
SECURE_COOKIES=false

# Logging
LOG_LEVEL=debug
LOG_FILE=storage/logs/app.log
ENV

cat > .env.example <<'ENVEXAMPLE'
# Application
APP_NAME=ROUTPHER
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=

# Database
DB_CONNECTION=sqlite
DB_PATH=storage/db/app.db

# JWT Authentication
JWT_SECRET=
JWT_ACCESS_EXP=900
JWT_REFRESH_EXP=604800

# Security
CSRF_ENABLED=true
SECURE_COOKIES=false

# Logging
LOG_LEVEL=debug
LOG_FILE=storage/logs/app.log
ENVEXAMPLE

success ".env files created with secure random keys"

#------------------------------------------------------------------------------
# Create composer.json
#------------------------------------------------------------------------------
info "Creating composer.json..."

cat > composer.json <<'JSON'
{
  "name": "routpher/app",
  "description": "ROUTPHER - Lightweight PHP framework",
  "type": "project",
  "require": {
    "php": "^8.0",
    "firebase/php-jwt": "^6.10"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  },
  "autoload": {
    "classmap": [
      "app/core/",
      "app/middleware/",
      "app/models/"
    ],
    "files": [
      "app/core/helpers.php"
    ]
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  },
  "scripts": {
    "test": "phpunit"
  }
}
JSON

success "composer.json created"

#------------------------------------------------------------------------------
# Create SQLite database
#------------------------------------------------------------------------------
info "Initializing SQLite database..."
touch storage/db/app.db
chmod 664 storage/db/app.db 2>/dev/null || true
success "Database file created"

#------------------------------------------------------------------------------
# Create public/.htaccess and index.php
#------------------------------------------------------------------------------
info "Setting up web root..."

cat > public/.htaccess <<'HTA'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect to HTTPS (production)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect to front controller
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory browsing
Options -Indexes

# Prevent access to .env files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
HTA

cat > public/index.php <<'PHP'
<?php
/**
 * ROUTPHER Framework - Front Controller
 * All requests are routed through this file
 */

// Load autoloader and bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\App;

// Create application instance
$app = new App(__DIR__ . '/..');

// Register global middleware
$app->use([\App\Middleware\SecurityHeaders::class, 'handle']);

if (env('CSRF_ENABLED', true)) {
    $app->use([\App\Core\CSRF::class, 'verify']);
}

$app->use([\App\Core\Auth::class, 'loadUser']);

// Run the application
$app->run();
PHP

success "Web root configured"

#------------------------------------------------------------------------------
# Create bootstrap.php
#------------------------------------------------------------------------------
info "Creating application bootstrap..."

cat > app/bootstrap.php <<'PHP'
<?php
/**
 * Bootstrap file - loads environment and core classes
 */

// Error reporting based on environment
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => env('SECURE_COOKIES', false)
    ]);
}

// Set timezone
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
PHP

success "Bootstrap file created"

#------------------------------------------------------------------------------
# Create core helper functions
#------------------------------------------------------------------------------
info "Creating core helpers..."

cat > app/core/helpers.php <<'PHP'
<?php
/**
 * Global helper functions
 */

if (!function_exists('env')) {
    /**
     * Get environment variable with optional default
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert boolean strings
        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return $value === 'true';
        }

        return $value;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('view')) {
    /**
     * Render a view file with data
     */
    function view(string $path, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        $viewFile = __DIR__ . '/../views/' . $path . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $path");
        }

        include $viewFile;
        return ob_get_clean();
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a URL
     */
    function redirect(string $url, int $code = 302): never
    {
        header("Location: $url", true, $code);
        exit;
    }
}

if (!function_exists('json_response')) {
    /**
     * Send JSON response and exit
     */
    function json_response(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('abort')) {
    /**
     * Abort with HTTP error code
     */
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        if ($message) {
            echo $message;
        } else {
            $messages = [
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                500 => 'Internal Server Error'
            ];
            echo $messages[$code] ?? 'Error';
        }

        exit;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die (for debugging)
     */
    function dd(...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        exit;
    }
}

if (!function_exists('logger')) {
    /**
     * Get logger instance
     */
    function logger(): \App\Core\Logger
    {
        static $logger = null;
        if ($logger === null) {
            $logger = new \App\Core\Logger();
        }
        return $logger;
    }
}

if (!function_exists('auth')) {
    /**
     * Get authenticated user
     */
    function auth(): ?array
    {
        return $GLOBALS['auth_user'] ?? null;
    }
}
PHP

success "Helper functions created"

#------------------------------------------------------------------------------
# Create core classes
#------------------------------------------------------------------------------
info "Creating core framework classes..."

# Request class
cat > app/core/Request.php <<'PHP'
<?php

namespace App\Core;

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $headers;
    public array $cookies;
    public array $files;
    public array $meta = [];

    private ?array $jsonData = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->normalizePath($_SERVER['REQUEST_URI'] ?? '/');
        $this->query = $_GET;
        $this->body = $_POST;
        $this->headers = $this->getHeaders();
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
    }

    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        return trim($path, '/');
    }

    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get JSON body
     */
    public function json(): array
    {
        if ($this->jsonData !== null) {
            return $this->jsonData;
        }

        $contentType = $this->headers['Content-Type'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $this->jsonData = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $this->jsonData = [];
        }

        return $this->jsonData;
    }

    /**
     * Get input value (from body or JSON)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($this->body[$key])) {
            return $this->body[$key];
        }

        $json = $this->json();
        return $json[$key] ?? $default;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return ($this->headers['X-Requested-With'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return stripos($this->headers['Content-Type'] ?? '', 'application/json') !== false;
    }

    /**
     * Simple validation
     */
    public function validate(array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            $value = $this->input($field);

            foreach ($ruleList as $r) {
                if ($r === 'required' && empty($value)) {
                    $errors[$field][] = "$field is required";
                }

                if ($r === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "$field must be a valid email";
                }

                if (strpos($r, 'min:') === 0) {
                    $min = (int)substr($r, 4);
                    if (strlen($value) < $min) {
                        $errors[$field][] = "$field must be at least $min characters";
                    }
                }

                if (strpos($r, 'max:') === 0) {
                    $max = (int)substr($r, 4);
                    if (strlen($value) > $max) {
                        $errors[$field][] = "$field must not exceed $max characters";
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new \Exception('Validation failed: ' . json_encode($errors));
        }

        return array_intersect_key($this->body, array_flip(array_keys($rules)));
    }
}
PHP

# Response class
cat > app/core/Response.php <<'PHP'
<?php

namespace App\Core;

class Response
{
    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html(string $content, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
        exit;
    }

    /**
     * Send redirect response
     */
    public static function redirect(string $url, int $code = 302): never
    {
        header("Location: $url", true, $code);
        exit;
    }
}
PHP

# Database class
cat > app/core/DB.php <<'PHP'
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
PHP

# Logger class
cat > app/core/Logger.php <<'PHP'
<?php

namespace App\Core;

class Logger
{
    private string $logFile;
    private string $logLevel;

    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3
    ];

    public function __construct()
    {
        $this->logFile = __DIR__ . '/../../' . env('LOG_FILE', 'storage/logs/app.log');
        $this->logLevel = env('LOG_LEVEL', 'info');
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        if (self::LEVELS[$level] < self::LEVELS[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] " . strtoupper($level) . ": $message$contextStr\n";

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
PHP

# Router class
cat > app/core/Router.php <<'PHP'
<?php

namespace App\Core;

class Router
{
    private string $appDir;

    public function __construct(string $appDir)
    {
        $this->appDir = rtrim($appDir, '/');
    }

    /**
     * Dispatch request to appropriate handler
     * Supports file-based routing with dynamic [param] folders
     * and +server.php for API endpoints
     */
    public function dispatch(Request $req): void
    {
        // Check for API route (+server.php)
        if ($this->dispatchServerRoute($req)) {
            return;
        }

        // Regular page routing
        $this->dispatchPageRoute($req);
    }

    /**
     * Try to dispatch to +server.php (API endpoint)
     */
    private function dispatchServerRoute(Request $req): bool
    {
        $segments = $req->path === '' ? [] : explode('/', $req->path);
        $current = $this->appDir;
        $params = [];
        $folders = [$current];

        foreach ($segments as $segment) {
            $direct = "$current/$segment";

            if (is_dir($direct)) {
                $current = $direct;
                $folders[] = $current;
                continue;
            }

            // Try dynamic [param] folders
            $found = false;
            foreach (glob("$current/*", GLOB_ONLYDIR) as $dir) {
                if (preg_match('/^\[(.+)\]$/', basename($dir), $matches)) {
                    $params[$matches[1]] = $segment;
                    $current = $dir;
                    $folders[] = $current;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        // Check for +server.php in deepest matching folder
        $serverFile = "$current/+server.php";

        if (file_exists($serverFile)) {
            $req->meta['params'] = $params;
            $this->executeServerFile($serverFile, $req);
            return true;
        }

        return false;
    }

    /**
     * Execute +server.php file
     */
    private function executeServerFile(string $file, Request $req): void
    {
        $handler = require $file;

        if (is_callable($handler)) {
            $handler($req);
        } elseif (is_array($handler)) {
            $method = strtolower($req->method);
            if (isset($handler[$method]) && is_callable($handler[$method])) {
                $handler[$method]($req);
            } else {
                Response::json(['error' => 'Method not allowed'], 405);
            }
        }
    }

    /**
     * Dispatch to page.php (web route)
     */
    private function dispatchPageRoute(Request $req): void
    {
        $segments = $req->path === '' ? [] : explode('/', $req->path);
        $current = $this->appDir;
        $folders = [$current];
        $params = [];

        foreach ($segments as $segment) {
            $direct = "$current/$segment";

            if (is_dir($direct)) {
                $current = $direct;
                $folders[] = $current;
                continue;
            }

            // Try dynamic [param] folders
            $found = false;
            foreach (glob("$current/*", GLOB_ONLYDIR) as $dir) {
                if (preg_match('/^\[(.+)\]$/', basename($dir), $matches)) {
                    $params[$matches[1]] = $segment;
                    $current = $dir;
                    $folders[] = $current;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->renderError(404, $folders);
                return;
            }
        }

        $pageFile = "$current/page.php";

        if (!file_exists($pageFile)) {
            $this->renderError(404, $folders);
            return;
        }

        try {
            // Find and render loading.php if exists
            $loadingFile = $this->findDeepestFile($folders, 'loading.php');
            if ($loadingFile) {
                echo $this->renderFile($loadingFile, ['params' => $params]);
            }

            // Collect all layout files from root to current
            $layouts = $this->collectLayouts($folders);

            // Render page
            $content = $this->renderFile($pageFile, ['params' => $params]);

            // Wrap with layouts (innermost to outermost)
            for ($i = count($layouts) - 1; $i >= 0; $i--) {
                $content = $this->renderFile($layouts[$i], [
                    'content' => $content,
                    'params' => $params
                ]);
            }

            echo $content;

        } catch (\Throwable $e) {
            logger()->error("Route error: " . $e->getMessage(), [
                'path' => $req->path,
                'trace' => $e->getTraceAsString()
            ]);

            $errorFile = $this->findDeepestFile($folders, 'error.php');

            if ($errorFile) {
                echo $this->renderFile($errorFile, [
                    'error' => $e,
                    'params' => $params
                ]);
            } else {
                http_response_code(500);
                if (env('APP_DEBUG', false)) {
                    echo '<pre>' . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>';
                } else {
                    echo 'Internal Server Error';
                }
            }
        }
    }

    /**
     * Render a PHP file with variables
     */
    private function renderFile(string $file, array $vars = []): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Collect all layout.php files from folders
     */
    private function collectLayouts(array $folders): array
    {
        $layouts = [];
        foreach ($folders as $folder) {
            $layoutFile = "$folder/layout.php";
            if (file_exists($layoutFile)) {
                $layouts[] = $layoutFile;
            }
        }
        return $layouts;
    }

    /**
     * Find deepest occurrence of a file in folder hierarchy
     */
    private function findDeepestFile(array $folders, string $filename): ?string
    {
        for ($i = count($folders) - 1; $i >= 0; $i--) {
            $file = $folders[$i] . '/' . $filename;
            if (file_exists($file)) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Render error page
     */
    private function renderError(int $code, array $folders): void
    {
        http_response_code($code);

        $errorFile = $this->appDir . "/views/errors/$code.php";

        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            $messages = [
                404 => 'Not Found',
                403 => 'Forbidden',
                500 => 'Internal Server Error'
            ];
            echo $messages[$code] ?? 'Error';
        }
    }
}
PHP

# App class
cat > app/core/App.php <<'PHP'
<?php

namespace App\Core;

class App
{
    private array $middlewares = [];
    private Router $router;

    public function __construct(string $rootPath)
    {
        $this->router = new Router($rootPath . '/app');
    }

    /**
     * Register middleware
     */
    public function use(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        $request = new Request();

        // Build middleware chain
        $handler = function($req) {
            return $this->router->dispatch($req);
        };

        // Wrap handler with middleware (in reverse order)
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $handler;
            $handler = function($req) use ($middleware, $next) {
                return $middleware($req, $next);
            };
        }

        // Execute chain
        $handler($request);
    }
}
PHP

# Auth class
cat > app/core/Auth.php <<'PHP'
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
PHP

# CSRF class
cat > app/core/CSRF.php <<'PHP'
<?php

namespace App\Core;

class CSRF
{
    /**
     * Generate CSRF token
     */
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /**
     * Verify CSRF token (middleware)
     */
    public static function verify(Request $req, callable $next): mixed
    {
        // Only verify for state-changing methods
        if (in_array($req->method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {

            // Skip CSRF for JSON API requests (rely on CORS + SameSite cookies)
            if ($req->isJson()) {
                return $next($req);
            }

            $sentToken = $req->input('_csrf') ?? $req->headers['X-CSRF-Token'] ?? null;
            $sessionToken = $_SESSION['_csrf'] ?? '';

            if (!$sentToken || !hash_equals($sessionToken, $sentToken)) {
                logger()->warning('CSRF token mismatch', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'path' => $req->path
                ]);

                http_response_code(403);
                echo 'CSRF token mismatch';
                exit;
            }
        }

        return $next($req);
    }
}
PHP

success "Core classes created"

#------------------------------------------------------------------------------
# Create middleware
#------------------------------------------------------------------------------
info "Creating middleware..."

mkdir -p app/middleware

cat > app/middleware/SecurityHeaders.php <<'PHP'
<?php

namespace App\Middleware;

use App\Core\Request;

class SecurityHeaders
{
    public static function handle(Request $req, callable $next): mixed
    {
        // Security headers
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // CSP (adjust as needed)
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' unpkg.com; style-src 'self' 'unsafe-inline';";
        header("Content-Security-Policy: $csp");

        // HSTS (uncomment for production with HTTPS)
        if (env('SECURE_COOKIES', false)) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }

        return $next($req);
    }
}
PHP

cat > app/middleware/RateLimit.php <<'PHP'
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
PHP

success "Middleware created"

#------------------------------------------------------------------------------
# Create models (if auth enabled)
#------------------------------------------------------------------------------
if [ "$WITH_AUTH" = true ]; then
    info "Creating User model..."

    cat > app/models/User.php <<'PHP'
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
PHP

    success "User model created"
fi

#------------------------------------------------------------------------------
# Create views
#------------------------------------------------------------------------------
info "Creating views..."

# Main layout
cat > app/views/layouts/main.php <<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'ROUTPHER App' ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
        header { background: #2563eb; color: white; padding: 1rem 2rem; }
        nav { display: flex; gap: 2rem; }
        nav a { color: white; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; }
        footer { text-align: center; padding: 2rem; color: #666; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="/">Home</a>
            <?php if (isset($GLOBALS['auth_user'])): ?>
                <a href="/profile">Profile</a>
                <a href="/logout">Logout</a>
            <?php else: ?>
                <a href="/login">Login</a>
                <a href="/register">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> ROUTPHER App</p>
    </footer>
</body>
</html>
HTML

# Home page
cat > app/page.php <<'HTML'
<?php $title = 'Welcome to ROUTPHER'; ?>

<h1>Welcome to ROUTPHER</h1>

<p>Your application is ready! ROUTPHER is a lightweight PHP framework with:</p>

<ul style="margin: 1rem 0; padding-left: 2rem;">
    <li>📁 File-based routing with dynamic parameters</li>
    <li>🔐 JWT authentication with access & refresh tokens</li>
    <li>🛡️ CSRF protection</li>
    <li>🗄️ SQLite database (MySQL/PostgreSQL ready)</li>
    <li>⚡ HTMX integration for modern UX</li>
    <li>🎨 Simple and clean architecture</li>
</ul>

<h2>Getting Started</h2>

<p>Check out the <code>app/</code> directory to start building your application.</p>

<p style="margin-top: 2rem;">
    <a href="/login" class="btn">Get Started</a>
</p>
HTML

# Error pages
cat > app/views/errors/404.php <<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Not Found</title>
    <style>
        body { font-family: system-ui; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .error { text-align: center; }
        h1 { font-size: 4rem; margin: 0; color: #2563eb; }
        p { font-size: 1.2rem; color: #666; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="error">
        <h1>404</h1>
        <p>Page not found</p>
        <a href="/">Go home</a>
    </div>
</body>
</html>
HTML

cat > app/views/errors/500.php <<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error</title>
    <style>
        body { font-family: system-ui; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .error { text-align: center; }
        h1 { font-size: 4rem; margin: 0; color: #dc2626; }
        p { font-size: 1.2rem; color: #666; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="error">
        <h1>500</h1>
        <p>Internal Server Error</p>
        <a href="/">Go home</a>
    </div>
</body>
</html>
HTML

success "Views created"

#------------------------------------------------------------------------------
# Create auth views and routes (if enabled)
#------------------------------------------------------------------------------
if [ "$WITH_AUTH" = true ]; then
    info "Creating authentication pages..."

    mkdir -p app/login app/register app/logout app/profile

    # Login page
    cat > app/login/page.php <<'HTML'
<?php $title = 'Login'; ?>

<h1>Login</h1>

<?php if (isset($_SESSION['error'])): ?>
    <div style="padding: 1rem; background: #fee; border: 1px solid #fcc; border-radius: 4px; margin-bottom: 1rem;">
        <?= e($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<form method="POST" action="/login" style="max-width: 400px;">
    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Email:</label>
        <input type="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Password:</label>
        <input type="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <input type="hidden" name="_csrf" value="<?= \App\Core\CSRF::token() ?>">

    <button type="submit" class="btn">Login</button>

    <p style="margin-top: 1rem;">
        Don't have an account? <a href="/register">Register</a>
    </p>
</form>
HTML

    # Login +server.php (handles POST)
    cat > app/login/+server.php <<'PHP'
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
PHP

    # Register page
    cat > app/register/page.php <<'HTML'
<?php $title = 'Register'; ?>

<h1>Register</h1>

<?php if (isset($_SESSION['error'])): ?>
    <div style="padding: 1rem; background: #fee; border: 1px solid #fcc; border-radius: 4px; margin-bottom: 1rem;">
        <?= e($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<form method="POST" action="/register" style="max-width: 400px;">
    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Name:</label>
        <input type="text" name="name" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Email:</label>
        <input type="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Password:</label>
        <input type="password" name="password" required minlength="8" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <input type="hidden" name="_csrf" value="<?= \App\Core\CSRF::token() ?>">

    <button type="submit" class="btn">Register</button>

    <p style="margin-top: 1rem;">
        Already have an account? <a href="/login">Login</a>
    </p>
</form>
HTML

    # Register +server.php
    cat > app/register/+server.php <<'PHP'
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
PHP

    # Profile page
    cat > app/profile/page.php <<'HTML'
<?php
// Require authentication
if (!isset($GLOBALS['auth_user'])) {
    redirect('/login');
}

$user = $GLOBALS['auth_user'];
$title = 'Profile';
?>

<h1>Profile</h1>

<div style="padding: 1.5rem; background: #f9f9f9; border-radius: 8px; margin-top: 1rem;">
    <p><strong>Name:</strong> <?= e($user['name']) ?></p>
    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Role:</strong> <?= e($user['role']) ?></p>
    <p><strong>Member since:</strong> <?= date('F j, Y', $user['created_at']) ?></p>
</div>

<p style="margin-top: 2rem;">
    <a href="/logout" class="btn">Logout</a>
</p>
HTML

    # Logout
    cat > app/logout/page.php <<'PHP'
<?php
// Clear cookies
setcookie('access', '', time() - 3600, '/');
setcookie('refresh', '', time() - 3600, '/');

// Destroy session
session_destroy();

redirect('/');
PHP

    success "Authentication pages created"
fi

#------------------------------------------------------------------------------
# Create API routes (if enabled)
#------------------------------------------------------------------------------
if [ "$WITH_API" = true ]; then
    info "Creating API routes..."

    mkdir -p app/api/users app/api/auth/refresh

    # API users list
    cat > app/api/users/+server.php <<'PHP'
<?php

use App\Core\Response;
use App\Core\DB;

return [
    'get' => function($req) {
        $users = DB::table('users')
            ->limit(100)
            ->get();

        // Remove password field
        $users = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);

        Response::json($users);
    }
];
PHP

    # API auth refresh
    cat > app/api/auth/refresh/+server.php <<'PHP'
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
PHP

    success "API routes created"
fi

#------------------------------------------------------------------------------
# Create database migrations
#------------------------------------------------------------------------------
info "Creating database migrations..."

cat > database/migrations/001_create_users_table.php <<'PHP'
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
PHP

cat > database/migrations/002_create_migrations_table.php <<'PHP'
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
PHP

success "Migrations created"

#------------------------------------------------------------------------------
# Create database seeders
#------------------------------------------------------------------------------
info "Creating database seeders..."

cat > database/seeds/UserSeeder.php <<'PHP'
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
PHP

success "Seeders created"

#------------------------------------------------------------------------------
# Create CLI scripts
#------------------------------------------------------------------------------
info "Creating CLI tools..."

# Migrate script
cat > artisan <<'PHP'
#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/bootstrap.php';

use App\Core\DB;

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        echo "Running migrations...\n";

        $migrations = glob(__DIR__ . '/database/migrations/*.php');
        sort($migrations);

        // Get executed migrations
        $pdo = DB::pdo();
        $executed = [];

        try {
            $stmt = $pdo->query('SELECT migration FROM migrations');
            $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            // Migrations table might not exist yet
        }

        foreach ($migrations as $file) {
            $name = basename($file);

            if (in_array($name, $executed)) {
                echo "• Skipping $name (already executed)\n";
                continue;
            }

            echo "→ Running $name\n";
            require $file;

            // Record migration
            try {
                $stmt = $pdo->prepare('INSERT INTO migrations (migration, executed_at) VALUES (?, ?)');
                $stmt->execute([$name, time()]);
            } catch (\Exception $e) {
                // Ignore if migrations table doesn't exist yet
            }
        }

        echo "\n✓ Migrations complete\n";
        break;

    case 'db:seed':
        echo "Running seeders...\n";

        $seeders = glob(__DIR__ . '/database/seeds/*.php');
        sort($seeders);

        foreach ($seeders as $file) {
            echo "→ Running " . basename($file) . "\n";
            require $file;
        }

        echo "\n✓ Seeding complete\n";
        break;

    case 'serve':
        $host = $argv[2] ?? '127.0.0.1';
        $port = $argv[3] ?? '8000';

        echo "ROUTPHER development server starting on http://$host:$port\n";
        echo "Press Ctrl+C to stop.\n\n";

        passthru(PHP_BINARY . " -S $host:$port -t public");
        break;

    case 'key:generate':
        $key = base64_encode(random_bytes(32));

        $envFile = __DIR__ . '/.env';
        $content = file_get_contents($envFile);
        $content = preg_replace('/APP_KEY=.*/', "APP_KEY=$key", $content);
        file_put_contents($envFile, $content);

        echo "✓ Application key generated: $key\n";
        break;

    case 'routes:list':
        echo "Route listing not yet implemented\n";
        break;

    case 'help':
    default:
        echo <<<HELP
ROUTPHER Framework CLI

Usage:
  php artisan <command>

Available commands:
  migrate         Run database migrations
  db:seed         Run database seeders
  serve [host] [port]  Start development server (default: 127.0.0.1:8000)
  key:generate    Generate new application key
  routes:list     List all registered routes
  help            Show this help message

HELP;
        break;
}
PHP

chmod +x artisan 2>/dev/null || true

success "CLI tools created"

#------------------------------------------------------------------------------
# Install composer dependencies
#------------------------------------------------------------------------------
if [ "$NO_COMPOSER" = false ] && command -v composer >/dev/null 2>&1; then
    info "Installing composer dependencies..."
    composer install --no-interaction --quiet 2>&1 | grep -v "Warning" || true
    success "Composer dependencies installed"
else
    warn "Composer not found or --no-composer flag set. Run 'composer install' manually."
fi

#------------------------------------------------------------------------------
# Run migrations and seed
#------------------------------------------------------------------------------
if [ -f vendor/autoload.php ]; then
    info "Running database migrations..."
    php artisan migrate

    if [ "$WITH_AUTH" = true ]; then
        info "Seeding database..."
        php artisan db:seed
    fi
else
    warn "Skipping migrations (run 'composer install' first, then 'php artisan migrate')"
fi

#------------------------------------------------------------------------------
# Final instructions
#------------------------------------------------------------------------------
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓ ROUTPHER project created successfully!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo ""
echo "  cd $PROJECT"
echo "  php artisan serve"
echo ""
echo -e "${BLUE}Then open:${NC} http://127.0.0.1:8000"
echo ""

if [ "$WITH_AUTH" = true ]; then
    echo -e "${BLUE}Default credentials:${NC}"
    echo "  Admin: admin@example.com / password"
    echo "  User:  user@example.com / password"
    echo ""
fi

echo -e "${YELLOW}⚠ Important:${NC}"
echo "  • Change JWT_SECRET and APP_KEY in .env before production"
echo "  • Enable HTTPS and set SECURE_COOKIES=true in production"
echo "  • Review security settings in app/middleware/SecurityHeaders.php"
echo ""
echo -e "${BLUE}Documentation:${NC} Check index.html for complete docs"
echo ""
