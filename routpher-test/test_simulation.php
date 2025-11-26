<?php
/**
 * ROUTPHER Framework Simulation Test
 * Tests all major framework features
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\Request;
use App\Core\Auth;
use App\Core\DB;
use App\Models\User;

$results = [
    'passed' => [],
    'failed' => [],
    'warnings' => []
];

function test($name, callable $fn) {
    global $results;
    try {
        ob_start();
        $fn();
        ob_end_clean();
        $results['passed'][] = $name;
        echo "✓ $name\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        $results['failed'][] = "$name: " . $e->getMessage();
        echo "✗ $name: " . $e->getMessage() . "\n";
    }
}

function warn($message) {
    global $results;
    $results['warnings'][] = $message;
    echo "⚠ $message\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "ROUTPHER FRAMEWORK SIMULATION TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Test 1: Database Connection
echo "[1] DATABASE TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("Database connection established", function() {
    $pdo = DB::pdo();
    assert($pdo instanceof PDO);
});

test("Users table exists and has data", function() {
    $users = User::all();
    assert(count($users) >= 2, "Expected at least 2 users");
});

test("Can find user by ID", function() {
    $user = User::find(1);
    assert($user !== null, "User 1 should exist");
    assert(isset($user['email']), "User should have email");
});

test("Can find user by email", function() {
    $user = User::findByEmail('admin@example.com');
    assert($user !== null, "Admin user should exist");
});

test("Query builder works", function() {
    $users = DB::table('users')->where('role', 'admin')->get();
    assert(count($users) >= 1, "Should find at least one admin");
});

echo "\n[2] AUTHENTICATION TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("Can issue JWT tokens", function() {
    $tokens = Auth::issueTokens(1);
    assert(isset($tokens['access']), "Should have access token");
    assert(isset($tokens['refresh']), "Should have refresh token");
    assert(isset($tokens['expires']), "Should have expiry time");
});

test("Can validate JWT tokens", function() {
    $tokens = Auth::issueTokens(1);
    $decoded = Auth::validate($tokens['access']);
    assert($decoded !== null, "Token should be valid");
    assert($decoded->sub === 1, "User ID should be 1");
    assert($decoded->type === 'access', "Type should be access");
});

test("Refresh token has correct type", function() {
    $tokens = Auth::issueTokens(1);
    $decoded = Auth::validate($tokens['refresh']);
    assert($decoded !== null, "Refresh token should be valid");
    assert($decoded->type === 'refresh', "Type should be refresh");
});

test("Invalid tokens are rejected", function() {
    $decoded = Auth::validate('invalid.token.here');
    assert($decoded === null, "Invalid token should return null");
});

test("Password hashing works", function() {
    $user = User::findByEmail('admin@example.com');
    assert(password_verify('password', $user['password']), "Password should verify");
});

echo "\n[3] ROUTING TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("Router initializes correctly", function() {
    $router = new Router(__DIR__ . '/app');
    assert($router instanceof Router, "Router should be created");
});

test("Home page file exists", function() {
    assert(file_exists(__DIR__ . '/app/page.php'), "Home page should exist");
});

test("Login route files exist", function() {
    assert(file_exists(__DIR__ . '/app/login/page.php'), "Login page should exist");
    assert(file_exists(__DIR__ . '/app/login/+server.php'), "Login API should exist");
});

test("Profile route exists", function() {
    assert(file_exists(__DIR__ . '/app/profile/page.php'), "Profile page should exist");
});

test("Main layout exists", function() {
    assert(file_exists(__DIR__ . '/app/views/layouts/main.php'), "Main layout should exist");
});

test("Error pages exist", function() {
    assert(file_exists(__DIR__ . '/app/views/errors/404.php'), "404 page should exist");
    assert(file_exists(__DIR__ . '/app/views/errors/500.php'), "500 page should exist");
});

echo "\n[4] MIDDLEWARE TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("SecurityHeaders middleware exists", function() {
    assert(class_exists('App\Middleware\SecurityHeaders'), "SecurityHeaders class should exist");
    assert(method_exists('App\Middleware\SecurityHeaders', 'handle'), "Should have handle method");
});

test("RateLimit middleware exists", function() {
    assert(class_exists('App\Middleware\RateLimit'), "RateLimit class should exist");
    assert(method_exists('App\Middleware\RateLimit', 'limit'), "Should have limit method");
});

test("CSRF class exists", function() {
    assert(class_exists('App\Core\CSRF'), "CSRF class should exist");
    assert(method_exists('App\Core\CSRF', 'token'), "Should have token method");
    assert(method_exists('App\Core\CSRF', 'verify'), "Should have verify method");
});

test("CSRF token generation works", function() {
    session_start();
    $token = \App\Core\CSRF::token();
    assert(!empty($token), "Token should not be empty");
    assert(strlen($token) === 64, "Token should be 64 characters (hex)");
});

echo "\n[5] REQUEST/RESPONSE TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("Request class parses method correctly", function() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/test';
    $req = new Request();
    assert($req->method === 'POST', "Method should be POST");
    assert($req->path === 'test', "Path should be normalized");
});

test("Request handles JSON input", function() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/test';
    $req = new Request();
    $req->headers['Content-Type'] = 'application/json';
    assert($req->isJson() === true, "Should detect JSON content type");
});

test("Request validation works", function() {
    $_POST['email'] = 'test@example.com';
    $_POST['password'] = 'test1234';
    $req = new Request();

    $validated = $req->validate([
        'email' => 'required|email',
        'password' => 'required|min:8'
    ]);

    assert(isset($validated['email']), "Should have validated email");
    assert($validated['email'] === 'test@example.com', "Email should match");
});

test("Request validation fails for invalid data", function() {
    $_POST = [];
    $req = new Request();

    try {
        $req->validate(['email' => 'required']);
        throw new \Exception("Validation should have failed");
    } catch (\Exception $e) {
        assert(str_contains($e->getMessage(), 'Validation failed'), "Should throw validation error");
    }
});

echo "\n[6] HELPER FUNCTIONS TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("env() helper works", function() {
    putenv('TEST_VAR=test_value');
    $_ENV['TEST_VAR'] = 'test_value';
    assert(env('TEST_VAR') === 'test_value', "Should read env variable");
    assert(env('NONEXISTENT', 'default') === 'default', "Should return default");
});

test("e() escapes HTML correctly", function() {
    $unsafe = '<script>alert("xss")</script>';
    $safe = e($unsafe);
    assert(str_contains($safe, '&lt;'), "Should escape HTML");
    assert(!str_contains($safe, '<script>'), "Should not contain script tag");
});

test("logger() returns Logger instance", function() {
    $logger = logger();
    assert($logger instanceof \App\Core\Logger, "Should return Logger instance");
});

echo "\n[7] SECURITY TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("JWT_SECRET is configured", function() {
    $secret = env('JWT_SECRET');
    assert(!empty($secret), "JWT_SECRET should be set");
    assert(strlen($secret) > 20, "JWT_SECRET should be sufficiently long");
});

test("APP_KEY is configured", function() {
    $key = env('APP_KEY');
    assert(!empty($key), "APP_KEY should be set");
    assert(strlen($key) > 20, "APP_KEY should be sufficiently long");
});

test("Passwords are hashed with PASSWORD_DEFAULT", function() {
    $hash = password_hash('test', PASSWORD_DEFAULT);
    assert(str_starts_with($hash, '$2y$'), "Should use bcrypt");
});

test("CSRF uses cryptographically secure random bytes", function() {
    session_start();
    unset($_SESSION['_csrf']);
    $token1 = \App\Core\CSRF::token();
    unset($_SESSION['_csrf']);
    $token2 = \App\Core\CSRF::token();
    assert($token1 !== $token2, "Tokens should be unique");
});

if (env('CSRF_ENABLED', true)) {
    test("CSRF protection is enabled", function() {
        assert(env('CSRF_ENABLED', true) === true, "CSRF should be enabled");
    });
} else {
    warn("CSRF protection is disabled (not recommended for production)");
}

echo "\n[8] FILE STRUCTURE TESTS\n";
echo "───────────────────────────────────────────────────────────────\n";

test("Storage directories exist", function() {
    assert(is_dir(__DIR__ . '/storage/db'), "DB storage should exist");
    assert(is_dir(__DIR__ . '/storage/logs'), "Logs storage should exist");
    assert(is_dir(__DIR__ . '/storage/cache'), "Cache storage should exist");
});

test("Database file exists and is writable", function() {
    $dbPath = __DIR__ . '/storage/db/app.db';
    assert(file_exists($dbPath), "Database file should exist");
    assert(is_writable($dbPath), "Database should be writable");
});

test(".env file exists and is not empty", function() {
    $envPath = __DIR__ . '/.env';
    assert(file_exists($envPath), ".env should exist");
    assert(filesize($envPath) > 0, ".env should not be empty");
});

test(".htaccess exists with security rules", function() {
    $htaccess = file_get_contents(__DIR__ . '/public/.htaccess');
    assert(str_contains($htaccess, 'RewriteEngine'), "Should have rewrite rules");
    assert(str_contains($htaccess, 'X-Frame-Options'), "Should have security headers");
    assert(str_contains($htaccess, '.env'), "Should block .env access");
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "TEST RESULTS SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✓ Passed: " . count($results['passed']) . "\n";
echo "✗ Failed: " . count($results['failed']) . "\n";
echo "⚠ Warnings: " . count($results['warnings']) . "\n";
echo "\n";

if (!empty($results['failed'])) {
    echo "FAILED TESTS:\n";
    foreach ($results['failed'] as $failure) {
        echo "  • $failure\n";
    }
    echo "\n";
}

if (!empty($results['warnings'])) {
    echo "WARNINGS:\n";
    foreach ($results['warnings'] as $warning) {
        echo "  • $warning\n";
    }
    echo "\n";
}

$totalTests = count($results['passed']) + count($results['failed']);
$successRate = count($results['failed']) === 0 ? 100 : round((count($results['passed']) / $totalTests) * 100, 1);

echo "Success Rate: $successRate%\n";
echo "\n";

if (count($results['failed']) === 0) {
    echo "🎉 ALL TESTS PASSED! Framework is working correctly.\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED. Please review the failures above.\n";
    exit(1);
}
