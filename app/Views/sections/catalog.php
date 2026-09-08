<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\ContentRepository $content
 * @var App\Storage\ProductRepository $products
 * @var list<App\Domain\Product> $featured
 * @var list<array{family:string,count:int}> $familyCounts
 */

use App\Domain\Families;
use App\Support\Icons;
use App\Support\Schematics;

$catalog = $content->section('catalog');
$tag = $locale->pick($catalog, 'tag');
$title = $locale->pick($catalog, 'title');
$description = $locale->pick($catalog, 'description');
$lang = $locale->language;
?>
<section class="section section--sunken section--ruled" id="catalog">
  <div class="container">

    <div class="section-head">
      <p class="eyebrow">
        <span class="eyebrow__index">05</span>
        <span><?= $this->e($tag !== '' ? $tag : $locale->t('nav.catalog')) ?></span>
      </p>
      <?php if ($title !== ''): ?>
        <h2 class="section-title"><?= $this->e($title) ?></h2>
      <?php endif; ?>
      <?php if ($description !== ''): ?>
        <p class="section-lead"><?= $this->e($description) ?></p>
      <?php endif; ?>
    </div>

    <h3 class="eyebrow" style="margin-block-end:var(--s-4)">
      <span><?= $this->e($locale->t('catalog.familiesTitle')) ?></span>
    </h3>

    <div class="family-grid">
      <?php foreach ($familyCounts as $index => $entry): ?>
        <a class="family-card reveal"
           data-delay="<?= (string) min(4, intdiv($index, 3)) ?>"
           href="<?= $this->e($app->url('/catalog', ['family' => $entry['family']])) ?>">
          <?= Schematics::render($entry['family'], 'family-card__schematic') ?>
          <span class="family-card__name"><?= $this->e(Families::label($entry['family'], $lang)) ?></span>
          <span class="family-card__count">
            <?= $this->e($locale->digits((string) $entry['count'])) ?>
            <?= $this->e($locale->t('catalog.countSuffix')) ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($featured !== []): ?>
      <div class="cluster" style="justify-content:space-between;margin-block:var(--s-12) var(--s-5)">
        <h3 class="eyebrow" style="margin:0">
          <span><?= $this->e($locale->t('catalog.featuredTitle')) ?></span>
        </h3>
        <a class="link-lead" href="<?= $this->e($app->url('/catalog')) ?>">
          <?= $this->e($locale->t('action.viewAll')) ?>
          <?= Icons::render('arrow', 'btn__icon btn__icon--arrow', 16) ?>
        </a>
      </div>

      <div class="grid grid--3">
        <?php foreach ($featured as $index => $product): ?>
          <?= $this->partial('partials/product-card', [
              'product' => $product,
              'delay' => min(4, intdiv($index, 3)),
          ]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>
