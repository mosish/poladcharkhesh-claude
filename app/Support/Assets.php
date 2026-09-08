<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Asset URLs with a content fingerprint.
 *
 * The build writes a manifest mapping logical names to hashed filenames. When
 * the manifest is absent — during development — the plain path is served with
 * an mtime query so the browser still picks up edits.
 */
final class Assets
{
    /** @var array<string,string>|null */
    private ?array $manifest = null;

    public function __construct(private readonly string $publicPath)
    {
    }

    public function url(string $logicalPath): string
    {
        $logicalPath = '/' . ltrim($logicalPath, '/');

        if ($this->manifest === null) {
            $manifestPath = $this->publicPath . '/assets/manifest.json';
            $decoded = is_readable($manifestPath)
                ? json_decode((string) file_get_contents($manifestPath), true)
                : null;
            $this->manifest = is_array($decoded) ? array_filter($decoded, 'is_string') : [];
        }

        if (isset($this->manifest[$logicalPath])) {
            return $this->manifest[$logicalPath];
        }

        $file = $this->publicPath . $logicalPath;
        $mtime = is_readable($file) ? filemtime($file) : false;

        return $mtime === false ? $logicalPath : $logicalPath . '?v=' . $mtime;
    }

    /** Inline a small file directly into the document (used for critical CSS). */
    public function inline(string $logicalPath): string
    {
        $file = $this->publicPath . '/' . ltrim($logicalPath, '/');

        return is_readable($file) ? (string) file_get_contents($file) : '';
    }

    public function exists(string $logicalPath): bool
    {
        return is_readable($this->publicPath . '/' . ltrim($logicalPath, '/'));
    }
}
