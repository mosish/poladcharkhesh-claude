<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Locale;
use App\Domain\Families;
use App\Domain\Product;

/**
 * The data handed to the client-side bearing renderer.
 *
 * Only figures that exist in the catalogue are sent. The renderer draws a
 * proportionally correct section from d, D and B, but the number of rolling
 * elements it lays out is derived from geometry for illustration — that count
 * is not published data, so the interface says so rather than presenting it as
 * a specification.
 */
final class BearingPayload
{
    /** @return array<string,mixed> */
    public static function build(Product $product, Locale $locale): array
    {
        return [
            'code' => $product->designation(),
            'name' => $product->name($locale),
            'family' => $product->family,
            'element' => (string) (Families::get($product->family)['element'] ?? 'ball'),
            'schematic' => $product->schematicType ?? Families::schematic($product->family),
            'd' => $product->d,
            'D' => $product->D,
            'B' => $product->B,
            'rMin' => $product->rMin,
            'speedGreaseRpm' => $product->speedGreaseRpm,
            'speedOilRpm' => $product->speedOilRpm,
            'referenceSpeedRpm' => $product->thermalSpeedRatingRpm,
            'sealed' => $product->sealing($locale) !== null && self::looksSealed($product->sealing($locale)),
            'labels' => [
                'bore' => $locale->t('instrument.boreLabel'),
                'od' => $locale->t('instrument.odLabel'),
                'width' => $locale->t('instrument.widthLabel'),
                'speed' => $locale->t('instrument.speedLabel'),
                'temp' => $locale->t('instrument.tempLabel'),
                'mm' => $locale->t('spec.unitMm'),
                'rpm' => $locale->t('spec.unitRpm'),
            ],
            'rtl' => $locale->isRtl(),
        ];
    }

    private static function looksSealed(?string $sealing): bool
    {
        if ($sealing === null) {
            return false;
        }
        $normalised = mb_strtolower($sealing, 'UTF-8');
        foreach (['2rs', 'rsh', 'rs1', '2z', 'zz', 'ddu', 'llu', 'nse', 'seal', 'آب‌بند', 'آب بند'] as $marker) {
            if (str_contains($normalised, $marker)) {
                return true;
            }
        }

        return false;
    }
}
