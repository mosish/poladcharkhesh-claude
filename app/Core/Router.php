<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A small pattern router. Segments written as `{name}` are captured and handed
 * to the handler as an associative array.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,names:list<string>,handler:callable}> */
    private array $routes = [];

    /** @var null|callable(Request):Response */
    private $fallback = null;

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function fallback(callable $handler): void
    {
        $this->fallback = $handler;
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $names = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $m) use (&$names): string {
                $names[] = $m[1];

                return '([^/]+)';
            },
            $pattern
        );

        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#u',
            'names' => $names,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $allowedForPath = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $request->method) {
                $allowedForPath[] = $route['method'];
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['names'] as $i => $name) {
                $params[$name] = $matches[$i] ?? '';
            }

            return ($route['handler'])($request, $params);
        }

        if ($allowedForPath !== []) {
            return Response::text('Method Not Allowed', 405)
                ->withHeader('Allow', implode(', ', array_unique($allowedForPath)));
        }

        if ($this->fallback !== null) {
            return ($this->fallback)($request);
        }

        return Response::text('Not Found', 404);
    }
}
