<?php

declare(strict_types=1);

namespace DDG\Http;

use DDG\Exceptions\ValidationException;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        $query = $_GET ?? [];

        $body = [];
        $rawBody = file_get_contents('php://input');
        if ($rawBody !== false && $rawBody !== '') {
            $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ValidationException('JSON inválido no corpo da requisição.');
                }
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            } else {
                parse_str($rawBody, $body);
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr((string) $key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        return new self($method, $path, $query, $body, $headers);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function queryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }
}
