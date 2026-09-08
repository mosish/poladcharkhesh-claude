<?php

declare(strict_types=1);

/**
 * The ambient hero visualisation.
 *
 * A single rotating bearing rendered from real catalogue dimensions. No
 * controls here — this one is atmosphere, and the section further down the page
 * is where the visualiser becomes an instrument you can operate.
 *
 * @var App\Core\View $this
 * @var App\Core\Locale $locale
 * @var App\Domain\Product $bearing
 */

use App\Domain\Product;
use App\Support\BearingPayload;

$payload = BearingPayload::build($bearing, $locale);
?>
<figure class="hero-visual">
  <div class="hero-visual__stage">
    <canvas class="hero-visual__canvas"
            data-bearing-canvas
            data-payload="hero-bearing-payload"
            data-mode="assembly"
            data-ambient="true"
            width="720" height="720"
            role="img"
            aria-label="<?= $this->e($bearing->name($locale)) ?>"></canvas>

    <noscript>
      <p class="hero-visual__fallback"><?= $this->e($locale->t('instrument.noCanvas')) ?></p>
    </noscript>
  </div>

  <figcaption class="hero-visual__caption">
    <span class="hero-visual__code code"><?= $this->e($bearing->designation()) ?></span>
    <span class="hero-visual__dims">
      <?= $this->e($locale->t('product.dimensionsShort')) ?>
      <span class="num"><?= $this->e($bearing->dimensionSummary() ?? '') ?></span>
      <span><?= $this->e($locale->t('spec.unitMm')) ?></span>
    </span>
  </figcaption>

  <script type="application/json" id="hero-bearing-payload"><?= $this->jsonBlock($payload) ?></script>
</figure>
