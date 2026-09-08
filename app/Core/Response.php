<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string,string> $headers */
    private function __construct(
        public readonly string $body,
        public readonly int $status = 200,
        private array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** @param mixed $data */
    public static function json($data, int $status = 200): self
    {
        $encoded = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return new self($encoded === false ? '{}' : $encoded, $status, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public static function xml(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            // Baseline production headers. A Content-Security-Policy is applied
            // separately in public/index.php so it can be tested independently
            // without risking a broken page here.
            $defaults = [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Cross-Origin-Opener-Policy' => 'same-origin',
            ];

            foreach (array_merge($defaults, $this->headers) as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->body;
    }
}
