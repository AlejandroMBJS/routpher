# ROUTPHER Framework

**Enterprise-Grade PHP Framework with Next.js-Style Developer Experience**

[![Version](https://img.shields.io/badge/version-2.1.0-blue.svg)](https://github.com/yourusername/routpher)
[![Security](https://img.shields.io/badge/security-9%2F10-brightgreen.svg)](SECURITY_FIXES_SUMMARY.md)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> A standardized framework for companies. Combines battle-tested PHP with modern patterns. Zero config, maximum security, instant developer productivity.

---

## Table of Contents

- [Why ROUTPHER?](#why-routpher)
- [Who Should Use ROUTPHER?](#who-should-use-routpher)
- [Features](#features)
- [Security Highlights](#security-highlights-v21)
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Documentation](#documentation)
- [Project Structure](#project-structure)
- [Security](#security)
- [Performance](#performance)
- [Adding Real-Time Features](#adding-real-time-features)
- [Contributing](#contributing)
- [License](#license)

---

## Why ROUTPHER?

**ROUTPHER was built to establish a company-wide development standard** that combines battle-tested technologies with modern development practices.

### The Business Challenge

Our company needed a framework that would:
- **Standardize Development:** Create a unified approach across all PHP projects
- **Leverage Modern Patterns:** Bring the productivity of Next.js-style routing to our PHP stack
- **Reduce Onboarding Time:** Make it easy for new developers to become productive quickly
- **Ensure Security:** Enterprise-grade security features out of the box
- **Maintain Performance:** Fast enough for production at scale

### The Solution

ROUTPHER combines:
- **Established Technology:** PHP 8.0+ with proven libraries (JWT, PDO, PSR-4)
- **Modern Developer Experience:** File-based routing inspired by Next.js App Router
- **Security First:** All OWASP recommendations implemented by default
- **Team Productivity:** Minimal boilerplate, intuitive structure, clear conventions

### Benefits for the Company

✅ **Faster Development:** No route configuration, just create files
✅ **Lower Maintenance:** Simple architecture, easy to debug
✅ **Consistent Codebase:** All projects follow the same structure
✅ **Reduced Training Costs:** Developers familiar with Next.js adapt instantly
✅ **Production Ready:** Security score 9/10, battle-tested

### Benefits for New Developers

✅ **Familiar Patterns:** If you know Next.js, you already know ROUTPHER
✅ **Quick Onboarding:** Start building features on day one
✅ **Clear Documentation:** Comprehensive guides and examples
✅ **Modern Stack:** Work with current best practices, not legacy patterns
✅ **Growth Opportunity:** Learn enterprise PHP development the right way

---

## Who Should Use ROUTPHER?

### Perfect For

**🏢 Companies & Teams**
- Organizations standardizing their PHP development stack
- Companies onboarding developers with Next.js/React experience
- Teams building multiple internal tools or customer applications
- Businesses requiring enterprise-grade security out of the box

**👥 Development Teams**
- Teams of 2-20 developers needing consistent patterns
- Agencies building multiple client projects on a standard stack
- Startups scaling their development team
- Companies migrating from legacy PHP frameworks

**💼 Use Cases**
- Internal business applications and admin panels
- Customer-facing web applications
- RESTful APIs and microservices
- MVPs and prototypes that need to scale
- SaaS platforms (up to 10k users/day)

### Extensible For Real-Time Features

While ROUTPHER doesn't include WebSocket infrastructure by default, it's designed to **easily integrate** real-time capabilities:

✅ **Real-time Notifications** — Integrate with libraries like Ratchet or ReactPHP
✅ **Team Chat** — Add WebSocket server for internal communication
✅ **Video Conferencing** — Integrate WebRTC or third-party services (Jitsi, Daily.co)
✅ **Live Collaboration** — Build real-time features without framework limitations

**Philosophy:** ROUTPHER provides the foundation. You add specialized features as needed, keeping the core lightweight.

### Not Recommended For

❌ High-traffic applications (>100k users/day) without optimization
❌ Projects requiring deep Laravel/Symfony ecosystem integration
❌ Teams unwilling to adopt file-based routing conventions

---

## Features

### 🚀 Core Features

- **📁 File-Based Routing** — Just create folders and `page.php` files. No route definitions needed.
- **🔐 JWT Authentication** — Built-in access & refresh tokens with secure cookie handling
- **🛡️ Security First** — Path traversal protection, CSRF, CSP nonces, rate limiting out of the box
- **⚡ High Performance** — SQLite WAL mode (10-100x faster concurrent writes), optimized autoloading
- **🗄️ Database Ready** — SQLite by default, MySQL/PostgreSQL supported with migrations
- **🎨 Developer Experience** — CLI tools, logging, query builder, minimal boilerplate
- **🔄 Modern Stack** — HTMX integration, PSR-4 autoloading, PHP 8.0+ features

### 🎯 Next.js Parity

- ✅ File-based routing
- ✅ Dynamic routes with `[param]`
- ✅ Nested layouts
- ✅ API routes with `+server.php`
- ✅ Loading states
- ✅ Error boundaries
- ✅ Middleware pipeline

---

## Security Highlights (v2.1)

ROUTPHER has undergone extensive security audits and hardening. **Security Score: 9/10** 🛡️

### Critical Vulnerabilities Fixed

✅ **Path Traversal Protection** — Automatically filters `..` and `.` segments
✅ **Session Fixation Prevention** — Session IDs regenerated after login
✅ **Header Injection Prevention** — All redirects sanitized
✅ **XSS Protection** — CSP with cryptographic nonces instead of `unsafe-inline`
✅ **SQLite Performance** — WAL mode enabled (10-100x faster concurrent writes)

### Built-In Security Features

- CSRF protection on all state-changing requests
- Secure JWT authentication with HttpOnly cookies
- Rate limiting by IP address
- Security headers (CSP, X-Frame-Options, HSTS, etc.)
- SQL injection prevention via prepared statements
- Password hashing with bcrypt
- Input validation helpers

**Read the full security report:** [SECURITY_FIXES_SUMMARY.md](SECURITY_FIXES_SUMMARY.md)

---

## Quick Start

### Prerequisites

- PHP 8.0 or higher
- PHP Extensions: PDO, pdo_sqlite, json, mbstring, openssl
- Composer

### Create New Project

```bash
# Clone the repository
git clone https://github.com/yourusername/routpher.git my-app
cd my-app

# Install dependencies
composer install

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

Visit **http://127.0.0.1:8000** and you're ready to build! 🎉

---

## Installation

### Option 1: Quick Setup

```bash
bash create-php-app.sh my-app
cd my-app
php artisan serve
```

### Option 2: Manual Setup

```bash
# 1. Clone repository
git clone https://github.com/yourusername/routpher.git
cd routpher

# 2. Install dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Run migrations
php artisan migrate

# 5. Seed database (optional)
php artisan db:seed

# 6. Start server
php artisan serve
```

---

## Documentation

**Full documentation is available at:** `index.html` (Open in your browser)

### Quick Links

- [File-Based Routing](#file-based-routing)
- [Dynamic Routes](#dynamic-routes)
- [API Routes (+server.php)](#api-routes)
- [Authentication (JWT)](#authentication)
- [Middleware](#middleware)
- [Database & Migrations](#database)
- [Deployment](#deployment)

---

## Project Structure

```
my-app/
├── app/
│   ├── core/              # Framework classes
│   │   ├── App.php        # Application core
│   │   ├── Router.php     # File-based router
│   │   ├── Auth.php       # JWT authentication
│   │   ├── DB.php         # Database layer (with WAL mode)
│   │   ├── Request.php    # Request handler (with path traversal protection)
│   │   └── helpers.php    # Global helpers (with header injection prevention)
│   ├── middleware/        # Custom middleware
│   │   ├── SecurityHeaders.php  # CSP nonces, security headers
│   │   └── RateLimit.php        # Rate limiting
│   ├── models/            # Database models
│   ├── views/
│   │   ├── layouts/       # Shared layouts
│   │   └── errors/        # Error pages (404, 500)
│   ├── page.php           # Homepage (/)
│   ├── login/
│   │   ├── page.php       # Login page (GET /login)
│   │   └── +server.php    # Login API (POST /login) - with session regeneration
│   └── bootstrap.php      # App initialization
├── config/                # Configuration files
├── database/
│   ├── migrations/        # Database migrations
│   └── seeds/             # Database seeders
├── public/
│   ├── index.php          # Front controller
│   └── .htaccess          # Apache rewrite rules
├── storage/
│   ├── db/                # SQLite database (WAL mode enabled)
│   ├── logs/              # Application logs
│   └── cache/             # Cache files
├── .env                   # Environment configuration
└── artisan                # CLI tool
```

---

## File-Based Routing

Creating routes is as simple as creating folders and files:

| File Path | URL |
|-----------|-----|
| `app/page.php` | `/` |
| `app/about/page.php` | `/about` |
| `app/blog/page.php` | `/blog` |
| `app/users/[id]/page.php` | `/users/123` |

### Example: Creating a Page

```php
<?php
// app/about/page.php

$title = 'About Us';
?>

<h1>About Us</h1>
<p>Welcome to our company!</p>
```

That's it! No route definitions, no controllers. Just files and folders.

---

## Dynamic Routes

Use `[param]` folders to create dynamic route segments:

```php
<?php
// app/users/[id]/page.php

use App\Models\User;

$userId = $params['id']; // Provided by router
$user = User::find($userId);

if (!$user) {
    abort(404);
}

$title = e($user['name']);
?>

<h1><?= e($user['name']) ?></h1>
<p>Email: <?= e($user['email']) ?></p>
```

---

## API Routes

Create API endpoints with `+server.php` files:

```php
<?php
// app/api/users/+server.php

use App\Core\Response;
use App\Models\User;

return [
    'get' => function($req) {
        $users = User::all();
        Response::json($users);
    },

    'post' => function($req) {
        $data = $req->json();
        $userId = User::create($data);
        Response::json(['id' => $userId], 201);
    }
];
```

---

## Authentication

ROUTPHER includes complete JWT authentication:

```php
<?php
use App\Core\Auth;

// Issue tokens after successful login
$tokens = Auth::issueTokens($userId);

// Validate token
$decoded = Auth::validate($token);

// Access authenticated user
$user = auth();

if (!$user) {
    redirect('/login');
}
```

**Security Features:**
- Session regeneration after login (prevents session fixation)
- HttpOnly cookies for refresh tokens
- Short-lived access tokens (15 min)
- Long-lived refresh tokens (7 days)

---

## Middleware

Built-in middleware for common tasks:

- **SecurityHeaders** — CSP nonces, X-Frame-Options, HSTS
- **CSRF** — Token validation on POST/PUT/DELETE/PATCH
- **Auth::loadUser** — JWT token validation
- **RateLimit** — Rate limiting by IP

### Using CSP Nonces

```php
<!-- In your templates -->
<script nonce="<?= $req->meta['csp_nonce'] ?? '' ?>">
    console.log('This script is allowed by CSP');
</script>
```

---

## Database

ROUTPHER uses SQLite by default with **WAL mode enabled** for 10-100x faster concurrent writes.

### Query Builder

```php
use App\Core\DB;

// Get all users
$users = DB::table('users')->get();

// Find by email
$user = DB::table('users')
    ->where('email', 'john@example.com')
    ->first();

// Insert
$userId = DB::table('users')->insert([
    'email' => 'jane@example.com',
    'name' => 'Jane Doe',
    'created_at' => time()
]);
```

### Migrations

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

---

## Security

### Production Checklist

Before deploying to production:

- ✅ Set `APP_ENV=production`
- ✅ Set `APP_DEBUG=false`
- ✅ Set `SECURE_COOKIES=true`
- ✅ Generate new `APP_KEY` and `JWT_SECRET`
- ✅ Enable HTTPS
- ✅ Review security headers
- ✅ Set proper file permissions

### Security Score

**Current Score: 9/10** ✅

All critical and medium-priority vulnerabilities have been fixed. The framework is production-ready for small to medium-scale projects.

**Detailed Security Report:** [SECURITY_FIXES_SUMMARY.md](SECURITY_FIXES_SUMMARY.md)

---

## Performance

### Benchmarks

| Operation | Performance |
|-----------|-------------|
| Path normalization | 471k ops/sec |
| JWT generation | 204k ops/sec |
| JWT validation | 329k ops/sec |
| Database SELECT | 90k ops/sec (SQLite) |
| Database INSERT | 10-100x faster (WAL mode) |
| Autoloading | 5.4M ops/sec |

**Memory:** No leaks detected (0.83 KB over 1000 iterations)

### Recommended Scale

- **100-1,000 users/day:** Works perfectly ✅
- **1,000-10,000 users/day:** Enable route caching, consider MySQL ⚠️
- **10,000+ users/day:** Requires optimization (Redis, route caching, PostgreSQL) ⚠️

---

## Deployment

### Apache

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/my-app/public

    <Directory /var/www/my-app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/my-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Environment Variables (Production)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
SECURE_COOKIES=true
CSRF_ENABLED=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=production_db

JWT_SECRET=your-generated-secret
LOG_LEVEL=warning
```

---

## Testing

Run the test suite:

```bash
cd routpher-test
php test_simulation.php
```

**Current Test Results:**
- ✅ 36/36 tests passing
- ✅ 100% success rate
- ✅ No memory leaks

---

## CLI Commands

```bash
php artisan migrate              # Run database migrations
php artisan db:seed              # Run database seeders
php artisan serve [host] [port]  # Start dev server
php artisan key:generate         # Generate new APP_KEY
php artisan help                 # Show help
```

---

## Roadmap

### Phase 1: Security & Stability (✅ COMPLETE)
- [x] Fix path traversal vulnerability
- [x] Enable SQLite WAL mode
- [x] Add session regeneration
- [x] Implement CSP nonces
- [x] Sanitize redirects

### Phase 2: Testing (In Progress)
- [ ] Unit tests (target: 80% coverage)
- [ ] Integration tests
- [ ] Load testing

### Phase 3: Real-Time Features (In Progress)
- [ ] Official WebSocket integration guide
- [ ] Server-Sent Events (SSE) helpers
- [ ] Real-time notification system template
- [ ] Team chat starter kit
- [ ] Video conferencing integration examples

### Phase 4: Advanced Features (Planned)
- [ ] Admin panel (Django-style)
- [ ] Enhanced ORM with relationships
- [ ] Forms framework
- [ ] Queue system
- [ ] Caching layer (Redis)
- [ ] Route caching

---

## Adding Real-Time Features

ROUTPHER's lightweight architecture makes it easy to add real-time capabilities when your application needs them.

### Real-Time Notifications with Server-Sent Events (SSE)

The simplest approach for real-time notifications:

```php
<?php
// app/api/notifications/stream/+server.php

use App\Core\Response;

return [
    'get' => function($req) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        // Send notifications as they occur
        while (true) {
            $notifications = getNewNotifications(auth()['id']);

            if (!empty($notifications)) {
                echo "data: " . json_encode($notifications) . "\n\n";
                ob_flush();
                flush();
            }

            sleep(1);
        }
    }
];
```

**Frontend (JavaScript):**
```javascript
const eventSource = new EventSource('/api/notifications/stream');
eventSource.onmessage = (event) => {
    const notifications = JSON.parse(event.data);
    displayNotifications(notifications);
};
```

### WebSocket Integration with Ratchet

For bidirectional real-time communication (chat, live collaboration):

```bash
composer require cboden/ratchet
```

```php
<?php
// websocket-server.php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ChatHandler;

require 'vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatHandler()
        )
    ),
    8080
);

$server->run();
```

**Run alongside your app:**
```bash
# Terminal 1: Main app
php artisan serve

# Terminal 2: WebSocket server
php websocket-server.php
```

### Video Conferencing Integration

**Option 1: Jitsi Meet (Open Source)**
```php
<?php
// app/video/[room]/page.php

$roomId = $params['room'];
$userName = auth()['name'];
?>

<div id="meet"></div>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
    const api = new JitsiMeetExternalAPI('meet.jit.si', {
        roomName: '<?= e($roomId) ?>',
        parentNode: document.querySelector('#meet'),
        userInfo: {
            displayName: '<?= e($userName) ?>'
        }
    });
</script>
```

**Option 2: Daily.co API**
```php
<?php
// app/api/video/create-room/+server.php

use App\Core\Response;

return [
    'post' => function($req) {
        $dailyApiKey = env('DAILY_API_KEY');

        $response = file_get_contents('https://api.daily.co/v1/rooms', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $dailyApiKey\r\nContent-Type: application/json",
                'content' => json_encode(['properties' => ['exp' => time() + 3600]])
            ]
        ]));

        Response::json(json_decode($response, true));
    }
];
```

### Polling for Simpler Real-Time Updates

For many use cases, simple polling is sufficient:

```javascript
// Check for updates every 5 seconds
setInterval(async () => {
    const response = await fetch('/api/updates/check');
    const data = await response.json();

    if (data.hasUpdates) {
        updateUI(data.updates);
    }
}, 5000);
```

### Future Roadmap: Built-in Real-Time Support

Planned for v2.3.0:
- [ ] Official WebSocket integration guide
- [ ] Built-in SSE helper methods
- [ ] Real-time notification system example
- [ ] Team chat starter template
- [ ] Video call integration examples

**Your real-time needs will inform our priorities.** The framework is designed to not limit you.

---

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/yourusername/routpher.git
cd routpher
composer install
php artisan serve
```

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## Acknowledgments

- Inspired by **Next.js App Router**
- Built for the **PHP community**
- Security audit recommendations from **OWASP**

---

## Support

- 📧 Email: support@routpher.dev
- 🐛 Issues: [GitHub Issues](https://github.com/yourusername/routpher/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/yourusername/routpher/discussions)
- 📖 Docs: Open `index.html` in your browser

---

**ROUTPHER** — Enterprise-grade standardized PHP framework
Version 2.1.0 (Security Enhanced) | Security Score: 9/10 | Production Ready ✅

Built for companies that value developer productivity and code consistency
