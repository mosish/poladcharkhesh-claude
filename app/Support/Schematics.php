<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Half-section symbols for each bearing family.
 *
 * Drawn to the convention used in manufacturer catalogues: the rotation axis
 * runs along the bottom as a chain line, the outer ring sits furthest from it,
 * the inner ring nearest, and the rolling element occupies the space between.
 * Reading one of these tells an engineer what the part is; they are symbols,
 * not decoration.
 *
 * Common geometry, in a 48 × 48 box:
 *   axis          y = 44
 *   outer ring    y = 8 … 16
 *   rolling body  y = 18 … 30
 *   inner ring    y = 32 … 40
 */
final class Schematics
{
    private const AXIS = '<path d="M3 44h6m3 0h6m3 0h6m3 0h6m3 0h6" stroke-width="1" opacity=".55"/>';

    private const SHAPES = [
        'deep-groove-ball' => '
            <rect x="8" y="8" width="32" height="8" rx="1"/>
            <rect x="8" y="32" width="32" height="8" rx="1"/>
            <circle cx="24" cy="24" r="6.5"/>
            <path d="M17.5 20.5a6.5 6.5 0 0 1 13 0" opacity=".4" stroke-width="1"/>',

        'angular-contact-ball' => '
            <path d="M8 8h32v8h-9l-6 6" stroke-linejoin="miter"/>
            <path d="M8 40h32v-8h-9l-6-6" stroke-linejoin="miter"/>
            <circle cx="24" cy="24" r="6.5"/>
            <path d="M14 34 34 14" stroke-width="1" stroke-dasharray="3 2.5" opacity=".75"/>',

        'self-aligning-ball' => '
            <path d="M8 8h32v8a16 16 0 0 1-32 0z"/>
            <rect x="8" y="32" width="32" height="8" rx="1"/>
            <circle cx="16" cy="25" r="5.5"/>
            <circle cx="32" cy="25" r="5.5"/>',

        'tapered-roller' => '
            <path d="M8 8h32v9l-3 1H11z" stroke-linejoin="miter"/>
            <path d="M8 40h32v-9l-3-1H11z" stroke-linejoin="miter"/>
            <path d="M14 19h20l-3 10H17z" stroke-linejoin="miter"/>',

        'spherical-roller' => '
            <path d="M8 8h32v8a16 16 0 0 1-32 0z"/>
            <rect x="8" y="32" width="32" height="8" rx="1"/>
            <path d="M11 20c3-2.5 7-2.5 10 0 0 4-1.5 7-5 8-3.5-1-5-4-5-8z" transform="rotate(-8 16 25)"/>
            <path d="M27 20c3-2.5 7-2.5 10 0 0 4-1.5 7-5 8-3.5-1-5-4-5-8z" transform="rotate(8 32 25)"/>',

        'toroidal-roller' => '
            <path d="M8 8h32v7a20 20 0 0 1-32 0z"/>
            <path d="M8 40h32v-7a20 20 0 0 0-32 0z"/>
            <path d="M13 21c5-3 17-3 22 0 0 3.5-2 5.5-11 5.5S13 24.5 13 21z"/>',

        'cylindrical-roller' => '
            <path d="M8 8h32v8h-4v3h-24v-3H8z" stroke-linejoin="miter"/>
            <rect x="8" y="32" width="32" height="8" rx="1"/>
            <rect x="13" y="20" width="22" height="9" rx="1.5"/>',

        'needle-roller' => '
            <rect x="8" y="8" width="32" height="6" rx="1"/>
            <rect x="8" y="34" width="32" height="6" rx="1"/>
            <rect x="12" y="16" width="4.5" height="16" rx="2"/>
            <rect x="21.75" y="16" width="4.5" height="16" rx="2"/>
            <rect x="31.5" y="16" width="4.5" height="16" rx="2"/>',

        'thrust-ball' => '
            <rect x="8" y="10" width="32" height="7" rx="1"/>
            <rect x="8" y="31" width="32" height="7" rx="1"/>
            <circle cx="15" cy="24" r="6"/>
            <circle cx="33" cy="24" r="6"/>',

        'spherical-thrust-roller' => '
            <path d="M8 10h32v5.5L8 20z" stroke-linejoin="miter"/>
            <path d="M8 38h32v-5.5L8 28z" stroke-linejoin="miter"/>
            <path d="M12 22.5c4-2.5 14-4.5 20-4.5 0 3-1 5-4.5 6-6 1.5-12 2-15.5 1z"/>',

        'bearing-unit' => '
            <path d="M6 40V22a18 18 0 0 1 36 0v18" stroke-linejoin="miter"/>
            <path d="M6 40h9M33 40h9"/>
            <circle cx="24" cy="23" r="11"/>
            <circle cx="24" cy="23" r="6"/>
            <circle cx="10" cy="36" r="2" stroke-width="1"/>
            <circle cx="38" cy="36" r="2" stroke-width="1"/>',

        'bearing-housing' => '
            <path d="M5 41V26c0-9 8.5-15 19-15s19 6 19 15v15z" stroke-linejoin="miter"/>
            <path d="M5 26h38" stroke-dasharray="3 2.5" opacity=".7" stroke-width="1"/>
            <circle cx="24" cy="26" r="10"/>
            <path d="M11 34h4M33 34h4" stroke-width="1"/>',

        'oil-seal' => '
            <path d="M8 9h32v10H22l-5 8v14H8z" stroke-linejoin="miter"/>
            <path d="M40 9v32H22" stroke-linejoin="miter" opacity=".55" stroke-width="1"/>
            <path d="M17 31c4 .5 7 2.5 8 5.5" stroke-width="1"/>
            <path d="M13 20h6" stroke-width="1" opacity=".6"/>',

        'lubricant' => '
            <path d="M24 6c5 8 8.5 12.5 8.5 17a8.5 8.5 0 0 1-17 0C15.5 18.5 19 14 24 6z"/>
            <path d="M12 34h24v6a2 2 0 0 1-2 2H14a2 2 0 0 1-2-2z" stroke-linejoin="miter"/>',
    ];

    public static function render(string $family, string $class = '', int $size = 48): string
    {
        $shape = self::SHAPES[$family] ?? null;
        if ($shape === null) {
            return Icons::render('bearing', $class, $size);
        }

        $withAxis = in_array($family, ['bearing-housing', 'lubricant'], true) ? '' : self::AXIS;

        $classAttr = $class === ''
            ? ''
            : ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

        return '<svg' . $classAttr . ' viewBox="0 0 48 48" width="' . $size . '" height="' . $size . '"'
            . ' fill="none" stroke="currentColor" stroke-width="1.6"'
            . ' stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $shape . $withAxis . '</svg>';
    }

    public static function exists(string $family): bool
    {
        return isset(self::SHAPES[$family]);
    }
}
