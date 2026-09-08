<?php

declare(strict_types=1);

/**
 * The engineering visualiser.
 *
 * Four views of the same bearing, drawn from its real principal dimensions:
 * the running assembly, a half section through the raceways, an ISO dimension
 * overlay, and a speed-versus-temperature estimate. The thermal view carries
 * its caveat in the interface, not in a footnote — an indicative figure that
 * looks authoritative is worse than no figure.
 *
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Domain\Product|null $instrumentBearing
 */

use App\Domain\Product;
use App\Support\BearingPayload;
use App\Support\Icons;

$bearing = $instrumentBearing ?? null;
if ($bearing === null) {
    return;
}

$payload = BearingPayload::build($bearing, $locale);

$modes = [
    ['key' => 'assembly', 'label' => $locale->t('instrument.modeAssembly')],
    ['key' => 'cutaway', 'label' => $locale->t('instrument.modeCutaway')],
    ['key' => 'dimensions', 'label' => $locale->t('instrument.modeDimensions')],
    ['key' => 'thermal', 'label' => $locale->t('instrument.modeThermal')],
];

$explanations = $locale->isRtl()
    ? [
        ['t' => 'مجموعه', 'd' => 'حرکت نسبی رینگ داخلی، ساچمه‌ها و قفسه با نسبت دورانی واقعی.'],
        ['t' => 'برش', 'd' => 'نیم‌مقطع فنی از شیار غلتش، رینگ‌ها و المان غلتشی.'],
        ['t' => 'ابعاد', 'd' => 'خطوط اندازه‌گذاری استاندارد روی هندسهٔ واقعی قطعه.'],
        ['t' => 'حرارتی', 'd' => 'تخمین دمای کارکرد نسبت به دور، همراه با محدودهٔ مجاز.'],
    ]
    : [
        ['t' => 'Assembly', 'd' => 'Inner ring, rolling elements and cage turning at their true relative rates.'],
        ['t' => 'Cutaway', 'd' => 'A half section through the raceways, rings and rolling element.'],
        ['t' => 'Dimensions', 'd' => 'ISO dimension leaders drawn against the actual geometry.'],
        ['t' => 'Thermal', 'd' => 'Estimated running temperature against speed, with the permitted range marked.'],
    ];
?>
<section class="section section--dark section--ruled" id="visualiser">
  <div class="container">
    <div class="split split--wide-end">

      <div class="stack stack-6">
        <div class="section-head" style="margin-block-end:0">
          <p class="eyebrow">
            <span class="eyebrow__index">03</span>
            <span><?= $this->e($locale->t('instrument.title')) ?></span>
          </p>
          <h2 class="section-title">
            <?= $this->e($locale->isRtl()
                ? 'هندسهٔ قطعه را پیش از سفارش ببینید'
                : 'See the geometry before you specify it') ?>
          </h2>
          <p class="section-lead">
            <?= $this->e($locale->isRtl()
                ? 'نمایش زیر از ابعاد واقعی همین شمارهٔ فنی در کاتالوگ ساخته می‌شود؛ نه یک تصویر تزئینی. با تغییر حالت، مقطع فنی، اندازه‌گذاری استاندارد و رفتار حرارتی نسبت به دور را می‌بینید.'
                : 'The view below is built from this reference’s own catalogue dimensions rather than a stock illustration. Switch modes to move between the section, the dimension overlay and the thermal behaviour.') ?>
          </p>
        </div>

        <ul class="stack stack-4">
          <?php foreach ($explanations as $index => $item): ?>
            <li style="display:flex;gap:var(--s-4)">
              <span class="about__point-index"><?= sprintf('%02d', $index + 1) ?></span>
              <span>
                <span class="about__point-title" style="color:var(--dark-ink)"><?= $this->e($item['t']) ?></span>
                <span class="about__point-text" style="color:var(--dark-ink-2);display:block"><?= $this->e($item['d']) ?></span>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>

        <p>
          <a class="link-lead" href="<?= $this->e($app->url($bearing->url())) ?>">
            <?= $this->e($locale->t('action.viewDetails')) ?>
            <?= Icons::render('arrow', 'btn__icon btn__icon--arrow', 16) ?>
          </a>
        </p>
      </div>

      <div class="instrument registered" data-instrument>
        <div class="instrument__head">
          <span class="instrument__title">
            <?= Icons::render('bearing', '', 14) ?>
            <span class="code"><?= $this->e($bearing->designation()) ?></span>
          </span>

          <div class="instrument__modes" role="group" aria-label="<?= $this->e($locale->t('instrument.title')) ?>">
            <?php foreach ($modes as $i => $mode): ?>
              <button class="instrument__mode"
                      type="button"
                      data-instrument-mode="<?= $this->e($mode['key']) ?>"
                      aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                <?= $this->e($mode['label']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="instrument__stage">
          <canvas class="instrument__canvas"
                  data-bearing-canvas
                  data-payload="instrument-bearing-payload"
                  data-mode="assembly"
                  width="900" height="900"
                  role="img"
                  aria-label="<?= $this->e($bearing->name($locale)) ?>"></canvas>
          <noscript>
            <p class="instrument__fallback"><?= $this->e($locale->t('instrument.noCanvas')) ?></p>
          </noscript>
        </div>

        <div class="instrument__readout">
          <div class="instrument__cell">
            <span class="instrument__cell-label"><?= $this->e($locale->t('instrument.boreLabel')) ?> · d</span>
            <span class="instrument__cell-value"><?= $this->e(Product::mm($bearing->d)) ?></span>
          </div>
          <div class="instrument__cell">
            <span class="instrument__cell-label"><?= $this->e($locale->t('instrument.odLabel')) ?> · D</span>
            <span class="instrument__cell-value"><?= $this->e(Product::mm($bearing->D)) ?></span>
          </div>
          <div class="instrument__cell">
            <span class="instrument__cell-label"><?= $this->e($locale->t('instrument.widthLabel')) ?> · B</span>
            <span class="instrument__cell-value"><?= $this->e(Product::mm($bearing->B)) ?></span>
          </div>
          <div class="instrument__cell">
            <span class="instrument__cell-label"><?= $this->e($locale->t('instrument.speedLabel')) ?><span data-instrument-speed-unit></span></span>
            <span class="instrument__cell-value" data-instrument-speed>—</span>
          </div>
          <div class="instrument__cell" data-instrument-temp-cell hidden>
            <span class="instrument__cell-label"><?= $this->e($locale->t('instrument.tempLabel')) ?></span>
            <span class="instrument__cell-value" data-instrument-temp>—</span>
          </div>
        </div>

        <div class="instrument__controls">
          <label class="visually-hidden" for="instrument-speed">
            <?= $this->e($locale->t('instrument.speedLabel')) ?>
          </label>
          <input class="instrument__slider"
                 id="instrument-speed"
                 type="range"
                 min="0" max="100" value="35" step="1"
                 data-instrument-slider
                 aria-describedby="instrument-hint">
        </div>

        <p class="instrument__note" id="instrument-hint">
          <span data-instrument-note><?= $this->e($locale->t('instrument.rotationHint')) ?></span>
        </p>

        <p class="instrument__note" data-instrument-thermal-note hidden>
          <strong><?= $this->e($locale->t('instrument.thermalDisclaimer')) ?></strong>
          <?= $this->e($locale->t('instrument.thermalNote')) ?>
        </p>

        <script type="application/json" id="instrument-bearing-payload"><?= $this->jsonBlock($payload) ?></script>
      </div>

    </div>
  </div>
</section>
