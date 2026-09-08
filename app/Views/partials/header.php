<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 */

use App\Support\Icons;

/** @var list<array{href:string,key:string}> $links */
$links = [
    ['href' => '/#catalog', 'key' => 'catalog'],
    ['href' => '/#tools', 'key' => 'tools'],
    ['href' => '/#industries', 'key' => 'industries'],
    ['href' => '/#about', 'key' => 'about'],
    ['href' => '/#why-us', 'key' => 'whyUs'],
    ['href' => '/#contact', 'key' => 'contact'],
];

$whatsapp = $company->whatsappUrl($locale->language);
?>
<header class="site-header" data-sticky-header>
  <div class="container">
    <div class="site-header__inner">

      <a class="brand" href="<?= $this->e($app->url('/')) ?>">
        <?= $this->partial('partials/logo', ['class' => 'brand__mark']) ?>
        <span class="brand__text">
          <span class="brand__name"><?= $this->e($company->name($locale->language)) ?></span>
          <span class="brand__sub"><?= $this->e($company->legalName($locale->language)) ?></span>
        </span>
      </a>

      <nav class="nav" aria-label="<?= $this->e($locale->t('nav.menu')) ?>">
        <?php foreach ($links as $link): ?>
          <a class="nav__link" href="<?= $this->e($app->url($link['href'])) ?>">
            <?= $this->e($locale->t('nav.' . $link['key'])) ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="site-header__actions">
        <a class="lang-toggle" href="<?= $this->e($app->languageToggleUrl()) ?>"
           hreflang="<?= $this->e($locale->other() === 'fa' ? 'fa-IR' : 'en') ?>">
          <?= Icons::render('globe', '', 15) ?>
          <span><?= $this->e($locale->t('action.switchLanguage')) ?></span>
        </a>

        <?php if ($whatsapp !== ''): ?>
          <a class="btn btn--primary btn--sm" href="<?= $this->e($whatsapp) ?>" rel="noopener" target="_blank">
            <?= $this->e($locale->t('action.enquire')) ?>
          </a>
        <?php endif; ?>

        <button class="nav-toggle" type="button"
                aria-expanded="false"
                aria-controls="mobile-nav"
                aria-label="<?= $this->e($locale->t('nav.openMenu')) ?>"
                data-nav-toggle>
          <span class="nav-toggle__bars" aria-hidden="true">
            <span></span><span></span><span></span>
          </span>
        </button>
      </div>

    </div>
  </div>
</header>

<div class="mobile-nav" id="mobile-nav" data-mobile-nav hidden>
  <div class="mobile-nav__body">
    <div class="container">
      <nav aria-label="<?= $this->e($locale->t('nav.menu')) ?>">
        <?php foreach ($links as $index => $link): ?>
          <a class="mobile-nav__link" href="<?= $this->e($app->url($link['href'])) ?>">
            <span><?= $this->e($locale->t('nav.' . $link['key'])) ?></span>
            <span class="mobile-nav__index"><?= sprintf('%02d', $index + 1) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="mobile-nav__actions">
        <?php if ($whatsapp !== ''): ?>
          <a class="btn btn--whatsapp btn--lg btn--block" href="<?= $this->e($whatsapp) ?>" rel="noopener" target="_blank">
            <?= Icons::render('whatsapp', 'btn__icon') ?>
            <?= $this->e($locale->t('action.whatsapp')) ?>
          </a>
        <?php endif; ?>
        <?php if ($company->value('landlinePhoneTel') !== ''): ?>
          <a class="btn btn--secondary btn--lg btn--block" href="<?= $this->e($company->value('landlinePhoneTel')) ?>">
            <?= Icons::render('phone', 'btn__icon') ?>
            <span dir="ltr"><?= $this->e($company->phoneDisplay($locale->language, true)) ?></span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
