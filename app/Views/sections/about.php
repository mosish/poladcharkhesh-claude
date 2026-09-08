<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 * @var App\Storage\ContentRepository $content
 * @var App\Storage\ProductRepository $products
 */

$about = $content->section('about');
$tag = $locale->pick($about, 'tag');
$title = $locale->pick($about, 'title');
$p1 = $locale->pick($about, 'paragraph1');
$p2 = $locale->pick($about, 'paragraph2');
$points = $content->items('about', 'points');

$lang = $locale->language;
$bore = $products->boreRange();
?>
<section class="section section--surface section--ruled" id="about">
  <div class="container">
    <div class="split split--wide-start">

      <div class="stack stack-6 reveal">
        <div class="section-head" style="margin-block-end:0">
          <p class="eyebrow">
            <span class="eyebrow__index">04</span>
            <span><?= $this->e($tag !== '' ? $tag : $locale->t('nav.about')) ?></span>
          </p>
          <?php if ($title !== ''): ?>
            <h2 class="section-title"><?= $this->e($title) ?></h2>
          <?php endif; ?>
        </div>

        <?php if ($p1 !== ''): ?>
          <p class="section-lead"><?= $this->e($p1) ?></p>
        <?php endif; ?>
        <?php if ($p2 !== ''): ?>
          <p class="section-lead"><?= $this->e($p2) ?></p>
        <?php endif; ?>

        <?php if ($points !== []): ?>
          <ul class="about__points">
            <?php foreach ($points as $index => $point): ?>
              <?php
              $pointTitle = $locale->pick($point, 'title');
              $pointText = $locale->pick($point, 'text');
              if ($pointTitle === '') {
                  continue;
              }
              ?>
              <li>
                <span class="about__point-index"><?= sprintf('%02d', $index + 1) ?></span>
                <span>
                  <span class="about__point-title"><?= $this->e($pointTitle) ?></span>
                  <span class="about__point-text"><?= $this->e($pointText) ?></span>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <!-- A drawing-sheet title block. It states what the catalogue actually
           contains, in the language an engineer reads a drawing in. -->
      <div class="plate registered reveal" data-delay="1">
        <div class="plate__grid u-grid-bg" aria-hidden="true"></div>

        <div class="plate__title-block">
          <div class="plate__field">
            <span class="plate__field-label"><?= $this->e($lang === 'fa' ? 'شرکت' : 'Company') ?></span>
            <span class="plate__field-value" style="font-family:var(--font-sans)"><?= $this->e($company->name($lang)) ?></span>
          </div>
          <div class="plate__field">
            <span class="plate__field-label"><?= $this->e($lang === 'fa' ? 'شهر' : 'Location') ?></span>
            <span class="plate__field-value" style="font-family:var(--font-sans)"><?= $this->e($company->value($lang === 'fa' ? 'cityFa' : 'cityEn')) ?></span>
          </div>
          <div class="plate__field">
            <span class="plate__field-label"><?= $this->e($lang === 'fa' ? 'شمارهٔ فنی' : 'References') ?></span>
            <span class="plate__field-value"><?= $this->e((string) $products->count()) ?></span>
          </div>
          <div class="plate__field">
            <span class="plate__field-label"><?= $this->e($lang === 'fa' ? 'خانواده' : 'Families') ?></span>
            <span class="plate__field-value"><?= $this->e((string) count($products->familyCounts())) ?></span>
          </div>
          <div class="plate__field">
            <span class="plate__field-label"><?= $this->e($lang === 'fa' ? 'بازهٔ قطر داخلی' : 'Bore range') ?></span>
            <span class="plate__field-value">
              <?= $this->e(App\Domain\Product::mm($bore['min'])) ?>–<?= $this->e(App\Domain\Product::mm($bore['max'])) ?> mm
            </span>
          </div>
          <div class="plate__field">
            <span class="plate__field-label"><?= $this->e($lang === 'fa' ? 'استاندارد محاسبات' : 'Calculation basis') ?></span>
            <span class="plate__field-value">ISO 281</span>
          </div>
        </div>

        <p class="dim-rule" style="margin-block-start:var(--s-6)">
          <span class="dim-rule__cap"></span>
          <span><?= $this->e($lang === 'fa' ? 'کاتالوگ فنی' : 'Technical catalogue') ?></span>
          <span class="dim-rule__cap"></span>
        </p>
      </div>

    </div>
  </div>
</section>
