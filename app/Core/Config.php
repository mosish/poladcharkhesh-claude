<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Environment configuration.
 *
 * Values come from the real environment first, then from a `.env` file at the
 * project root. In production the security secrets are mandatory: the
 * application refuses to start without them rather than inventing values that
 * would silently rotate on every request and log every admin out.
 */
final class Config
{
    /** @var array<string,string> */
    private static array $values = [];

    private static bool $loaded = false;

    /** Secrets with no safe default. Required whenever APP_ENV=production. */
    private const REQUIRED_IN_PRODUCTION = [
        'APP_SESSION_SECRET',
        'APP_COOKIE_SECRET',
    ];

    public static function load(string $root): void
    {
        if (self::$loaded) {
            return;
        }

        $file = $root . '/.env';
        if (is_readable($file)) {
            self::$values = self::parse((string) file_get_contents($file));
        }

        self::$loaded = true;

        if (self::isProduction()) {
            self::assertProductionSecrets();
        }
    }

    /** @return array<string,string> */
    private static function parse(string $contents): array
    {
        $out = [];
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (strlen($value) >= 2) {
                $first = $value[0];
                if (($first === '"' || $first === "'") && str_ends_with($value, $first)) {
                    $value = substr($value, 1, -1);
                }
            }
            $out[$key] = $value;
        }

        return $out;
    }

    private static function assertProductionSecrets(): void
    {
        $missing = [];
        foreach (self::REQUIRED_IN_PRODUCTION as $key) {
            $value = self::get($key, '');
            if ($value === '' || strlen($value) < 32) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            // Fail fast. Never fabricate a runtime secret in production.
            http_response_code(500);
            error_log('[config] Missing or too-short production secrets: ' . implode(', ', $missing));
            exit('Server configuration incomplete. See docs/DEPLOYMENT.md.');
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $fromEnv = getenv($key);
        if ($fromEnv !== false && $fromEnv !== '') {
            return $fromEnv;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function env(): string
    {
        return self::get('APP_ENV', 'development') ?? 'development';
    }

    public static function isProduction(): bool
    {
        return self::env() === 'production';
    }

    /** Absolute path to the JSON data store, which lives outside the web root. */
    public static function dataPath(): string
    {
        $configured = self::get('APP_DATA_PATH');
        if ($configured !== null && $configured !== '') {
            return rtrim($configured, '/');
        }

        return APP_ROOT . '/data';
    }
}
