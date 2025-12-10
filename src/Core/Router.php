<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable|string $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|string $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, callable|string $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = $this->parseUri($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->pathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                // Executar middlewares
                foreach ($route['middlewares'] as $key => $middleware) {
                    if (is_string($key) && is_array($middleware)) {
                        // Middleware com parâmetros: ['AuthMiddleware' => [true, false]]
                        $middlewareClass = $key;
                        $params = $middleware;
                        $reflection = new \ReflectionClass($middlewareClass);
                        $middlewareInstance = $reflection->newInstanceArgs($params);
                    } elseif (is_string($key) && is_string($middleware)) {
                        // Middleware com parâmetro string: ['CheckPermissionMiddleware' => 'manage_products']
                        $middlewareClass = $key;
                        $reflection = new \ReflectionClass($middlewareClass);
                        $middlewareInstance = $reflection->newInstance($middleware);
                    } else {
                        // Middleware simples
                        $middlewareInstance = new $middleware();
                    }
                    
                    if (!$middlewareInstance->handle()) {
                        return;
                    }
                }

                // Executar handler
                if (is_string($route['handler'])) {
                    [$controller, $method] = explode('@', $route['handler']);
                    $controllerInstance = new $controller();
                    call_user_func_array([$controllerInstance, $method], $matches);
                } else {
                    call_user_func_array($route['handler'], $matches);
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 - Página não encontrada";
    }

    private function parseUri(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        return $uri ?: '/';
    }

    private function pathToRegex(string $path): string
    {
        $path = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }
}

