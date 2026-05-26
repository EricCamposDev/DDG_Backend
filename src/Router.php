<?php

declare(strict_types=1);

namespace DDG;

use DDG\Exceptions\NotFoundException;
use DDG\Http\Request;
use DDG\Http\Response;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: callable, params: array<int, string>}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method;
        $path = $request->path;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['params'] as $name) {
                if (isset($matches[$name])) {
                    $params[$name] = $matches[$name];
                }
            }

            return ($route['handler'])($request, $params);
        }

        throw new NotFoundException(sprintf('Rota %s %s não encontrada.', $method, $path));
    }

    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        $params = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $pattern
        );

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'params' => $params,
        ];
    }
}
