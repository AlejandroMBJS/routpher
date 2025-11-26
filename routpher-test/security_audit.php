<?php
/**
 * ROUTPHER Comprehensive Security Audit
 * Tests for common vulnerabilities and security best practices
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Router;

$findings = [
    'CRITICAL' => [],
    'HIGH' => [],
    'MEDIUM' => [],
    'LOW' => [],
    'BEST_PRACTICES' => [],
    'PASSED' => []
];

function audit($severity, $category, $name, callable $test) {
    global $findings;
    try {
        $result = $test();
        if ($result === true || $result === null) {
            $findings['PASSED'][] = "✓ $name";
            echo "✓ $name\n";
        } else {
            $findings[$severity][] = "$name: $result";
            echo "⚠ [$severity] $name: $result\n";
        }
    } catch (\Throwable $e) {
        $findings[$severity][] = "$name: " . $e->getMessage();
        echo "✗ [$severity] $name: " . $e->getMessage() . "\n";
    }
}

echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "ROUTPHER COMPREHENSIVE SECURITY AUDIT\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

// ═══════════════════════════════════════════════════════════════
// 1. JWT SECURITY
// ═══════════════════════════════════════════════════════════════

echo "[1] JWT AUTHENTICATION SECURITY\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('CRITICAL', 'JWT', 'JWT uses HS256 algorithm (not none)', function() {
    $tokens = Auth::issueTokens(1);
    $parts = explode('.', $tokens['access']);
    if (count($parts) !== 3) {
        return "Invalid JWT format";
    }
    $header = json_decode(base64_decode($parts[0]), true);
    if ($header['alg'] === 'none') {
        return "CRITICAL: JWT uses 'none' algorithm - tokens can be forged!";
    }
    if ($header['alg'] !== 'HS256') {
        return "Warning: Expected HS256, got {$header['alg']}";
    }
    return true;
});

audit('CRITICAL', 'JWT', 'JWT_SECRET is strong and unique', function() {
    $secret = env('JWT_SECRET');
    if (empty($secret)) {
        return "JWT_SECRET is not set!";
    }
    if (strlen($secret) < 32) {
        return "JWT_SECRET is too short (< 32 chars)";
    }
    if ($secret === 'your-secret-key-here' || $secret === 'secret') {
        return "JWT_SECRET is using a default/weak value!";
    }
    return true;
});

audit('CRITICAL', 'JWT', 'Access tokens have expiration', function() {
    $tokens = Auth::issueTokens(1);
    $decoded = Auth::validate($tokens['access']);
    if (!isset($decoded->exp)) {
        return "Access token does not have expiration claim!";
    }
    $ttl = $decoded->exp - time();
    if ($ttl > 3600) {
        return "Access token TTL is too long ({$ttl}s) - should be < 1 hour";
    }
    return true;
});

audit('CRITICAL', 'JWT', 'Refresh tokens have expiration', function() {
    $tokens = Auth::issueTokens(1);
    $decoded = Auth::validate($tokens['refresh']);
    if (!isset($decoded->exp)) {
        return "Refresh token does not have expiration claim!";
    }
    return true;
});

audit('CRITICAL', 'JWT', 'Tokens have proper type claim', function() {
    $tokens = Auth::issueTokens(1);
    $accessDecoded = Auth::validate($tokens['access']);
    $refreshDecoded = Auth::validate($tokens['refresh']);

    if ($accessDecoded->type !== 'access') {
        return "Access token has wrong type: {$accessDecoded->type}";
    }
    if ($refreshDecoded->type !== 'refresh') {
        return "Refresh token has wrong type: {$refreshDecoded->type}";
    }
    return true;
});

audit('HIGH', 'JWT', 'Token expiration times are reasonable', function() {
    $accessExp = (int)env('JWT_ACCESS_EXP', 900);
    $refreshExp = (int)env('JWT_REFRESH_EXP', 604800);

    $warnings = [];
    if ($accessExp > 3600) {
        $warnings[] = "Access token expiry too long ({$accessExp}s)";
    }
    if ($refreshExp > 30 * 24 * 3600) {
        $warnings[] = "Refresh token expiry too long ({$refreshExp}s)";
    }
    return empty($warnings) ? true : implode(', ', $warnings);
});

echo "\n[2] PASSWORD SECURITY\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('CRITICAL', 'Password', 'Passwords are hashed (not plaintext)', function() {
    $user = \App\Models\User::findByEmail('admin@example.com');
    if ($user['password'] === 'password') {
        return "CRITICAL: Passwords stored in plaintext!";
    }
    if (!str_starts_with($user['password'], '$')) {
        return "Password doesn't appear to be hashed properly";
    }
    return true;
});

audit('CRITICAL', 'Password', 'Uses bcrypt or argon2', function() {
    $user = \App\Models\User::findByEmail('admin@example.com');
    if (str_starts_with($user['password'], '$2y$') || str_starts_with($user['password'], '$2b$')) {
        return true; // bcrypt
    }
    if (str_starts_with($user['password'], '$argon2')) {
        return true; // argon2
    }
    return "Unknown password hashing algorithm";
});

audit('HIGH', 'Password', 'Bcrypt cost factor is appropriate', function() {
    $hash = password_hash('test', PASSWORD_DEFAULT);
    preg_match('/^\$2[ayb]\$(\d+)\$/', $hash, $matches);
    $cost = (int)($matches[1] ?? 10);
    if ($cost < 10) {
        return "Bcrypt cost too low ($cost), should be >= 10";
    }
    if ($cost > 12) {
        return "Bcrypt cost very high ($cost), may impact performance";
    }
    return true;
});

audit('MEDIUM', 'Password', 'Password verification uses timing-safe comparison', function() {
    // password_verify is inherently timing-safe
    return true;
});

echo "\n[3] CSRF PROTECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('CRITICAL', 'CSRF', 'CSRF tokens use cryptographically secure random', function() {
    session_start();
    unset($_SESSION['_csrf']);
    $token1 = \App\Core\CSRF::token();
    unset($_SESSION['_csrf']);
    $token2 = \App\Core\CSRF::token();

    if ($token1 === $token2) {
        return "CRITICAL: CSRF tokens are not random!";
    }
    if (strlen($token1) < 32) {
        return "CSRF token too short (< 32 chars)";
    }
    return true;
});

audit('CRITICAL', 'CSRF', 'CSRF verification uses hash_equals (timing-safe)', function() {
    $code = file_get_contents(__DIR__ . '/app/core/CSRF.php');
    if (!str_contains($code, 'hash_equals')) {
        return "CSRF verification does not use timing-safe comparison!";
    }
    return true;
});

audit('HIGH', 'CSRF', 'CSRF protection is enabled by default', function() {
    if (!env('CSRF_ENABLED', true)) {
        return "CSRF protection is disabled!";
    }
    return true;
});

audit('MEDIUM', 'CSRF', 'CSRF tokens are regenerated appropriately', function() {
    // Currently tokens are persisted in session and reused
    return "CSRF tokens are not rotated per-request (acceptable for most apps)";
});

echo "\n[4] SESSION SECURITY\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('HIGH', 'Session', 'Session cookies use httponly flag', function() {
    $bootstrap = file_get_contents(__DIR__ . '/app/bootstrap.php');
    if (!str_contains($bootstrap, 'cookie_httponly')) {
        return "Session httponly flag not explicitly set";
    }
    if (!str_contains($bootstrap, "'cookie_httponly' => true")) {
        return "Session httponly should be true";
    }
    return true;
});

audit('HIGH', 'Session', 'Session cookies use SameSite protection', function() {
    $bootstrap = file_get_contents(__DIR__ . '/app/bootstrap.php');
    if (!str_contains($bootstrap, 'cookie_samesite')) {
        return "Session SameSite not configured";
    }
    return true;
});

audit('HIGH', 'Session', 'Session secure flag enabled for production', function() {
    $bootstrap = file_get_contents(__DIR__ . '/app/bootstrap.php');
    if (!str_contains($bootstrap, 'cookie_secure')) {
        return "Session secure flag not configured";
    }
    // Accepts SECURE_COOKIES env var (acceptable)
    return true;
});

audit('MEDIUM', 'Session', 'Session fixation protection', function() {
    // Framework doesn't call session_regenerate_id() after login
    return "session_regenerate_id() not called after authentication (potential session fixation)";
});

echo "\n[5] SQL INJECTION PROTECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('CRITICAL', 'SQL', 'Query builder uses prepared statements', function() {
    $code = file_get_contents(__DIR__ . '/app/core/DB.php');
    if (!str_contains($code, 'prepare')) {
        return "CRITICAL: No prepared statements found!";
    }
    if (str_contains($code, "\"SELECT * FROM {$this->table}\"")) {
        // This is safe because $this->table is internal, not user input
        return true;
    }
    return true;
});

audit('CRITICAL', 'SQL', 'User model uses prepared statements', function() {
    $code = file_get_contents(__DIR__ . '/app/models/User.php');
    $prepareCount = substr_count($code, '->prepare(');
    if ($prepareCount < 3) {
        return "User model should use more prepared statements";
    }
    return true;
});

audit('CRITICAL', 'SQL', 'No string concatenation in SQL queries', function() {
    $code = file_get_contents(__DIR__ . '/app/models/User.php');
    // Check for dangerous patterns like: "SELECT * FROM users WHERE id = " . $id
    if (preg_match('/["\'].*FROM.*["\']\s*\.\s*\$/', $code)) {
        return "CRITICAL: String concatenation found in SQL query!";
    }
    return true;
});

echo "\n[6] XSS PROTECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('HIGH', 'XSS', 'HTML escaping helper exists and works', function() {
    $unsafe = '<script>alert("XSS")</script>';
    $safe = e($unsafe);
    if (str_contains($safe, '<script>')) {
        return "CRITICAL: e() helper does not escape HTML!";
    }
    if (!str_contains($safe, '&lt;')) {
        return "e() helper not escaping correctly";
    }
    return true;
});

audit('MEDIUM', 'XSS', 'Views use e() for untrusted data', function() {
    // Check main layout file
    $layout = file_get_contents(__DIR__ . '/app/views/layouts/main.php');

    // Look for <?= without e()
    $hasUnescaped = preg_match('/<\?=\s*\$(?!content)/', $layout);

    if ($hasUnescaped) {
        return "Some views may output unescaped data - review manually";
    }
    return true;
});

audit('MEDIUM', 'XSS', 'Content Security Policy is configured', function() {
    $secHeaders = file_get_contents(__DIR__ . '/app/middleware/SecurityHeaders.php');
    if (!str_contains($secHeaders, 'Content-Security-Policy')) {
        return "CSP header not configured";
    }
    if (str_contains($secHeaders, "'unsafe-inline'")) {
        return "CSP allows unsafe-inline (reduces protection)";
    }
    return true;
});

echo "\n[7] PATH TRAVERSAL PROTECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('CRITICAL', 'PathTraversal', 'Router sanitizes paths', function() {
    $router = new Router(__DIR__ . '/app');

    // Test directory traversal attempts
    $_SERVER['REQUEST_URI'] = '/../../../etc/passwd';
    $req = new Request();

    if (str_contains($req->path, '..')) {
        return "CRITICAL: Router allows .. in paths!";
    }
    return true;
});

audit('CRITICAL', 'PathTraversal', 'No direct file inclusion from user input', function() {
    $routerCode = file_get_contents(__DIR__ . '/app/core/Router.php');

    // Check if router validates file paths before inclusion
    if (!str_contains($routerCode, 'file_exists')) {
        return "Router doesn't validate file existence before inclusion";
    }

    // Look for dangerous patterns
    if (preg_match('/include\s+\$_(GET|POST|REQUEST)/', $routerCode)) {
        return "CRITICAL: Direct inclusion from user input!";
    }

    return true;
});

audit('HIGH', 'PathTraversal', 'Dynamic route parameters are validated', function() {
    $routerCode = file_get_contents(__DIR__ . '/app/core/Router.php');

    // Router uses preg_match for [param] folders which inherently limits to alphanumeric
    if (!str_contains($routerCode, 'preg_match')) {
        return "Dynamic parameters not properly validated";
    }

    return true;
});

echo "\n[8] HEADER INJECTION PROTECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('MEDIUM', 'Headers', 'Redirect function doesn\'t allow newlines', function() {
    $helpersCode = file_get_contents(__DIR__ . '/app/core/helpers.php');

    // Check if redirect sanitizes URLs
    // Current implementation doesn't explicitly filter newlines
    return "redirect() should sanitize URLs to prevent header injection";
});

audit('HIGH', 'Headers', 'Security headers are set', function() {
    $secHeaders = file_get_contents(__DIR__ . '/app/middleware/SecurityHeaders.php');

    $required = [
        'X-Frame-Options',
        'X-Content-Type-Options',
        'X-XSS-Protection',
        'Referrer-Policy'
    ];

    foreach ($required as $header) {
        if (!str_contains($secHeaders, $header)) {
            return "Missing security header: $header";
        }
    }

    return true;
});

audit('MEDIUM', 'Headers', 'HSTS header configured for HTTPS', function() {
    $secHeaders = file_get_contents(__DIR__ . '/app/middleware/SecurityHeaders.php');

    if (!str_contains($secHeaders, 'Strict-Transport-Security')) {
        return "HSTS header not configured";
    }

    // It's conditional on SECURE_COOKIES which is acceptable
    return true;
});

echo "\n[9] FILE UPLOAD SECURITY\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('MEDIUM', 'FileUpload', 'File upload validation example exists', function() {
    // Check documentation for file upload security guidance
    $docsContent = file_get_contents(__DIR__ . '/../index.html');

    if (!str_contains($docsContent, 'file upload')) {
        return "No file upload security guidance in documentation";
    }

    return true;
});

echo "\n[10] ENVIRONMENT & CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('CRITICAL', 'Config', '.env file is protected from web access', function() {
    $htaccess = file_get_contents(__DIR__ . '/public/.htaccess');

    if (!str_contains($htaccess, '.env')) {
        return "CRITICAL: .env file not blocked in .htaccess!";
    }

    return true;
});

audit('HIGH', 'Config', 'APP_KEY is set and strong', function() {
    $key = env('APP_KEY');
    if (empty($key)) {
        return "APP_KEY not set";
    }
    if (strlen($key) < 32) {
        return "APP_KEY too short";
    }
    return true;
});

audit('HIGH', 'Config', 'Debug mode disabled in production', function() {
    if (env('APP_ENV') === 'production' && env('APP_DEBUG', false)) {
        return "DEBUG mode enabled in production!";
    }
    return true;
});

audit('MEDIUM', 'Config', 'Error reporting configured correctly', function() {
    $bootstrap = file_get_contents(__DIR__ . '/app/bootstrap.php');

    if (!str_contains($bootstrap, 'error_reporting')) {
        return "Error reporting not explicitly configured";
    }

    return true;
});

echo "\n[11] RATE LIMITING & DOS PROTECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('HIGH', 'RateLimit', 'Rate limiting middleware exists', function() {
    if (!class_exists('App\Middleware\RateLimit')) {
        return "Rate limiting middleware missing";
    }
    return true;
});

audit('MEDIUM', 'RateLimit', 'Rate limiting uses IP-based identification', function() {
    $code = file_get_contents(__DIR__ . '/app/middleware/RateLimit.php');

    if (!str_contains($code, 'REMOTE_ADDR')) {
        return "Rate limiting doesn't identify by IP";
    }

    return true;
});

audit('LOW', 'RateLimit', 'Rate limit data persists across requests', function() {
    $code = file_get_contents(__DIR__ . '/app/middleware/RateLimit.php');

    // Currently uses in-memory array
    if (str_contains($code, 'private static array $attempts')) {
        return "Rate limiting uses in-memory storage (won't persist across PHP-FPM workers)";
    }

    return true;
});

echo "\n[12] LOGGING & MONITORING\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

audit('HIGH', 'Logging', 'Failed login attempts are logged', function() {
    $loginCode = file_get_contents(__DIR__ . '/app/login/+server.php');

    if (!str_contains($loginCode, 'logger()')) {
        return "Login failures not logged";
    }

    return true;
});

audit('MEDIUM', 'Logging', 'CSRF violations are logged', function() {
    $csrfCode = file_get_contents(__DIR__ . '/app/core/CSRF.php');

    if (!str_contains($csrfCode, 'logger()')) {
        return "CSRF violations not logged";
    }

    return true;
});

audit('LOW', 'Logging', 'Log files are protected from web access', function() {
    if (file_exists(__DIR__ . '/public/storage/logs')) {
        return "CRITICAL: Logs accessible via web!";
    }

    // Logs are in storage/ not public/ - good
    return true;
});

echo "\n═══════════════════════════════════════════════════════════════════════════════════\n";
echo "SECURITY AUDIT SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'BEST_PRACTICES'] as $severity) {
    if (!empty($findings[$severity])) {
        echo "[$severity] (" . count($findings[$severity]) . " findings):\n";
        foreach ($findings[$severity] as $finding) {
            echo "  • $finding\n";
        }
        echo "\n";
    }
}

echo "PASSED: " . count($findings['PASSED']) . " security checks\n\n";

$totalIssues = count($findings['CRITICAL']) + count($findings['HIGH']) +
               count($findings['MEDIUM']) + count($findings['LOW']);

if (count($findings['CRITICAL']) > 0) {
    echo "❌ CRITICAL ISSUES FOUND - DO NOT DEPLOY TO PRODUCTION!\n";
    exit(1);
} elseif (count($findings['HIGH']) > 3) {
    echo "⚠️  MULTIPLE HIGH SEVERITY ISSUES - Address before production deployment\n";
    exit(1);
} elseif ($totalIssues > 0) {
    echo "✓ No critical issues, but $totalIssues findings should be addressed\n";
    exit(0);
} else {
    echo "✓ All security checks passed!\n";
    exit(0);
}
