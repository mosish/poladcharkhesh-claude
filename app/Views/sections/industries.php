<?php

declare(strict_types=1);

/**
 * Industries.
 *
 * Each sector links to the bearing families typically specified in its
 * rotating equipment. The links go to family filters, not to hand-picked
 * product lists: the imported data tags every product with the same two
 * industry ids, so a per-industry product list would be presenting a seeding
 * artefact as curation.
 *
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\ContentRepository $content
 * @var list<array{id:string,label:string,note:string,families:list<string>}> $industries
 */

use App\Domain\Families;

$section = $content->section('industries');
$tag = $locale->pick($section, 'tag');
$title = $locale->pick($section, 'title');
$description = $locale->pick($section, 'description');
$lang = $locale->language;
?>
<section class="section section--paper section--ruled" id="industries">
  <div class="container">

    <div class="section-head">
      <p class="eyebrow">
        <span class="eyebrow__index">07</span>
        <span><?= $this->e($tag !== '' ? $tag : $locale->t('nav.industries')) ?></span>
      </p>
      <?php if ($title !== ''): ?>
        <h2 class="section-title"><?= $this->e($title) ?></h2>
      <?php endif; ?>
      <?php if ($description !== ''): ?>
        <p class="section-lead"><?= $this->e($description) ?></p>
      <?php endif; ?>
    </div>

    <div class="grid grid--2" style="gap:0 clamp(var(--s-8),5vw,var(--s-16))">
      <?php foreach ($industries as $index => $industry): ?>
        <article class="industry reveal" data-delay="<?= (string) min(4, intdiv($index, 2)) ?>">
          <h3 class="industry__name">
            <span class="industry__index"><?= sprintf('%02d', $index + 1) ?></span>
            <span><?= $this->e($industry['label']) ?></span>
          </h3>
          <p class="industry__note"><?= $this->e($industry['note']) ?></p>
          <div class="industry__families">
            <?php foreach ($industry['families'] as $family): ?>
              <a class="industry__family"
                 href="<?= $this->e($app->url('/catalog', ['family' => $family])) ?>">
                <?= $this->e(Families::label($family, $lang, true)) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
