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
