<?php

declare(strict_types=1);

/**
 * A catalogue card.
 *
 * Enough to identify the part technically — designation, family, principal
 * dimensions and provenance — and one route onward. No price, no cart, no
 * urgency: availability is a conversation, and the card says so.
 *
 * The whole card is clickable, but there is exactly one link in the accessible
 * tree: the "full specification" action stretches over the card rather than
 * wrapping the content in an anchor, so screen readers and keyboard users get
 * one clearly named target instead of a block of nested interactive content.
 *
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Domain\Product $product
 * @var int $delay
 */

use App\Support\Icons;
use App\Support\Schematics;

$delay ??= 0;
$dimensions = $product->dimensionSummary();
$url = $app->url($product->url());

// Only images the asset audit could actually decode are rendered. Everything
// else falls back to the family's technical schematic, which is a deliberate
// drawing of the right kind of part rather than a broken image.
$image = $media->firstUsable($product->gallery());
?>
<article class="card card--interactive card--linked reveal" data-delay="<?= (string) $delay ?>">
  <div class="frame <?= $image === null ? 'frame--schematic' : '' ?>">
    <?php if ($image !== null): ?>
      <img src="<?= $this->e($image) ?>"
           alt=""
           loading="lazy" decoding="async"
           width="480" height="360">
    <?php else: ?>
      <?= Schematics::render($product->family ?? '', 'frame__schematic', 96) ?>
    <?php endif; ?>
  </div>

  <div class="card__body">
    <div class="product-card__head">
      <span class="product-card__code"><?= $this->e($product->designation()) ?></span>
      <?php if ($product->hasVerifiedSource()): ?>
        <span class="badge badge--verified" title="<?= $this->e($locale->t('product.sourced')) ?>">
          <?= Icons::render('shield-check', '', 12) ?>
          <span class="visually-hidden"><?= $this->e($locale->t('product.sourced')) ?></span>
          <span aria-hidden="true"><?= $this->e($locale->t('product.sourcedShort')) ?></span>
        </span>
      <?php endif; ?>
    </div>

    <p class="product-card__name"><?= $this->e($product->name($locale)) ?></p>

    <span class="badge badge--family badge--self"><?= $this->e($product->familyLabel($locale, true)) ?></span>

    <?php if ($dimensions !== null): ?>
      <p class="product-card__dims">
        <span class="product-card__dims-label"><?= $this->e($locale->t('product.dimensionsShort')) ?></span>
        <span><?= $this->e($dimensions) ?></span>
        <span class="product-card__dims-label"><?= $this->e($locale->t('spec.unitMm')) ?></span>
      </p>
    <?php endif; ?>
  </div>

  <div class="product-card__foot">
    <span class="badge <?= $product->inStock ? 'badge--stock' : 'badge--enquire' ?>">
      <?= $this->e($product->inStock ? $locale->t('product.inStock') : $locale->t('product.enquireStock')) ?>
    </span>
    <a class="link-lead stretch-link" href="<?= $this->e($url) ?>">
      <span class="visually-hidden"><?= $this->e($product->designation()) ?> — </span>
      <?= $this->e($locale->t('action.viewDetails')) ?>
      <?= Icons::render('arrow', 'btn__icon btn__icon--arrow', 15) ?>
    </a>
  </div>
</article>
