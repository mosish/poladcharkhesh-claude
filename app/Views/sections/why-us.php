<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\Locale $locale
 * @var App\Storage\ContentRepository $content
 */

$section = $content->section('whyUs');
$tag = $locale->pick($section, 'tag');
$title = $locale->pick($section, 'title');
$description = $locale->pick($section, 'description');
$items = $content->items('whyUs');

if ($items === []) {
    return;
}
?>
<section class="section section--surface section--ruled" id="why-us">
  <div class="container">

    <div class="section-head">
      <p class="eyebrow">
        <span class="eyebrow__index">08</span>
        <span><?= $this->e($tag !== '' ? $tag : $locale->t('nav.whyUs')) ?></span>
      </p>
      <?php if ($title !== ''): ?>
        <h2 class="section-title"><?= $this->e($title) ?></h2>
      <?php endif; ?>
      <?php if ($description !== ''): ?>
        <p class="section-lead"><?= $this->e($description) ?></p>
      <?php endif; ?>
    </div>

    <div class="grid grid--3">
      <?php foreach ($items as $index => $item): ?>
        <?php
        $itemTitle = $locale->pick($item, 'title');
        $itemText = $locale->pick($item, 'text');
        if ($itemTitle === '') {
            continue;
        }
        ?>
        <article class="value reveal" data-delay="<?= (string) min(4, intdiv($index, 3)) ?>">
          <span class="value__num"><?= sprintf('%02d', $index + 1) ?></span>
          <span>
            <h3 class="value__title"><?= $this->e($itemTitle) ?></h3>
            <p class="value__text"><?= $this->e($itemText) ?></p>
          </span>
        </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
