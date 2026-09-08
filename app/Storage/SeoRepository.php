<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * Site-wide SEO defaults and the canonical domain configuration.
 */
final class SeoRepository extends DocumentRepository
{
    protected function collection(): string
    {
        return 'seo';
    }

    /** @return array<string,mixed> */
    protected function defaults(): array
    {
        return [
            'defaultTitleFa' => '', 'defaultTitleEn' => '',
            'defaultDescriptionFa' => '', 'defaultDescriptionEn' => '',
            'keywordsFa' => [], 'keywordsEn' => [],
            'organizationNameFa' => '', 'organizationNameEn' => '',
            'ogImageUrl' => '',
            'googleSiteVerification' => '',
            // Section 32: the Persian site is served from the .ir domain and the
            // English site from .com. Canonicals are built from these, never
            // from the request host, so a development hostname can never be
            // published as a canonical URL.
            'canonicalHostFa' => 'poladcharkhesh.ir',
            'canonicalHostEn' => 'poladcharkhesh.com',
        ];
    }

    public function canonicalHost(string $language): string
    {
        return $this->value($language === 'fa' ? 'canonicalHostFa' : 'canonicalHostEn');
    }

    public function defaultTitle(string $language): string
    {
        return $this->value($language === 'fa' ? 'defaultTitleFa' : 'defaultTitleEn');
    }

    public function defaultDescription(string $language): string
    {
        return $this->value($language === 'fa' ? 'defaultDescriptionFa' : 'defaultDescriptionEn');
    }

    /** @return list<string> */
    public function keywords(string $language): array
    {
        $value = $this->get()[$language === 'fa' ? 'keywordsFa' : 'keywordsEn'] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
