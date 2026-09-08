<?php

declare(strict_types=1);

/**
 * The Polad Charkhesh emblem.
 *
 * Geometry carried over unchanged from the existing brand mark: a lubrication
 * droplet above an industrial gear with a concentric hub. Only the rendering
 * was rebuilt — flat colour instead of a gradient and drop shadow, so the mark
 * stays crisp at 38 px in the header and prints cleanly on a datasheet.
 *
 * @var App\Core\View $this
 * @var string $class
 * @var bool $onDark
 */

$class ??= 'brand__mark';
$onDark ??= false;
$fill = $onDark ? '#ffffff' : '#232c86';
$cut = $onDark ? '#0d1233' : '#ffffff';
?>
<svg class="<?= $this->e($class) ?>" viewBox="0 0 500 500" fill="none" aria-hidden="true" focusable="false">
  <path d="M250 15c0 0-45 105-90 175-35 55-65 80-65 80h310c0 0-30-25-65-80-45-70-90-175-90-175z" fill="<?= $this->e($fill) ?>"/>
  <path d="M285 85l60 135-15 15-60-135z" fill="<?= $this->e($cut) ?>" opacity=".95"/>
  <path d="M70 275h75v45h30l5 25h30l5 20h70l5-20h30l5-25h30v-45h75v55h-35l-10 30 25 20-25 30-35-10-20 25 5 30-35 10-20-25-30 5-30-5-20 25-35-10 5-30-20-25-35 10-25-30 25-20-10-30H70z" fill="<?= $this->e($fill) ?>"/>
  <circle cx="250" cy="335" r="52" fill="<?= $this->e($cut) ?>"/>
  <circle cx="250" cy="335" r="32" fill="<?= $this->e($fill) ?>"/>
  <circle cx="250" cy="335" r="14" fill="<?= $this->e($cut) ?>"/>
</svg>
