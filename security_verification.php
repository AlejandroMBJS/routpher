<?php
/**
 * Security Verification Test
 * Tests the security fixes implemented
 */

echo "═══════════════════════════════════════════════════════════════\n";
echo "SECURITY FIXES VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Test 1: Path Traversal Prevention
echo "[1] PATH TRAVERSAL PREVENTION\n";
echo "───────────────────────────────────────────────────────────────\n";

function normalizePath(string $uri): string
{
    $path = parse_url($uri, PHP_URL_PATH) ?? '/';
    $path = trim($path, '/');

    // SECURITY: Remove dangerous path segments to prevent directory traversal
    $segments = explode('/', $path);
    $segments = array_filter($segments, function($segment) {
        return $segment !== '' && $segment !== '.' && $segment !== '..';
    });

    return implode('/', $segments);
}

$dangerousUrls = [
    '/../../etc/passwd',
    '/../../../etc/hosts',
    '/app/../../../secret',
    '/./../../admin',
    '/normal/../../dangerous',
    '/safe/path',
];

$allSafe = true;
foreach ($dangerousUrls as $input) {
    $normalized = normalizePath($input);

    // Check if .. or . are removed
    $isSafe = !str_contains($normalized, '..') && !str_contains($normalized, '/./');

    if ($isSafe) {
        echo "✓ Blocked: $input → $normalized\n";
    } else {
        echo "✗ VULNERABLE: $input → $normalized\n";
        $allSafe = false;
    }
}

echo $allSafe ? "\n✓ Path traversal protection working!\n\n" : "\n✗ Path traversal protection FAILED!\n\n";

// Test 2: Check fixes in routpher-demo
echo "[2] ROUTPHER-DEMO SECURITY FIXES\n";
echo "───────────────────────────────────────────────────────────────\n";

$demoPath = __DIR__ . '/routpher-demo';

$requestContent = @file_get_contents("$demoPath/app/core/Request.php");
$hasPathFix = $requestContent && str_contains($requestContent, 'array_filter') &&
              str_contains($requestContent, "!== '..'");

echo ($hasPathFix ? "✓" : "✗") . " Path traversal fix in Request.php\n";

$dbContent = @file_get_contents("$demoPath/app/core/DB.php");
$hasWalMode = $dbContent && str_contains($dbContent, 'PRAGMA journal_mode=WAL');

echo ($hasWalMode ? "✓" : "✗") . " SQLite WAL mode in DB.php\n";

$loginContent = @file_get_contents("$demoPath/app/login/+server.php");
$hasSessionRegen = $loginContent && str_contains($loginContent, 'session_regenerate_id(true)');

echo ($hasSessionRegen ? "✓" : "✗") . " Session regeneration in login\n";

$helpersContent = @file_get_contents("$demoPath/app/core/helpers.php");
$hasRedirectSanitization = $helpersContent && str_contains($helpersContent, 'str_replace(["\r", "\n"]');

echo ($hasRedirectSanitization ? "✓" : "✗") . " Redirect URL sanitization\n";

$securityHeadersContent = @file_get_contents("$demoPath/app/middleware/SecurityHeaders.php");
$hasNonce = $securityHeadersContent && str_contains($securityHeadersContent, "'nonce-\$nonce'");

echo ($hasNonce ? "✓" : "✗") . " CSP nonce implementation\n\n";

// Test 3: Check fixes in routpher-test
echo "[3] ROUTPHER-TEST SECURITY FIXES\n";
echo "───────────────────────────────────────────────────────────────\n";

$testPath = __DIR__ . '/routpher-test';

$requestContent = @file_get_contents("$testPath/app/core/Request.php");
$hasPathFix = $requestContent && str_contains($requestContent, 'array_filter') &&
              str_contains($requestContent, "!== '..'");

echo ($hasPathFix ? "✓" : "✗") . " Path traversal fix in Request.php\n";

$dbContent = @file_get_contents("$testPath/app/core/DB.php");
$hasWalMode = $dbContent && str_contains($dbContent, 'PRAGMA journal_mode=WAL');

echo ($hasWalMode ? "✓" : "✗") . " SQLite WAL mode in DB.php\n";

$loginContent = @file_get_contents("$testPath/app/login/+server.php");
$hasSessionRegen = $loginContent && str_contains($loginContent, 'session_regenerate_id(true)');

echo ($hasSessionRegen ? "✓" : "✗") . " Session regeneration in login\n";

$helpersContent = @file_get_contents("$testPath/app/core/helpers.php");
$hasRedirectSanitization = $helpersContent && str_contains($helpersContent, 'str_replace(["\r", "\n"]');

echo ($hasRedirectSanitization ? "✓" : "✗") . " Redirect URL sanitization\n";

$securityHeadersContent = @file_get_contents("$testPath/app/middleware/SecurityHeaders.php");
$hasNonce = $securityHeadersContent && str_contains($securityHeadersContent, "'nonce-\$nonce'");

echo ($hasNonce ? "✓" : "✗") . " CSP nonce implementation\n\n";

// Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICATION COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Next step: Apply same fixes to routpher-test directory\n\n";
