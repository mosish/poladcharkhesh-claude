<?php

declare(strict_types=1);

namespace App\Storage;

use App\Core\Config;

/**
 * Flat-file JSON storage.
 *
 * The target host offers no database, so each collection is a JSON document on
 * disk. Two things make that safe enough for this workload:
 *
 *  - Writes go to a temporary file in the same directory and are then renamed
 *    into place. `rename()` is atomic within a filesystem, so a reader never
 *    observes a half-written document and a crash mid-write cannot truncate the
 *    live file.
 *  - Read-modify-write cycles hold an exclusive lock on a sidecar lock file for
 *    the whole cycle, so two concurrent inquiries cannot overwrite each other.
 *
 * The public surface is deliberately repository-shaped. If the host turns out
 * to provide `pdo_sqlite`, a SQLite driver can be dropped in behind the same
 * repositories without touching anything above this layer.
 */
final class JsonStore
{
    /** @var array<string,array<mixed>> */
    private array $cache = [];

    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? Config::dataPath() . '/store';
    }

    private function path(string $collection): string
    {
        if (preg_match('/^[a-z][a-z0-9_-]*$/', $collection) !== 1) {
            throw new \InvalidArgumentException("Invalid collection name: {$collection}");
        }

        return $this->root . '/' . $collection . '.json';
    }

    /**
     * Read a collection. Results are memoised for the lifetime of the request.
     *
     * @return array<mixed>
     */
    public function read(string $collection, array $default = []): array
    {
        if (isset($this->cache[$collection])) {
            return $this->cache[$collection];
        }

        $path = $this->path($collection);
        if (!is_readable($path)) {
            return $this->cache[$collection] = $default;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return $this->cache[$collection] = $default;
        }

        try {
            flock($handle, LOCK_SH);
            $raw = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        if (!is_string($raw) || trim($raw) === '') {
            return $this->cache[$collection] = $default;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            // A corrupt store is reported, never silently replaced with empty
            // data — that would look like a successful load of nothing.
            error_log("[store] {$collection}.json is not valid JSON");

            throw new StoreException("Data store '{$collection}' is unreadable.");
        }

        return $this->cache[$collection] = $decoded;
    }

    /**
     * Replace a collection wholesale.
     *
     * @param array<mixed> $data
     */
    public function write(string $collection, array $data): void
    {
        $path = $this->path($collection);
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0o770, true) && !is_dir($dir)) {
            throw new StoreException("Cannot create data directory: {$dir}");
        }

        $encoded = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($encoded === false) {
            throw new StoreException("Cannot encode data for '{$collection}'.");
        }

        $temp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temp, $encoded . "\n", LOCK_EX) === false) {
            throw new StoreException("Cannot write temporary file for '{$collection}'.");
        }

        @chmod($temp, 0o660);

        if (!rename($temp, $path)) {
            @unlink($temp);

            throw new StoreException("Cannot commit write for '{$collection}'.");
        }

        $this->cache[$collection] = $data;
    }

    /**
     * Read, transform and write a collection while holding an exclusive lock for
     * the whole cycle.
     *
     * @param callable(array<mixed>):array<mixed> $mutator
     * @return array<mixed> the written data
     */
    public function mutate(string $collection, callable $mutator, array $default = []): array
    {
        $lockPath = $this->path($collection) . '.lock';
        $dir = dirname($lockPath);
        if (!is_dir($dir) && !mkdir($dir, 0o770, true) && !is_dir($dir)) {
            throw new StoreException("Cannot create data directory: {$dir}");
        }

        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            throw new StoreException("Cannot acquire lock for '{$collection}'.");
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new StoreException("Cannot acquire lock for '{$collection}'.");
            }

            unset($this->cache[$collection]);
            $current = $this->read($collection, $default);
            $next = $mutator($current);
            $this->write($collection, $next);

            return $next;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function exists(string $collection): bool
    {
        return is_readable($this->path($collection));
    }

    public function lastModified(string $collection): ?int
    {
        $path = $this->path($collection);
        $time = is_readable($path) ? filemtime($path) : false;

        return $time === false ? null : $time;
    }
}
