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
