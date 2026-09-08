<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The bearing family taxonomy.
 *
 * Family is the axis that actually governs engineering behaviour: which load
 * directions a bearing accepts, which life exponent applies, and which
 * cross-section to draw. It is deliberately separate from the coarse
 * `category` used for catalogue grouping.
 *
 * `axial` describes what the family accepts in a plain arrangement:
 *   both  – locates in both directions
 *   one   – locates in one direction only
 *   limit – accepts axial load, but only a modest fraction of the radial load
 *   none  – must not be asked to carry axial load
 *
 * These flags drive the calculator's refusals (section 25 of the brief): an
 * unsupported configuration produces an engineering warning, never a number.
 */
final class Families
{
    public const BALL_EXPONENT = 3.0;
    public const ROLLER_EXPONENT = 10 / 3;

    /** @var array<string,array<string,mixed>> */
    private const MAP = [
        'deep-groove-ball' => [
            'labelEn' => 'Deep Groove Ball Bearings',
            'labelFa' => 'بلبرینگ شیار عمیق',
            'shortEn' => 'Deep groove ball',
            'shortFa' => 'شیار عمیق',
            'element' => 'ball',
            'exponent' => self::BALL_EXPONENT,
            'radial' => true,
            'axial' => 'both',
            'schematic' => 'deep-groove',
            'calculable' => true,
        ],
        'angular-contact-ball' => [
            'labelEn' => 'Angular Contact Ball Bearings',
            'labelFa' => 'بلبرینگ تماس زاویه‌ای',
            'shortEn' => 'Angular contact ball',
            'shortFa' => 'تماس زاویه‌ای',
            'element' => 'ball',
            'exponent' => self::BALL_EXPONENT,
            'radial' => true,
            'axial' => 'one',
            'schematic' => 'angular-contact',
            'calculable' => true,
        ],
        'self-aligning-ball' => [
            'labelEn' => 'Self-Aligning Ball Bearings',
            'labelFa' => 'بلبرینگ خودتنظیم',
            'shortEn' => 'Self-aligning ball',
            'shortFa' => 'خودتنظیم',
            'element' => 'ball',
            'exponent' => self::BALL_EXPONENT,
            'radial' => true,
            'axial' => 'limit',
            'schematic' => 'self-aligning-ball',
            'calculable' => true,
        ],
        'tapered-roller' => [
            'labelEn' => 'Tapered Roller Bearings',
            'labelFa' => 'رولبرینگ مخروطی',
            'shortEn' => 'Tapered roller',
            'shortFa' => 'مخروطی',
            'element' => 'roller',
            'exponent' => self::ROLLER_EXPONENT,
            'radial' => true,
            'axial' => 'one',
            'schematic' => 'tapered',
            'calculable' => true,
            // A radial load on a tapered roller bearing generates an axial
            // reaction, so the arrangement matters to the result.
            'inducedAxial' => true,
        ],
        'spherical-roller' => [
            'labelEn' => 'Spherical Roller Bearings',
            'labelFa' => 'رولبرینگ بشکه‌ای خودتنظیم',
            'shortEn' => 'Spherical roller',
            'shortFa' => 'بشکه‌ای',
            'element' => 'roller',
            'exponent' => self::ROLLER_EXPONENT,
            'radial' => true,
            'axial' => 'limit',
            'schematic' => 'spherical',
            'calculable' => true,
        ],
        'toroidal-roller' => [
            'labelEn' => 'Toroidal Roller Bearings (CARB)',
            'labelFa' => 'رولبرینگ توروییدال (CARB)',
            'shortEn' => 'Toroidal roller',
            'shortFa' => 'توروییدال',
            'element' => 'roller',
            'exponent' => self::ROLLER_EXPONENT,
            'radial' => true,
            'axial' => 'none',
            'schematic' => 'carb',
            'calculable' => true,
            'axialNoteEn' => 'A toroidal roller bearing is a non-locating bearing. It must not be asked to carry axial load.',
            'axialNoteFa' => 'رولبرینگ توروییدال یاتاقان آزاد است و نباید بار محوری به آن اعمال شود.',
        ],
        'cylindrical-roller' => [
            'labelEn' => 'Cylindrical Roller Bearings',
            'labelFa' => 'رولبرینگ استوانه‌ای',
            'shortEn' => 'Cylindrical roller',
            'shortFa' => 'استوانه‌ای',
            'element' => 'roller',
            'exponent' => self::ROLLER_EXPONENT,
            'radial' => true,
            'axial' => 'none',
            'schematic' => 'cylindrical',
            'calculable' => true,
            'axialNoteEn' => 'NU and N designs have no locating flanges and carry no axial load. NJ and NUP designs locate in one or both directions; check the designation before applying an axial load.',
            'axialNoteFa' => 'طرح‌های NU و N فاقد لبه هدایت‌کننده هستند و بار محوری تحمل نمی‌کنند. طرح‌های NJ و NUP در یک یا دو جهت مهار می‌کنند؛ پیش از اعمال بار محوری، کد فنی را بررسی کنید.',
        ],
        'needle-roller' => [
            'labelEn' => 'Needle Roller Bearings',
            'labelFa' => 'رولبرینگ سوزنی',
            'shortEn' => 'Needle roller',
            'shortFa' => 'سوزنی',
            'element' => 'roller',
            'exponent' => self::ROLLER_EXPONENT,
            'radial' => true,
            'axial' => 'none',
            'schematic' => 'needle',
            'calculable' => true,
            'axialNoteEn' => 'Needle roller bearings carry radial load only.',
            'axialNoteFa' => 'رولبرینگ سوزنی تنها بار شعاعی تحمل می‌کند.',
        ],
        'thrust-ball' => [
            'labelEn' => 'Thrust Ball Bearings',
            'labelFa' => 'بلبرینگ کف‌گرد',
            'shortEn' => 'Thrust ball',
            'shortFa' => 'کف‌گرد',
            'element' => 'ball',
            'exponent' => self::BALL_EXPONENT,
            'radial' => false,
            'axial' => 'one',
            'schematic' => 'thrust',
            'calculable' => true,
            'radialNoteEn' => 'Thrust ball bearings carry axial load only and must not be loaded radially.',
            'radialNoteFa' => 'بلبرینگ کف‌گرد تنها بار محوری تحمل می‌کند و نباید تحت بار شعاعی قرار گیرد.',
        ],
        'spherical-thrust-roller' => [
            'labelEn' => 'Spherical Roller Thrust Bearings',
            'labelFa' => 'رولبرینگ کف‌گرد بشکه‌ای',
            'shortEn' => 'Spherical roller thrust',
            'shortFa' => 'کف‌گرد بشکه‌ای',
            'element' => 'roller',
            'exponent' => self::ROLLER_EXPONENT,
            'radial' => true,
            'axial' => 'one',
            'schematic' => 'spherical-thrust',
            'calculable' => true,
            'radialNoteEn' => 'A radial load is permitted only alongside a substantially larger axial load. Check the manufacturer limit for the ratio.',
            'radialNoteFa' => 'بار شعاعی تنها همراه با بار محوری به‌مراتب بزرگ‌تر مجاز است. نسبت مجاز را از کاتالوگ سازنده بررسی کنید.',
        ],
        'bearing-unit' => [
            'labelEn' => 'Bearing Units',
            'labelFa' => 'یاتاقان کامل (بلبرینگ ژامبونی)',
            'shortEn' => 'Bearing unit',
            'shortFa' => 'یاتاقان کامل',
            'element' => 'ball',
            'exponent' => self::BALL_EXPONENT,
            'radial' => true,
            'axial' => 'limit',
            'schematic' => 'pillow-block',
            'calculable' => true,
        ],
        'bearing-housing' => [
            'labelEn' => 'Bearing Housings',
            'labelFa' => 'کرسی و هوزینگ بلبرینگ',
            'shortEn' => 'Housing',
            'shortFa' => 'هوزینگ',
            'element' => 'none',
            'exponent' => null,
            'radial' => false,
            'axial' => 'none',
            'schematic' => 'pillow-block',
            'calculable' => false,
        ],
        'oil-seal' => [
            'labelEn' => 'Industrial Oil Seals',
            'labelFa' => 'کاسه‌نمد صنعتی',
            'shortEn' => 'Oil seal',
            'shortFa' => 'کاسه‌نمد',
            'element' => 'none',
            'exponent' => null,
            'radial' => false,
            'axial' => 'none',
            'schematic' => 'oil-seal',
            'calculable' => false,
        ],
        'lubricant' => [
            'labelEn' => 'Specialised Lubricants',
            'labelFa' => 'روان‌کار تخصصی',
            'shortEn' => 'Lubricant',
            'shortFa' => 'روان‌کار',
            'element' => 'none',
            'exponent' => null,
            'radial' => false,
            'axial' => 'none',
            'schematic' => null,
            'calculable' => false,
        ],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    public static function exists(?string $family): bool
    {
        return $family !== null && isset(self::MAP[$family]);
    }

    /** @return array<string,mixed> */
    public static function get(?string $family): array
    {
        return self::MAP[$family] ?? [];
    }

    public static function label(?string $family, string $language, bool $short = false): string
    {
        $meta = self::get($family);
        if ($meta === []) {
            return $language === 'fa' ? 'دسته‌بندی‌نشده' : 'Uncategorised';
        }
        $prefix = $short ? 'short' : 'label';

        return (string) $meta[$prefix . ($language === 'fa' ? 'Fa' : 'En')];
    }

    /** The Lundberg–Palmgren life exponent, or null where life is not defined. */
    public static function exponent(?string $family): ?float
    {
        $value = self::get($family)['exponent'] ?? null;

        return is_float($value) ? $value : null;
    }

    public static function isCalculable(?string $family): bool
    {
        return (bool) (self::get($family)['calculable'] ?? false);
    }

    public static function acceptsRadial(?string $family): bool
    {
        return (bool) (self::get($family)['radial'] ?? false);
    }

    public static function axialCapability(?string $family): string
    {
        return (string) (self::get($family)['axial'] ?? 'none');
    }

    /** Family-specific engineering caveat for the active language, if any. */
    public static function note(?string $family, string $kind, string $language): ?string
    {
        $key = $kind . 'Note' . ($language === 'fa' ? 'Fa' : 'En');
        $value = self::get($family)[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public static function schematic(?string $family): ?string
    {
        $value = self::get($family)['schematic'] ?? null;

        return is_string($value) ? $value : null;
    }
}
