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
