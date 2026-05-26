<?php

declare(strict_types=1);

namespace DDG\Http;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly mixed $body,
        public readonly array $headers = ['Content-Type' => 'application/json; charset=utf-8'],
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self($status, $data);
    }

    public static function noContent(): self
    {
        return new self(204, null);
    }

    public static function created(mixed $data): self
    {
        return new self(201, $data);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }
        }

        if ($this->status === 204 || $this->body === null) {
            return;
        }

        $contentType = $this->headers['Content-Type'] ?? '';
        if (is_string($this->body) && !str_contains($contentType, 'application/json')) {
            echo $this->body;
            return;
        }

        echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
