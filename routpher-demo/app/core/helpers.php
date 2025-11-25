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
