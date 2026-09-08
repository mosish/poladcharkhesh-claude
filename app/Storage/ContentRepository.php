<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * Editable site copy.
 *
 * Persian and English are stored as separate fields and are never derived from
 * one another — no machine translation, in either direction. A section that has
 * not been written in one language falls back to the other at render time
 * rather than being auto-filled in the store.
 */
final class ContentRepository extends DocumentRepository
{
    protected function collection(): string
    {
        return 'content';
    }

    /**
     * The shape of the editable content. Values here are structural defaults
     * only; the real copy lives in the store and is edited from the admin panel.
     *
     * @return array<string,mixed>
     */
    protected function defaults(): array
    {
        return [
            'hero' => [
                'badgeFa' => '', 'badgeEn' => '',
                'titleHighlightFa' => '', 'titleHighlightEn' => '',
                'titleSuffixFa' => '', 'titleSuffixEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
                'searchPlaceholderFa' => '', 'searchPlaceholderEn' => '',
            ],
            'capabilities' => [
                'items' => [],
            ],
            'about' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'paragraph1Fa' => '', 'paragraph1En' => '',
                'paragraph2Fa' => '', 'paragraph2En' => '',
                'points' => [],
            ],
            'catalog' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
            ],
            'tools' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
            ],
            'industries' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
            ],
            'whyUs' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
                'items' => [],
            ],
            'support' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
            ],
            'contact' => [
                'tagFa' => '', 'tagEn' => '',
                'titleFa' => '', 'titleEn' => '',
                'descriptionFa' => '', 'descriptionEn' => '',
            ],
            'footer' => [
                'descriptionFa' => '', 'descriptionEn' => '',
                'copyrightFa' => '', 'copyrightEn' => '',
                'disclaimerFa' => '', 'disclaimerEn' => '',
            ],
        ];
    }

    /** @return array<string,mixed> */
    public function section(string $name): array
    {
        $value = $this->get()[$name] ?? [];

        return is_array($value) ? $value : [];
    }

    /** @return list<array<string,mixed>> */
    public function items(string $section, string $key = 'items'): array
    {
        $value = $this->section($section)[$key] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }
}
