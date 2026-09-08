<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * A single-document collection: company details, CMS content, SEO defaults.
 *
 * Each of these is one authoritative record that the public site and the
 * structured data both read from, so a phone number or an address exists in
 * exactly one place.
 */
abstract class DocumentRepository
{
    public function __construct(protected readonly JsonStore $store)
    {
    }

    abstract protected function collection(): string;

    /** @return array<string,mixed> */
    abstract protected function defaults(): array;

    /** @return array<string,mixed> */
    public function get(): array
    {
        /** @var array<string,mixed> $stored */
        $stored = $this->store->read($this->collection(), []);

        return array_replace_recursive($this->defaults(), $stored);
    }

    /**
     * Merge a partial update into the document under an exclusive lock.
     *
     * @param array<string,mixed> $changes
     * @return array<string,mixed>
     */
    public function update(array $changes): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->store->mutate(
            $this->collection(),
            static fn (array $current): array => array_replace($current, $changes),
            $this->defaults()
        );

        return $result;
    }

    public function value(string $key, string $default = ''): string
    {
        $value = $this->get()[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
