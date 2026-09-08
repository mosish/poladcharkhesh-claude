<?php

declare(strict_types=1);

namespace App\Domain;

use App\Core\Locale;

/**
 * A catalogue product.
 *
 * Engineering values are held exactly as they were verified against the
 * manufacturer catalogues. Nothing here derives, rounds or substitutes a
 * technical figure; absent data stays null so the UI can say "not specified"
 * instead of implying a value.
 */
final class Product
{
    /**
     * @param list<string>              $clearanceOptions
     * @param list<string>              $images
     * @param list<string>              $brands
     * @param list<string>              $applicationsFa
     * @param list<string>              $applicationsEn
     * @param list<string>              $industryIds
     * @param list<array<string,mixed>> $technicalSources
     */
    private function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $slug,
        public readonly string $category,
        public readonly ?string $family,
        public readonly string $nameFa,
        public readonly string $nameEn,
        public readonly ?string $descriptionFa,
        public readonly ?string $descriptionEn,
        public readonly bool $inStock,
        public readonly bool $featured,
        public readonly bool $isArchived,
        public readonly ?float $d,
        public readonly ?float $D,
        public readonly ?float $B,
        public readonly ?float $weightKg,
        public readonly ?float $crKn,
        public readonly ?float $corKn,
        public readonly ?float $speedGreaseRpm,
        public readonly ?float $speedOilRpm,
        public readonly ?float $thermalSpeedRatingRpm,
        public readonly ?string $cageMaterialFa,
        public readonly ?string $cageMaterialEn,
        public readonly ?string $sealingFa,
        public readonly ?string $sealingEn,
        public readonly array $clearanceOptions,
        public readonly ?string $schematicType,
        public readonly ?float $rMin,
        public readonly ?float $factorE,
        public readonly ?float $factorY,
        public readonly ?float $factorY0,
        public readonly ?float $factorY1,
        public readonly ?float $factorY2,
        public readonly ?float $factorF0,
        public readonly ?string $imageUrl,
        public readonly array $images,
        public readonly ?string $pdfUrl,
        public readonly array $brands,
        public readonly array $applicationsFa,
        public readonly array $applicationsEn,
        public readonly array $industryIds,
        public readonly array $technicalSources,
        public readonly ?string $metaTitleFa,
        public readonly ?string $metaTitleEn,
        public readonly ?string $metaDescriptionFa,
        public readonly ?string $metaDescriptionEn,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $updatedBy,
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromArray(array $row): self
    {
        $str = static fn (string $k): ?string => is_string($row[$k] ?? null) && $row[$k] !== '' ? $row[$k] : null;
        $num = static fn (string $k): ?float => is_numeric($row[$k] ?? null) ? (float) $row[$k] : null;
        $list = static function (string $k) use ($row): array {
            $value = $row[$k] ?? [];

            return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        };

        return new self(
            id: (string) ($row['id'] ?? ''),
            code: (string) ($row['code'] ?? ''),
            slug: (string) ($row['slug'] ?? ''),
            category: (string) ($row['category'] ?? ''),
            family: $str('family'),
            nameFa: (string) ($row['nameFa'] ?? ''),
            nameEn: (string) ($row['nameEn'] ?? ''),
            descriptionFa: $str('descriptionFa'),
            descriptionEn: $str('descriptionEn'),
            inStock: (bool) ($row['inStock'] ?? false),
            featured: (bool) ($row['featured'] ?? false),
            isArchived: (bool) ($row['isArchived'] ?? false),
            d: $num('d'),
            D: $num('D'),
            B: $num('B'),
            weightKg: $num('weightKg'),
            crKn: $num('crKn'),
            corKn: $num('corKn'),
            speedGreaseRpm: $num('speedGreaseRpm'),
            speedOilRpm: $num('speedOilRpm'),
            thermalSpeedRatingRpm: $num('thermalSpeedRatingRpm'),
            cageMaterialFa: $str('cageMaterialFa'),
            cageMaterialEn: $str('cageMaterialEn'),
            sealingFa: $str('sealingFa'),
            sealingEn: $str('sealingEn'),
            clearanceOptions: $list('clearanceOptions'),
            schematicType: $str('schematicType'),
            rMin: $num('rMin'),
            factorE: $num('factorE'),
            factorY: $num('factorY'),
            factorY0: $num('factorY0'),
            factorY1: $num('factorY1'),
            factorY2: $num('factorY2'),
            factorF0: $num('factorF0'),
            imageUrl: $str('imageUrl'),
            images: $list('images'),
            pdfUrl: $str('pdfUrl'),
            brands: $list('brands'),
            applicationsFa: $list('applicationsFa'),
            applicationsEn: $list('applicationsEn'),
            industryIds: $list('industryIds'),
            technicalSources: array_values(array_filter(
                is_array($row['technicalSources'] ?? null) ? $row['technicalSources'] : [],
                'is_array'
            )),
            metaTitleFa: $str('metaTitleFa'),
            metaTitleEn: $str('metaTitleEn'),
            metaDescriptionFa: $str('metaDescriptionFa'),
            metaDescriptionEn: $str('metaDescriptionEn'),
            createdAt: $str('createdAt'),
            updatedAt: $str('updatedAt'),
            updatedBy: $str('updatedBy'),
        );
    }

    public function name(Locale $locale): string
    {
        return $locale->language === Locale::FA
            ? ($this->nameFa !== '' ? $this->nameFa : $this->nameEn)
            : ($this->nameEn !== '' ? $this->nameEn : $this->nameFa);
    }

    public function description(Locale $locale): ?string
    {
        return $locale->language === Locale::FA
            ? ($this->descriptionFa ?? $this->descriptionEn)
            : ($this->descriptionEn ?? $this->descriptionFa);
    }

    /** @return list<string> */
    public function applications(Locale $locale): array
    {
        $primary = $locale->language === Locale::FA ? $this->applicationsFa : $this->applicationsEn;

        return $primary !== [] ? $primary : ($locale->language === Locale::FA ? $this->applicationsEn : $this->applicationsFa);
    }

    public function cageMaterial(Locale $locale): ?string
    {
        return $locale->language === Locale::FA
            ? ($this->cageMaterialFa ?? $this->cageMaterialEn)
            : ($this->cageMaterialEn ?? $this->cageMaterialFa);
    }

    public function sealing(Locale $locale): ?string
    {
        return $locale->language === Locale::FA
            ? ($this->sealingFa ?? $this->sealingEn)
            : ($this->sealingEn ?? $this->sealingFa);
    }

    public function familyLabel(Locale $locale, bool $short = false): string
    {
        return Families::label($this->family, $locale->language, $short);
    }

    /** The short designation used for display, without brand or note text. */
    public function designation(): string
    {
        $code = preg_replace('/\(.*?\)/u', '', $this->code) ?? $this->code;

        return trim($code);
    }

    /** `20 × 47 × 14` — the primary dimensions, or null if incomplete. */
    public function dimensionSummary(): ?string
    {
        if ($this->d === null || $this->D === null || $this->B === null) {
            return null;
        }

        return sprintf('%s × %s × %s', self::mm($this->d), self::mm($this->D), self::mm($this->B));
    }

    /** Format a millimetre value without trailing zeros. */
    public static function mm(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    public function primaryImage(): ?string
    {
        return $this->imageUrl ?? ($this->images[0] ?? null);
    }

    /** @return list<string> */
    public function gallery(): array
    {
        $all = $this->images;
        if ($this->imageUrl !== null && !in_array($this->imageUrl, $all, true)) {
            array_unshift($all, $this->imageUrl);
        }

        return array_values(array_unique($all));
    }

    public function hasVerifiedSource(): bool
    {
        foreach ($this->technicalSources as $source) {
            if (($source['verifiedAt'] ?? null) !== null && $source['verifiedAt'] !== '') {
                return true;
            }
        }

        return false;
    }

    public function url(): string
    {
        return '/product/' . rawurlencode($this->slug);
    }
}
