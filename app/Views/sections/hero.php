<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\ContentRepository $content
 * @var list<array{value:string,label:string}> $facts
 * @var list<array<string,string>> $searchIndex
 */

use App\Support\Icons;

$hero = $content->section('hero');
$badge = $locale->pick($hero, 'badge');
$titleAccent = $locale->pick($hero, 'titleHighlight');
$titleRest = $locale->pick($hero, 'titleSuffix');
$lead = $locale->pick($hero, 'description');
$placeholder = $locale->pick($hero, 'searchPlaceholder');
$examples = ['6204', '22212', 'NU 208', '30206'];
?>
<section class="hero on-dark">
  <div class="hero__grid u-grid-bg u-grid-bg--dark" aria-hidden="true"></div>
  <div class="hero__glow" aria-hidden="true"></div>

  <div class="container">
    <div class="hero__inner">

      <div class="hero__content">
        <?php if ($badge !== ''): ?>
          <p class="hero__badge">
            <span class="hero__badge-dot" aria-hidden="true"></span>
            <?= $this->e($badge) ?>
          </p>
        <?php endif; ?>

        <h1 class="hero__title">
          <?php if ($titleAccent !== ''): ?>
            <span class="hero__title-accent"><?= $this->e($titleAccent) ?></span>
          <?php endif; ?>
          <?= $this->e($titleRest) ?>
        </h1>

        <?php if ($lead !== ''): ?>
          <p class="hero__lead"><?= $this->e($lead) ?></p>
        <?php endif; ?>

        <!-- A real GET form: typing a designation and pressing enter works with
             or without JavaScript. The island only adds suggestions on top. -->
        <form class="hero-search"
              action="<?= $this->e($app->url('/catalog')) ?>"
              method="get"
              role="search"
              data-search
              autocomplete="off">
          <?php if ($locale->explicitChoice): ?>
            <input type="hidden" name="lang" value="<?= $this->e($locale->language) ?>">
          <?php endif; ?>

          <label class="visually-hidden" for="hero-search-input">
            <?= $this->e($locale->t('hero.searchLabel')) ?>
          </label>

          <div class="hero-search__field">
            <?= Icons::render('search', 'hero-search__icon') ?>
            <input class="hero-search__input"
                   id="hero-search-input"
                   type="search"
                   name="q"
                   placeholder="<?= $this->e($placeholder) ?>"
                   aria-describedby="hero-search-hint"
                   aria-expanded="false"
                   aria-controls="hero-search-results"
                   role="combobox"
                   data-search-input>
            <button class="btn btn--primary" type="submit">
              <span class="visually-hidden"><?= $this->e($locale->t('action.search')) ?></span>
              <?= Icons::render('arrow', 'btn__icon btn__icon--arrow') ?>
            </button>
          </div>

          <div class="hero-search__results"
               id="hero-search-results"
               role="listbox"
               aria-label="<?= $this->e($locale->t('action.search')) ?>"
               data-search-results
               data-empty-message="<?= $this->e($locale->t('hero.noResults')) ?>"
               hidden></div>

          <p class="hero-search__examples" id="hero-search-hint">
            <span><?= $this->e($locale->t('hero.examplesLabel')) ?></span>
            <?php foreach ($examples as $example): ?>
              <a class="hero-search__example"
                 href="<?= $this->e($app->url('/catalog', ['q' => $example])) ?>"><?= $this->e($example) ?></a>
            <?php endforeach; ?>
          </p>
        </form>

        <div class="hero__actions">
          <a class="btn btn--primary btn--lg" href="<?= $this->e($app->url('/catalog')) ?>">
            <?= $this->e($locale->t('action.browseCatalog')) ?>
            <?= Icons::render('arrow', 'btn__icon btn__icon--arrow') ?>
          </a>
          <a class="btn btn--secondary btn--lg" href="<?= $this->e($app->url('/#contact')) ?>">
            <?= Icons::render('headset', 'btn__icon') ?>
            <?= $this->e($locale->t('action.contactEngineering')) ?>
          </a>
        </div>

        <ul class="hero__facts">
          <?php foreach ($facts as $fact): ?>
            <li class="hero__fact">
              <span class="hero__fact-value"><?= $this->e($fact['value']) ?></span>
              <span class="hero__fact-label"><?= $this->e($fact['label']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if (($heroBearing ?? null) !== null): ?>
        <div class="hero__visual">
          <?= $this->partial('sections/hero-visual', ['bearing' => $heroBearing]) ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <script type="application/json" data-search-index><?= $this->jsonBlock($searchIndex) ?></script>
</section>
