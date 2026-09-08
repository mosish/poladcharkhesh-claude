<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Inline SVG icons.
 *
 * Drawn to a single specification — 24×24 box, 1.5 stroke, round caps,
 * currentColor — so the set reads as one family. Inlined rather than loaded as
 * a sprite or icon font: there are few of them, they inherit colour for free,
 * and it removes a network request on a site whose visitors may be on slow
 * connections.
 */
final class Icons
{
    private const PATHS = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'arrow' => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
        'chevron-up' => '<path d="m6 15 6-6 6 6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'phone' => '<path d="M6.5 3h3l1.5 4-2 1.5a12 12 0 0 0 5.5 5.5L16 12l4 1.5v3a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4 6.2 2 2 0 0 1 6 4z"/>',
        'whatsapp' => '<path d="M4 20l1.4-4A8 8 0 1 1 8.5 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/>',
        'map-pin' => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
        'globe' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5a13 13 0 0 1 0 17 13 13 0 0 1 0-17z"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'file-text' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>',
        'calculator' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8.5 7.5h7"/><path d="M9 12h.01M12 12h.01M15 12h.01M9 16h.01M12 16h.01M15 16h.01"/>',
        'gauge' => '<path d="M4 16a8 8 0 1 1 16 0"/><path d="m12 16 4-4.5"/><circle cx="12" cy="16" r="1"/>',
        'layers' => '<path d="m12 3 8 4.5-8 4.5-8-4.5z"/><path d="m4 12.5 8 4.5 8-4.5"/>',
        'ruler' => '<rect x="3" y="8.5" width="18" height="7" rx="1.5"/><path d="M7.5 8.5v3M12 8.5v4M16.5 8.5v3"/>',
        'shield-check' => '<path d="M12 3.5 19 6v5.5c0 4.4-2.9 7.6-7 9-4.1-1.4-7-4.6-7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'bearing' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.5"/><circle cx="12" cy="6" r="1.4"/><circle cx="12" cy="18" r="1.4"/><circle cx="6" cy="12" r="1.4"/><circle cx="18" cy="12" r="1.4"/>',
        'alert' => '<path d="M12 4.5 21 19H3z"/><path d="M12 10v4"/><path d="M12 16.5h.01"/>',
        'headset' => '<path d="M5 15v-3a7 7 0 0 1 14 0v3"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/><path d="M19 20a3 3 0 0 1-3 3h-2"/>',
        'factory' => '<path d="M3 20V10l5 3.5V10l5 3.5V10l5 3.5V20z"/><path d="M18 10V4h3v16"/><path d="M7 20v-3.5M12 20v-3.5M17 20v-3.5"/>',
        'external' => '<path d="M14 4h6v6"/><path d="m20 4-8.5 8.5"/><path d="M19 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4"/>',
    ];

    public static function render(string $name, string $class = '', int $size = 24): string
    {
        $paths = self::PATHS[$name] ?? null;
        if ($paths === null) {
            return '';
        }

        $classAttr = $class === ''
            ? ''
            : ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

        return '<svg' . $classAttr . ' viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '"'
            . ' fill="none" stroke="currentColor" stroke-width="1.5"'
            . ' stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $paths . '</svg>';
    }

    public static function exists(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }
}
