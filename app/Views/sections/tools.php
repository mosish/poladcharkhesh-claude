<?php

declare(strict_types=1);

/**
 * Engineering tools preview.
 *
 * The formulas shown are the ones the tools actually implement, written the way
 * they appear in ISO 281 and in manufacturer catalogues. Showing the working is
 * the point: an engineer decides whether to trust a calculator by looking at
 * what it computes, not by being told it is accurate.
 *
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\ContentRepository $content
 */

use App\Support\Icons;

$tools = $content->section('tools');
$tag = $locale->pick($tools, 'tag');
$title = $locale->pick($tools, 'title');
$description = $locale->pick($tools, 'description');
$rtl = $locale->isRtl();

$cards = [
    [
        'icon' => 'gauge',
        'name' => $rtl ? 'عمر اسمی بلبرینگ' : 'Basic rating life',
        'formula' => 'L₁₀ = (C / P)^p',
        'text' => $rtl
            ? 'عمر اسمی بر حسب میلیون دور و ساعت کارکرد، با توان ۳ برای بلبرینگ و ۱۰/۳ برای رولبرینگ.'
            : 'Rating life in millions of revolutions and in operating hours, with p = 3 for ball bearings and 10/3 for roller bearings.',
    ],
    [
        'icon' => 'layers',
        'name' => $rtl ? 'بار دینامیکی معادل' : 'Equivalent dynamic load',
        'formula' => 'P = X·Fr + Y·Fa',
        'text' => $rtl
            ? 'ضرایب X و Y از داده‌های همان شمارهٔ فنی خوانده می‌شود، نه از یک مقدار عمومی برای همهٔ بلبرینگ‌ها.'
            : 'X and Y are read from the selected reference’s own data rather than a single factor applied to every bearing.',
    ],
    [
        'icon' => 'shield-check',
        'name' => $rtl ? 'ضریب اطمینان استاتیکی' : 'Static safety factor',
        'formula' => 's₀ = C₀ / P₀',
        'text' => $rtl
            ? 'کنترل بار استاتیکی در حالت سکون یا دور بسیار پایین، با راهنمای مقادیر پیشنهادی.'
            : 'Checks static loading at rest or very low speed, with guidance on the values usually specified.',
    ],
    [
        'icon' => 'ruler',
        'name' => $rtl ? 'راهنمای لقی و سرعت' : 'Clearance & speed guidance',
        'formula' => 'CN · C3 · C4',
        'text' => $rtl
            ? 'انتخاب گروه لقی و بررسی حد سرعت گریس و روغن نسبت به شرایط کارکرد.'
            : 'Selecting a clearance group and checking grease and oil limiting speeds against the duty.',
    ],
];
?>
<section class="section section--dark section--ruled" id="tools">
  <div class="container">

    <div class="section-head">
      <p class="eyebrow">
        <span class="eyebrow__index">06</span>
        <span><?= $this->e($tag !== '' ? $tag : $locale->t('nav.tools')) ?></span>
      </p>
      <?php if ($title !== ''): ?>
        <h2 class="section-title"><?= $this->e($title) ?></h2>
      <?php endif; ?>
      <?php if ($description !== ''): ?>
        <p class="section-lead"><?= $this->e($description) ?></p>
      <?php endif; ?>
    </div>

    <div class="grid grid--2">
      <?php foreach ($cards as $index => $card): ?>
        <article class="tool-card reveal" data-delay="<?= (string) min(4, $index) ?>">
          <span style="color:var(--blue-light)"><?= Icons::render($card['icon'], '', 22) ?></span>
          <h3 class="tool-card__name"><?= $this->e($card['name']) ?></h3>
          <p class="tool-card__formula"><?= $this->e($card['formula']) ?></p>
          <p class="tool-card__text"><?= $this->e($card['text']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="notice notice--info" style="margin-block-start:var(--s-8);background:rgb(33 150 243 / .08);color:var(--dark-ink-2);border-color:transparent">
      <span class="notice__icon" style="color:var(--blue-light)"><?= Icons::render('alert', '', 18) ?></span>
      <span>
        <strong class="notice__title" style="color:var(--dark-ink)">
          <?= $this->e($rtl ? 'عمر اسمی پایه، نه عمر اصلاح‌شده' : 'Basic rating life, not modified rating life') ?>
        </strong>
        <?= $this->e($rtl
            ? 'این ابزارها عمر اسمی پایهٔ L۱۰ را مطابق ISO 281 محاسبه می‌کنند و ضریب اصلاح a_ISO مربوط به آلودگی، روان‌کاری و حد خستگی را اعمال نمی‌کنند. نتیجه، مبنای اولیهٔ انتخاب است و جایگزین بررسی مهندسی نیست.'
            : 'These tools compute the ISO 281 basic rating life L₁₀. They do not apply the a_ISO life modification factor for contamination, lubrication and fatigue limit, so the result is a starting point for selection rather than a substitute for an engineering review.') ?>
      </span>
    </div>

    <p style="margin-block-start:var(--s-8)">
      <a class="btn btn--secondary btn--lg" href="<?= $this->e($app->url('/tools')) ?>">
        <?= Icons::render('calculator', 'btn__icon') ?>
        <?= $this->e($locale->t('action.openTools')) ?>
      </a>
    </p>

  </div>
</section>
