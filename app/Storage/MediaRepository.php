<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * The registry of assets that are actually usable.
 *
 * `scripts/audit_assets.py` decodes every file the catalogue references and
 * records the result here. The interface asks this before rendering an image,
 * because a file can exist, serve with the right MIME type, and still be
 * undecodable — which is the state the reference repository's entire product
 * photography is in.
 *
 * When an image is not usable the card falls back to the family's technical
 * schematic. That is a deliberate choice over the two alternatives: a broken
 * image icon looks like neglect, and a stock photograph of some other bearing
 * would be a picture of a part the company is not selling.
 */
final class MediaRepository
{
    /** @var array<string,true>|null */
    private ?array $usable = null;

    public function __construct(private readonly JsonStore $store)
    {
    }

    public function isUsable(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        if ($this->usable === null) {
            /** @var array<string,mixed> $registry */
            $registry = $this->store->read('media', []);
            $list = $registry['usable'] ?? [];
            $map = [];
            if (is_array($list)) {
                foreach ($list as $entry) {
                    if (is_string($entry)) {
                        $map[$entry] = true;
                    }
                }
            }
            $this->usable = $map;
        }

        return isset($this->usable[$path]);
    }

    /**
     * The first usable image from a candidate list, or null.
     *
     * @param list<string> $candidates
     */
    public function firstUsable(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($this->isUsable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return list<array<string,mixed>> */
    public function broken(): array
    {
        /** @var array<string,mixed> $registry */
        $registry = $this->store->read('media', []);
        $broken = $registry['broken'] ?? [];

        return is_array($broken) ? array_values(array_filter($broken, 'is_array')) : [];
    }
}
