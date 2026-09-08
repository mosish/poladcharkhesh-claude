<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 */

use App\Domain\Families;
use App\Support\Icons;

$content = $app->content();
$footer = $content->section('footer');
$lang = $locale->language;

$description = $locale->pick($footer, 'description');
$copyright = $locale->pick($footer, 'copyright');
$disclaimer = $locale->pick($footer, 'disclaimer');

$navLinks = ['catalog', 'tools', 'industries', 'about', 'whyUs', 'contact'];
$familyCounts = array_slice($app->products()->familyCounts(), 0, 6);
$year = (int) date('Y');
?>
<footer class="site-footer">
  <div class="container">
    <div class="site-footer__main">

      <div>
        <a class="brand" href="<?= $this->e($app->url('/')) ?>">
          <?= $this->partial('partials/logo', ['class' => 'brand__mark', 'onDark' => true]) ?>
          <span class="brand__text">
            <span class="brand__name" style="color:#fff"><?= $this->e($company->name($lang)) ?></span>
            <span class="brand__sub" style="color:var(--dark-ink-3)"><?= $this->e($company->legalName($lang)) ?></span>
          </span>
        </a>
        <?php if ($description !== ''): ?>
          <p style="margin-block-start:var(--s-5);max-width:42ch;line-height:var(--leading-snug)">
            <?= $this->e($description) ?>
          </p>
        <?php endif; ?>
      </div>

      <div>
        <h3><?= $this->e($locale->t('footer.navigation')) ?></h3>
        <ul class="site-footer__nav">
          <?php foreach ($navLinks as $key): ?>
            <li><a href="<?= $this->e($app->url('/#' . ($key === 'whyUs' ? 'why-us' : $key))) ?>"><?= $this->e($locale->t('nav.' . $key)) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3><?= $this->e($locale->t('footer.families')) ?></h3>
        <ul class="site-footer__nav">
          <?php foreach ($familyCounts as $entry): ?>
            <li>
              <a href="<?= $this->e($app->url('/catalog', ['family' => $entry['family']])) ?>">
                <?= $this->e(Families::label($entry['family'], $lang, true)) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3><?= $this->e($locale->t('footer.contact')) ?></h3>
        <ul class="site-footer__contact">
          <?php if ($company->value('landlinePhoneTel') !== ''): ?>
            <li>
              <?= Icons::render('phone') ?>
              <a href="<?= $this->e($company->value('landlinePhoneTel')) ?>" dir="ltr"><?= $this->e($company->phoneDisplay($lang, true)) ?></a>
            </li>
          <?php endif; ?>
          <?php if ($company->value('primaryPhoneTel') !== ''): ?>
            <li>
              <?= Icons::render('whatsapp') ?>
              <a href="<?= $this->e($company->value('primaryPhoneTel')) ?>" dir="ltr"><?= $this->e($company->phoneDisplay($lang)) ?></a>
            </li>
          <?php endif; ?>
          <?php if ($company->address($lang) !== ''): ?>
            <li>
              <?= Icons::render('map-pin') ?>
              <span><?= $this->e($company->address($lang)) ?></span>
            </li>
          <?php endif; ?>
          <?php if ($company->value('email') !== ''): ?>
            <li>
              <?= Icons::render('mail') ?>
              <a href="mailto:<?= $this->e($company->value('email')) ?>" dir="ltr"><?= $this->e($company->value('email')) ?></a>
            </li>
          <?php endif; ?>
        </ul>
      </div>

    </div>

    <?php if ($disclaimer !== ''): ?>
      <p class="site-footer__disclaimer"><?= $this->e($disclaimer) ?></p>
    <?php endif; ?>

    <div class="site-footer__bottom">
      <span>
        © <span dir="ltr"><?= $this->e($locale->digits((string) $year)) ?></span>
        <?= $this->e($copyright !== '' ? $copyright : $company->legalName($lang) . ' — ' . $locale->t('footer.rights')) ?>
      </span>
      <a class="site-footer__admin" href="<?= $this->e($app->url('/admin')) ?>" rel="nofollow">
        <?= $this->e($locale->t('nav.admin')) ?>
      </a>
    </div>
  </div>
</footer>
