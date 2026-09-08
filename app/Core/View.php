<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Template rendering.
 *
 * Templates are plain PHP files. They receive an explicit data array, are
 * rendered in an isolated scope, and get `$this` bound to the view so that
 * `$this->e()` is always at hand — output escaping is never optional.
 */
final class View
{
    private string $basePath;

    /** @var array<string,mixed> */
    private array $shared = [];

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? APP_PATH . '/Views';
    }

    /** @param array<string,mixed> $data */
    public function share(array $data): void
    {
        $this->shared = array_merge($this->shared, $data);
    }

    /**
     * Render a template inside the base layout.
     *
     * @param array<string,mixed> $data
     */
    public function page(string $template, array $data = [], string $layout = 'layout/base'): string
    {
        $content = $this->partial($template, $data);

        return $this->partial($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Render a template on its own.
     *
     * @param array<string,mixed> $data
     */
    public function partial(string $template, array $data = []): string
    {
        $path = $this->basePath . '/' . str_replace(['..', '\\'], '', $template) . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        $scope = array_merge($this->shared, $data);

        $render = function () use ($path, $scope): string {
            extract($scope, EXTR_SKIP);
            ob_start();
            require $path;

            return (string) ob_get_clean();
        };

        return $render->call($this);
    }

    /** Escape for HTML text and quoted attribute contexts. */
    public function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Escape a value destined for a URL query component. */
    public function u(?string $value): string
    {
        return rawurlencode($value ?? '');
    }

    /**
     * Serialise data for a `<script type="application/json">` island payload.
     * Closing tags are neutralised so page markup cannot be broken out of.
     *
     * @param mixed $data
     */
    public function jsonBlock($data): string
    {
        $encoded = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($encoded === false) {
            return '{}';
        }

        return str_replace(['<', '>', '&'], ['<', '>', '&'], $encoded);
    }

    /** Emit an attribute only when the condition holds. */
    public function when(bool $condition, string $attributes): string
    {
        return $condition ? $attributes : '';
    }

    /** Build a class list from a map of class => condition. */
    public function classes(array $map): string
    {
        $out = [];
        foreach ($map as $class => $enabled) {
            if (is_int($class)) {
                $out[] = (string) $enabled;
            } elseif ($enabled) {
                $out[] = $class;
            }
        }

        return implode(' ', array_filter($out));
    }
}
