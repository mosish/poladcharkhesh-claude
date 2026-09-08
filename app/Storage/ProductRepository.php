<?php

declare(strict_types=1);

namespace App\Storage;

use App\Domain\Families;
use App\Domain\Product;

/**
 * Catalogue access.
 *
 * Reads are served from a single memoised decode of `products.json`, which is
 * small enough (68 records) that filtering in PHP is far cheaper than the
 * indirection a query layer would add.
 */
final class ProductRepository
{
    /** @var list<Product>|null */
    private ?array $products = null;

    public function __construct(private readonly JsonStore $store)
    {
    }

    /** @return list<Product> */
    public function all(bool $includeArchived = false): array
    {
        if ($this->products === null) {
            $rows = $this->store->read('products');
            $this->products = array_values(array_map(
                static fn (array $row): Product => Product::fromArray($row),
                array_filter($rows, 'is_array')
            ));
        }

        if ($includeArchived) {
            return $this->products;
        }

        return array_values(array_filter($this->products, static fn (Product $p): bool => !$p->isArchived));
    }

    public function findBySlug(string $slug, bool $includeArchived = false): ?Product
    {
        foreach ($this->all($includeArchived) as $product) {
            if ($product->slug === $slug) {
                return $product;
            }
        }

        return null;
    }

    public function findById(string $id): ?Product
    {
        foreach ($this->all(true) as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    public function count(bool $includeArchived = false): int
    {
        return count($this->all($includeArchived));
    }

    /** @return list<Product> */
    public function featured(int $limit = 6): array
    {
        $featured = array_values(array_filter($this->all(), static fn (Product $p): bool => $p->featured));
        if (count($featured) < $limit) {
            foreach ($this->all() as $product) {
                if (count($featured) >= $limit) {
                    break;
                }
                if (!$product->featured) {
                    $featured[] = $product;
                }
            }
        }

        return array_slice($featured, 0, $limit);
    }

    /**
     * Families actually present in the catalogue, with their counts, in the
     * canonical taxonomy order.
     *
     * @return list<array{family:string,count:int}>
     */
    public function familyCounts(): array
    {
        $counts = [];
        foreach ($this->all() as $product) {
            $key = $product->family ?? 'uncategorised';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $ordered = [];
        foreach (Families::keys() as $family) {
            if (isset($counts[$family])) {
                $ordered[] = ['family' => $family, 'count' => $counts[$family]];
            }
        }
        if (isset($counts['uncategorised'])) {
            $ordered[] = ['family' => 'uncategorised', 'count' => $counts['uncategorised']];
        }

        return $ordered;
    }

    /** The bore range present in the catalogue, for the dimensional filter. */
    public function boreRange(): array
    {
        $values = array_filter(array_map(static fn (Product $p): ?float => $p->d, $this->all()));

        return $values === [] ? ['min' => 0.0, 'max' => 0.0] : ['min' => min($values), 'max' => max($values)];
    }

    /**
     * Filter and rank the catalogue.
     *
     * @param array{q?:string,family?:string,category?:string,industry?:string,dMin?:float|null,dMax?:float|null,DMax?:float|null} $criteria
     * @return list<Product>
     */
    public function search(array $criteria): array
    {
        $results = $this->all();

        $family = trim((string) ($criteria['family'] ?? ''));
        if ($family !== '') {
            $results = array_values(array_filter(
                $results,
                static fn (Product $p): bool => $p->family === $family
            ));
        }

        $category = trim((string) ($criteria['category'] ?? ''));
        if ($category !== '') {
            $results = array_values(array_filter(
                $results,
                static fn (Product $p): bool => $p->category === $category
            ));
        }

        $industry = trim((string) ($criteria['industry'] ?? ''));
        if ($industry !== '') {
            $results = array_values(array_filter(
                $results,
                static fn (Product $p): bool => in_array($industry, $p->industryIds, true)
            ));
        }

        $dMin = $criteria['dMin'] ?? null;
        $dMax = $criteria['dMax'] ?? null;
        if ($dMin !== null || $dMax !== null) {
            $results = array_values(array_filter($results, static function (Product $p) use ($dMin, $dMax): bool {
                if ($p->d === null) {
                    return false;
                }
                if ($dMin !== null && $p->d < $dMin) {
                    return false;
                }

                return !($dMax !== null && $p->d > $dMax);
            }));
        }

        $query = trim((string) ($criteria['q'] ?? ''));
        if ($query === '') {
            return $results;
        }

        return self::rank($results, $query);
    }

    /**
     * Rank by how strongly each product answers the query.
     *
     * A bearing designation is what people actually type, so an exact or
     * prefix match on the code outranks everything else by a wide margin. The
     * numeric core is compared separately, because "6204" should find
     * "6204-2RSH / 2RS1" and "NU 208" should find "NU208 ECP".
     *
     * @param list<Product> $products
     * @return list<Product>
     */
    private static function rank(array $products, string $query): array
    {
        $needle = self::normalise($query);
        $needleDigits = preg_replace('/\D+/', '', $needle) ?? '';

        $scored = [];
        foreach ($products as $product) {
            $score = self::score($product, $needle, $needleDigits);
            if ($score > 0) {
                $scored[] = ['score' => $score, 'product' => $product];
            }
        }

        usort($scored, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score']
                ?: strcmp($a['product']->code, $b['product']->code);
        });

        return array_values(array_map(static fn (array $r): Product => $r['product'], $scored));
    }

    private static function score(Product $product, string $needle, string $needleDigits): int
    {
        $code = self::normalise($product->code);
        $codeDigits = preg_replace('/\D+/', '', $code) ?? '';
        $score = 0;

        if ($code === $needle) {
            $score += 1000;
        } elseif (str_starts_with($code, $needle)) {
            $score += 600;
        } elseif (str_contains($code, $needle)) {
            $score += 400;
        }

        if ($needleDigits !== '' && strlen($needleDigits) >= 3) {
            if ($codeDigits === $needleDigits) {
                $score += 500;
            } elseif (str_starts_with($codeDigits, $needleDigits)) {
                $score += 300;
            } elseif (str_contains($codeDigits, $needleDigits)) {
                $score += 120;
            }
        }

        if (self::normalise($product->slug) === $needle) {
            $score += 800;
        }

        foreach ([$product->nameEn, $product->nameFa] as $name) {
            $haystack = self::normalise($name);
            if ($haystack === '') {
                continue;
            }
            if (str_contains($haystack, $needle)) {
                $score += 90;
            }
        }

        foreach ($product->brands as $brand) {
            if (str_contains(self::normalise($brand), $needle)) {
                $score += 60;
            }
        }

        $family = $product->family ?? '';
        if ($family !== '' && str_contains(self::normalise($family), $needle)) {
            $score += 70;
        }
        foreach ([Families::label($family, 'en'), Families::label($family, 'fa')] as $label) {
            if (str_contains(self::normalise($label), $needle)) {
                $score += 50;
            }
        }

        foreach (array_merge($product->applicationsEn, $product->applicationsFa) as $application) {
            if (str_contains(self::normalise($application), $needle)) {
                $score += 35;
                break;
            }
        }

        foreach ([$product->descriptionEn, $product->descriptionFa] as $description) {
            if ($description !== null && str_contains(self::normalise($description), $needle)) {
                $score += 15;
                break;
            }
        }

        // A bare number that matches a primary dimension is a real query:
        // "47" should surface bearings with a 47 mm outside diameter.
        if ($needleDigits !== '' && $needleDigits === $needle && strlen($needleDigits) <= 3) {
            $value = (float) $needleDigits;
            if ($product->d !== null && abs($product->d - $value) < 0.01) {
                $score += 55;
            }
            if ($product->D !== null && abs($product->D - $value) < 0.01) {
                $score += 40;
            }
        }

        return $score;
    }

    /** Lowercase, fold Persian/Arabic digits to Latin, strip separators. */
    public static function normalise(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            'ي' => 'ی', 'ك' => 'ک', 'ۀ' => 'ه', 'ة' => 'ه',
            '‌' => ' ',
        ]);

        return (string) preg_replace('/[\s\-_.\/,]+/u', '', $value);
    }

    /**
     * Products related to the given one: same family first, then nearest bore.
     *
     * @return list<Product>
     */
    public function related(Product $product, int $limit = 4): array
    {
        $candidates = array_values(array_filter(
            $this->all(),
            static fn (Product $p): bool => $p->slug !== $product->slug
        ));

        usort($candidates, static function (Product $a, Product $b) use ($product): int {
            $familyA = $a->family === $product->family ? 0 : 1;
            $familyB = $b->family === $product->family ? 0 : 1;
            if ($familyA !== $familyB) {
                return $familyA <=> $familyB;
            }
            $boreA = $a->d !== null && $product->d !== null ? abs($a->d - $product->d) : PHP_FLOAT_MAX;
            $boreB = $b->d !== null && $product->d !== null ? abs($b->d - $product->d) : PHP_FLOAT_MAX;

            return $boreA <=> $boreB;
        });

        return array_slice($candidates, 0, $limit);
    }
}
