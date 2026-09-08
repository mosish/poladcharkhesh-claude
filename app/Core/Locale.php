<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Language and direction resolution.
 *
 * Precedence: an explicit `?lang=` choice, then the visitor's stored
 * preference, then the domain (.ir serves Persian, .com serves English), then
 * Persian as the project default. A manual switch always wins over the domain,
 * which is what section 32 of the brief asks for.
 */
final class Locale
{
    public const FA = 'fa';
    public const EN = 'en';
    public const SUPPORTED = [self::FA, self::EN];
    public const COOKIE = 'pc_lang';

    /** @var array<string,mixed>|null */
    private ?array $strings = null;

    private function __construct(
        public readonly string $language,
        public readonly bool $explicitChoice,
    ) {
    }

    public static function resolve(Request $request): self
    {
        $requested = strtolower($request->queryString('lang'));
        if (in_array($requested, self::SUPPORTED, true)) {
            return new self($requested, true);
        }

        $stored = strtolower($request->cookies[self::COOKIE] ?? '');
        if (in_array($stored, self::SUPPORTED, true)) {
            return new self($stored, true);
        }

        return new self(self::fromHost($request->host), false);
    }

    public static function fromHost(string $host): string
    {
        if ($host === '') {
            return self::FA;
        }
        if (str_ends_with($host, '.ir')) {
            return self::FA;
        }
        if (str_ends_with($host, '.com')) {
            return self::EN;
        }

        return self::FA;
    }

    public function isRtl(): bool
    {
        return $this->language === self::FA;
    }

    public function dir(): string
    {
        return $this->isRtl() ? 'rtl' : 'ltr';
    }

    public function htmlLang(): string
    {
        return $this->language === self::FA ? 'fa-IR' : 'en';
    }

    public function other(): string
    {
        return $this->language === self::FA ? self::EN : self::FA;
    }

    /** Pick the field for the active language from a `...Fa` / `...En` pair. */
    public function pick(array $record, string $base, string $fallback = ''): string
    {
        $key = $base . ($this->language === self::FA ? 'Fa' : 'En');
        $value = $record[$key] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        $otherKey = $base . ($this->language === self::FA ? 'En' : 'Fa');
        $other = $record[$otherKey] ?? null;

        return is_string($other) && trim($other) !== '' ? $other : $fallback;
    }

    /** Translate a dotted key from the UI string table. */
    public function t(string $key, string $fallback = ''): string
    {
        if ($this->strings === null) {
            $path = APP_PATH . '/Resources/lang/' . $this->language . '.php';
            /** @var array<string,mixed> $loaded */
            $loaded = is_file($path) ? require $path : [];
            $this->strings = $loaded;
        }

        $node = $this->strings;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return $fallback !== '' ? $fallback : $key;
            }
            $node = $node[$segment];
        }

        return is_string($node) ? $node : ($fallback !== '' ? $fallback : $key);
    }

    /** @return list<string> */
    public function list(string $key): array
    {
        if ($this->strings === null) {
            $this->t($key);
        }

        $node = $this->strings;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return [];
            }
            $node = $node[$segment];
        }

        return is_array($node) ? array_values(array_filter($node, 'is_string')) : [];
    }

    /**
     * Render a number for display. Persian pages use Persian digits for prose
     * numbers; engineering values keep Latin digits so a bearing designation or
     * a millimetre figure stays machine-readable and copyable.
     */
    public function digits(string $value): string
    {
        if (!$this->isRtl()) {
            return $value;
        }

        return strtr($value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}
