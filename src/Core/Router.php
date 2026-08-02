<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $handler): void
    {
        $this->routes[] = [
            'method' => 'GET',
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function post(string $pattern, string $handler): void
    {
        $this->routes[] = [
            'method' => 'POST',
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($uri, $route['pattern']);
            if ($params !== null) {
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        throw AppException::notFound('Az oldal nem található');
    }

    private function matchRoute(string $uri, string $pattern): ?array
    {
        // Normalize pattern
        $pattern = '/' . trim($pattern, '/');
        if ($pattern !== '/') {
            $pattern = rtrim($pattern, '/');
        }

        // Exact match (no parameters)
        if ($pattern === $uri) {
            return [];
        }

        // Convert pattern parameters {param} to regex
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract only named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    private function callHandler(string $handler, array $params): void
    {
        [$controllerName, $method] = explode('@', $handler);

        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new AppException("Controller not found: {$controllerClass}", AppException::SERVER_ERROR);
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new AppException("Method not found: {$controllerClass}@{$method}", AppException::SERVER_ERROR);
        }

        call_user_func_array([$controller, $method], $params);
    }
}
