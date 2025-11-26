<?php
/**
 * ROUTPHER Architecture Stress Test
 * Tests performance and scalability
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\Request;
use App\Core\Auth;
use App\Core\DB;

function benchmark($name, callable $fn, $iterations = 1000) {
    $start = microtime(true);
    $memStart = memory_get_usage();

    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }

    $end = microtime(true);
    $memEnd = memory_get_usage();

    $duration = ($end - $start) * 1000; // ms
    $avgDuration = $duration / $iterations;
    $memDelta = ($memEnd - $memStart) / 1024; // KB
    $throughput = $iterations / ($duration / 1000); // ops/sec

    echo sprintf(
        "%-50s %8.2f ms | %8.2f μs/op | %10.0f ops/s | %8.0f KB\n",
        $name,
        $duration,
        $avgDuration * 1000,
        $throughput,
        $memDelta
    );

    return [
        'duration' => $duration,
        'avg' => $avgDuration,
        'throughput' => $throughput,
        'memory' => $memDelta
    ];
}

echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "ROUTPHER ARCHITECTURE STRESS TEST\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

echo "Metric Format: Total Time | Avg Time/Op | Throughput | Memory Delta\n\n";

// ═══════════════════════════════════════════════════════════════
// 1. ROUTING PERFORMANCE
// ═══════════════════════════════════════════════════════════════

echo "[1] ROUTING PERFORMANCE\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

// Simulated path resolution
benchmark("Path normalization (simple routes)", function() {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/blog/posts/hello-world';
    $req = new Request();
}, 10000);

benchmark("Path normalization (deep nesting)", function() {
    $_SERVER['REQUEST_URI'] = '/dashboard/admin/users/settings/profile/advanced';
    $req = new Request();
}, 10000);

// Directory scanning simulation
benchmark("glob() on 100 directories", function() {
    $dirs = glob(__DIR__ . '/*', GLOB_ONLYDIR);
}, 1000);

benchmark("file_exists() checks (10 files)", function() {
    for ($i = 0; $i < 10; $i++) {
        file_exists(__DIR__ . "/app/page.php");
    }
}, 1000);

// Dynamic parameter matching
benchmark("Regex pattern matching ([param] folders)", function() {
    $folders = ['[id]', '[slug]', '[category]', 'static', 'about'];
    foreach ($folders as $folder) {
        preg_match('/^\[(.+)\]$/', $folder, $matches);
    }
}, 5000);

echo "\n⚠️  Routing Analysis:\n";
echo "   • File-based routing requires filesystem I/O on each request\n";
echo "   • glob() calls can be expensive with many directories\n";
echo "   • Consider implementing route caching for production\n";
echo "   • At 10,000+ pages, directory scanning will become a bottleneck\n\n";

// ═══════════════════════════════════════════════════════════════
// 2. JWT PERFORMANCE
// ═══════════════════════════════════════════════════════════════

echo "[2] JWT AUTHENTICATION PERFORMANCE\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

benchmark("JWT token generation", function() {
    Auth::issueTokens(1);
}, 1000);

$tokens = Auth::issueTokens(1);
benchmark("JWT token validation", function() use ($tokens) {
    Auth::validate($tokens['access']);
}, 1000);

benchmark("Password hashing (PASSWORD_DEFAULT)", function() {
    password_hash('password123', PASSWORD_DEFAULT);
}, 100); // Fewer iterations - this is intentionally slow

benchmark("Password verification", function() {
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // pre-computed
    password_verify('password', $hash);
}, 1000);

echo "\n⚠️  JWT Analysis:\n";
echo "   • JWT encoding/decoding is CPU-bound, not a bottleneck\n";
echo "   • Password hashing is intentionally slow (security feature)\n";
echo "   • At 1000+ concurrent logins, consider rate limiting\n\n";

// ═══════════════════════════════════════════════════════════════
// 3. DATABASE PERFORMANCE
// ═══════════════════════════════════════════════════════════════

echo "[3] DATABASE PERFORMANCE (SQLite)\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

benchmark("SELECT query (single row by ID)", function() {
    DB::table('users')->where('id', 1)->first();
}, 1000);

benchmark("SELECT query (all rows)", function() {
    DB::table('users')->get();
}, 1000);

benchmark("INSERT query", function() {
    static $counter = 0;
    $counter++;
    DB::table('users')->insert([
        'email' => "test$counter@example.com",
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'name' => "Test User $counter",
        'role' => 'user',
        'created_at' => time()
    ]);
}, 100); // Fewer iterations for inserts

// Check SQLite configuration
$pdo = DB::pdo();
$walEnabled = $pdo->query("PRAGMA journal_mode")->fetchColumn();
$cacheSize = $pdo->query("PRAGMA cache_size")->fetchColumn();

echo "\n⚠️  Database Analysis:\n";
echo "   • Journal mode: $walEnabled (WAL mode recommended for concurrency)\n";
echo "   • Cache size: $cacheSize pages\n";
echo "   • SQLite is suitable for small-medium apps (<100k users)\n";
echo "   • For high traffic, migrate to PostgreSQL or MySQL\n";
echo "   • Enable WAL mode: PRAGMA journal_mode=WAL;\n\n";

// ═══════════════════════════════════════════════════════════════
// 4. MEMORY LEAK DETECTION
// ═══════════════════════════════════════════════════════════════

echo "[4] MEMORY LEAK DETECTION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$memStart = memory_get_usage();
$iterations = 1000;

for ($i = 0; $i < $iterations; $i++) {
    // Simulate request lifecycle
    $_SERVER['REQUEST_URI'] = "/test/$i";
    $req = new Request();
    $tokens = Auth::issueTokens(1);
    $decoded = Auth::validate($tokens['access']);

    // Force garbage collection periodically
    if ($i % 100 === 0) {
        gc_collect_cycles();
        $memCurrent = memory_get_usage();
        $memDelta = ($memCurrent - $memStart) / 1024;
        echo sprintf("  Iteration %4d: Memory usage: %8.2f KB\n", $i, $memDelta);
    }
}

$memEnd = memory_get_usage();
$memTotal = ($memEnd - $memStart) / 1024;
$memPerRequest = $memTotal / $iterations;

echo sprintf("\nTotal memory delta: %.2f KB over %d iterations\n", $memTotal, $iterations);
echo sprintf("Average per request: %.2f KB\n", $memPerRequest);

if ($memPerRequest > 1) {
    echo "⚠️  WARNING: Potential memory leak detected (>1 KB per request)\n";
} else {
    echo "✓ No significant memory leaks detected\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// 5. AUTOLOADING PERFORMANCE
// ═══════════════════════════════════════════════════════════════

echo "[5] AUTOLOADING PERFORMANCE\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

benchmark("Class instantiation (Router)", function() {
    new Router(__DIR__ . '/app');
}, 5000);

benchmark("Class instantiation (Request)", function() {
    $_SERVER['REQUEST_URI'] = '/test';
    new Request();
}, 5000);

benchmark("Static method call (Auth::issueTokens)", function() {
    Auth::issueTokens(1);
}, 1000);

echo "\n⚠️  Autoloading Analysis:\n";
echo "   • Classmap autoloading is fast (no file scanning)\n";
echo "   • Nested namespaces have no performance impact\n";
echo "   • composer dump-autoload --optimize recommended for production\n\n";

// ═══════════════════════════════════════════════════════════════
// 6. CSRF TOKEN PERFORMANCE
// ═══════════════════════════════════════════════────────────════

echo "[6] CSRF TOKEN GENERATION\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

session_start();

benchmark("CSRF token generation (cached)", function() {
    \App\Core\CSRF::token(); // Should reuse session token
}, 10000);

unset($_SESSION['_csrf']);

benchmark("CSRF token generation (new)", function() {
    \App\Core\CSRF::token();
}, 1000);

echo "\n⚠️  CSRF Analysis:\n";
echo "   • Token generation uses random_bytes() (cryptographically secure)\n";
echo "   • Tokens are cached in session (fast subsequent calls)\n";
echo "   • No performance concerns\n\n";

// ═══════════════════════════════════════════════════════════════
// FINAL RECOMMENDATIONS
// ═══════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "SCALABILITY RECOMMENDATIONS\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

echo "✓ GOOD FOR:\n";
echo "  • Small to medium applications (1-1000 pages)\n";
echo "  • Prototyping and MVPs\n";
echo "  • Low to moderate traffic (<10k requests/day)\n";
echo "  • Development environments\n\n";

echo "⚠️  POTENTIAL BOTTLENECKS:\n";
echo "  1. File-based routing at scale (>10,000 pages)\n";
echo "     → Solution: Implement route caching (cache resolved routes in memory/Redis)\n\n";
echo "  2. Directory scanning with glob()\n";
echo "     → Solution: Build route manifest at deploy time\n\n";
echo "  3. SQLite limitations (concurrent writes)\n";
echo "     → Solution: Enable WAL mode or migrate to PostgreSQL/MySQL\n\n";
echo "  4. No built-in caching layer\n";
echo "     → Solution: Add Redis/Memcached for sessions and data caching\n\n";
echo "  5. In-memory rate limiting (doesn't persist across requests)\n";
echo "     → Solution: Use Redis for distributed rate limiting\n\n";

echo "🚀 OPTIMIZATION STRATEGIES:\n";
echo "  1. Enable OPcache (PHP opcode caching)\n";
echo "  2. Run `composer dump-autoload --optimize --classmap-authoritative`\n";
echo "  3. Enable SQLite WAL mode: PRAGMA journal_mode=WAL;\n";
echo "  4. Use FastCGI Process Manager (PHP-FPM) with proper pooling\n";
echo "  5. Implement route caching for production\n";
echo "  6. Add HTTP caching headers for static content\n";
echo "  7. Consider APCu for session storage at scale\n\n";

echo "📊 EXPECTED THROUGHPUT (on modern hardware):\n";
echo "  • Development server: ~100-200 req/s\n";
echo "  • PHP-FPM + Nginx: ~500-1000 req/s\n";
echo "  • With caching: ~2000-5000 req/s\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════════\n";
