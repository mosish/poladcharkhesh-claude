<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Locale;
use App\Domain\Families;
use App\Domain\Product;
use App\Storage\CompanyRepository;
use App\Storage\SeoRepository;

/**
 * Page metadata and structured data.
 *
 * Canonical URLs are built from the configured production hosts, never from the
 * request host, so a development or staging hostname cannot be published as a
 * canonical (section 32). Persian resolves to the .ir host and English to .com,
 * with reciprocal hreflang alternates.
 *
 * Nothing here emits price, offer, review or rating markup. The site is a
 * technical catalogue, not a shop, and inventing an aggregateRating would be
 * both false and a structured-data policy violation.
 */
final class Seo
{
    public function __construct(
        private readonly SeoRepository $seo,
        private readonly CompanyRepository $company,
    ) {
    }

    public function canonical(string $language, string $path): string
    {
        $host = $this->seo->canonicalHost($language);
        if ($host === '') {
            return '';
        }

        return 'https://' . $host . $this->normalisePath($path);
    }

    /** @return array<string,string> language => absolute URL */
    public function alternates(string $path): array
    {
        $out = [];
        foreach (Locale::SUPPORTED as $language) {
            $url = $this->canonical($language, $path);
            if ($url !== '') {
                $out[$language] = $url;
            }
        }

        return $out;
    }

    private function normalisePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * Resolve the title and description for a page.
     *
     * @param array{title?:string,description?:string} $overrides
     * @return array{title:string,description:string}
     */
    public function meta(Locale $locale, array $overrides = []): array
    {
        $siteTitle = $this->seo->defaultTitle($locale->language);
        $title = trim($overrides['title'] ?? '');
        $description = trim($overrides['description'] ?? '');

        if ($title === '') {
            $title = $siteTitle;
        } elseif ($siteTitle !== '' && !str_contains($title, $siteTitle)) {
            $suffix = $this->company->name($locale->language);
            if ($suffix !== '' && !str_contains($title, $suffix)) {
                $title .= ' | ' . $suffix;
            }
        }

        if ($description === '') {
            $description = $this->seo->defaultDescription($locale->language);
        }

        return [
            'title' => self::clip($title, 70),
            'description' => self::clip($description, 165),
        ];
    }

    public static function clip(string $value, int $max): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if (mb_strlen($value, 'UTF-8') <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1, 'UTF-8'), ' ،,.') . '…';
    }

    /**
     * Organization / LocalBusiness data, built from the one authoritative
     * company record so a phone number can never disagree with the footer.
     *
     * @return array<string,mixed>
     */
    public function organizationSchema(Locale $locale): array
    {
        $company = $this->company->get();
        $language = $locale->language;

        $phones = array_values(array_filter([
            self::telFromValue((string) ($company['primaryPhone'] ?? ''), '+98'),
            self::telFromValue((string) ($company['landlinePhone'] ?? ''), '+98'),
        ]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->company->legalName($language) ?: $this->company->name($language),
            'alternateName' => $this->company->name($language),
            'description' => $this->company->slogan($language),
            'url' => $this->canonical($language, '/'),
            'email' => (string) ($company['email'] ?? ''),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->company->address($language),
                'addressLocality' => (string) ($company[$language === 'fa' ? 'cityFa' : 'cityEn'] ?? ''),
                'addressCountry' => 'IR',
            ],
        ];

        if ($phones !== []) {
            $schema['telephone'] = $phones[0];
            $schema['contactPoint'] = array_map(static fn (string $tel): array => [
                '@type' => 'ContactPoint',
                'telephone' => $tel,
                'contactType' => 'sales',
                'availableLanguage' => ['fa', 'en'],
            ], $phones);
        }

        return array_filter($schema, static fn ($v): bool => $v !== '' && $v !== [] && $v !== null);
    }

    private static function telFromValue(string $raw, string $countryCode): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            return $countryCode . substr($digits, 1);
        }

        return '+' . $digits;
    }

    /**
     * Product structured data carrying genuine engineering properties.
     *
     * @return array<string,mixed>
     */
    public function productSchema(Product $product, Locale $locale): array
    {
        $properties = [];
        $add = static function (string $name, ?float $value, string $unitCode) use (&$properties): void {
            if ($value === null) {
                return;
            }
            $properties[] = array_filter([
                '@type' => 'PropertyValue',
                'name' => $name,
                'value' => $value,
                'unitCode' => $unitCode,
            ], static fn ($v): bool => $v !== '' && $v !== null);
        };

        $add('Bore diameter (d)', $product->d, 'MMT');
        $add('Outside diameter (D)', $product->D, 'MMT');
        $add('Width (B)', $product->B, 'MMT');
        $add('Basic dynamic load rating (Cr)', $product->crKn, 'B73');
        $add('Basic static load rating (C0r)', $product->corKn, 'B73');
        $add('Limiting speed, grease', $product->speedGreaseRpm, 'RPM');
        $add('Limiting speed, oil', $product->speedOilRpm, 'RPM');
        $add('Reference speed', $product->thermalSpeedRatingRpm, 'RPM');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name($locale),
            'sku' => $product->slug,
            'mpn' => $product->designation(),
            'description' => $product->description($locale) ?? '',
            'category' => Families::label($product->family, $locale->language),
            'url' => $this->canonical($locale->language, $product->url()),
            'additionalProperty' => $properties,
        ];

        $image = $product->primaryImage();
        if ($image !== null) {
            $schema['image'] = $this->absoluteAsset($locale->language, $image);
        }

        if ($product->weightKg !== null) {
            $schema['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $product->weightKg,
                'unitCode' => 'KGM',
            ];
        }

        if ($product->brands !== []) {
            $schema['brand'] = array_map(
                static fn (string $brand): array => ['@type' => 'Brand', 'name' => $brand],
                $product->brands
            );
        }

        $manufacturer = $product->technicalSources[0]['manufacturer'] ?? null;
        if (is_string($manufacturer) && $manufacturer !== '') {
            $schema['manufacturer'] = ['@type' => 'Organization', 'name' => $manufacturer];
        }

        // Deliberately no `offers`: there is no price and no purchase flow.
        return array_filter($schema, static fn ($v): bool => $v !== '' && $v !== [] && $v !== null);
    }

    /**
     * @param list<array{name:string,path:string}> $trail
     * @return array<string,mixed>
     */
    public function breadcrumbSchema(array $trail, string $language): array
    {
        $items = [];
        foreach ($trail as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $this->canonical($language, $crumb['path']),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    public function absoluteAsset(string $language, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->canonical($language, $path);
    }

    public function ogImage(string $language): string
    {
        $configured = $this->seo->value('ogImageUrl');

        return $configured === '' ? '' : $this->absoluteAsset($language, $configured);
    }
}
