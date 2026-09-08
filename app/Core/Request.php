<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A read-only view of the incoming request.
 *
 * The host is read here rather than anywhere else in the application, because
 * the canonical URL and the default language both depend on it (.ir vs .com),
 * and a development hostname must never leak into a production canonical.
 */
final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $host,
        public readonly bool $secure,
        /** @var array<string,string> */
        public readonly array $query,
        /** @var array<string,mixed> */
        public readonly array $body,
        /** @var array<string,string> */
        public readonly array $cookies,
        public readonly string $userAgent,
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = '/' . trim(rawurldecode($path), '/');

        $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
        $forwardedProto = self::trustedForwardedHeader('HTTP_X_FORWARDED_PROTO');
        if ($forwardedProto !== null) {
            $https = strtolower($forwardedProto) === 'https';
        }

        return new self(
            method: strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            path: $path === '/' ? '/' : rtrim($path, '/'),
            host: self::resolveHost(),
            secure: $https,
            query: array_map(strval(...), array_filter($_GET, 'is_scalar')),
            body: self::readBody(),
            cookies: array_map(strval(...), array_filter($_COOKIE, 'is_scalar')),
            userAgent: substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        );
    }

    private static function resolveHost(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $host = strtolower(explode(':', $host)[0]);

        // Only accept a hostname shape; anything else is treated as unknown so
        // that a spoofed Host header cannot end up in a canonical tag.
        return preg_match('/^[a-z0-9.\-]+$/', $host) === 1 ? $host : '';
    }

    /**
     * Read a proxy header only when the application is explicitly configured to
     * sit behind a trusted proxy. Blindly trusting X-Forwarded-* lets any client
     * dictate the scheme and the rate-limit identity.
     */
    private static function trustedForwardedHeader(string $key): ?string
    {
        if (!Config::bool('APP_TRUST_PROXY', false)) {
            return null;
        }
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? trim(explode(',', $value)[0]) : null;
    }

    /** The client IP to use for rate limiting. */
    public function clientIp(): string
    {
        $forwarded = self::trustedForwardedHeader('HTTP_X_FORWARDED_FOR');
        $ip = $forwarded ?? (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    /** @return array<string,mixed> */
    private static function readBody(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw === false || $raw === '' || strlen($raw) > 1_048_576) {
                return [];
            }
            $decoded = json_decode($raw, true, 32, JSON_INVALID_UTF8_SUBSTITUTE);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    public function queryString(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return trim($value);
    }

    public function scheme(): string
    {
        return $this->secure ? 'https' : 'http';
    }

    public function isKnownHost(): bool
    {
        return $this->host !== '' && str_contains($this->host, '.');
    }
}
