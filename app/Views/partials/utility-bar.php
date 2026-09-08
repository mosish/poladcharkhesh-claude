<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 */

use App\Support\Icons;

$hours = $company->workingHours($locale->language);
$landline = $company->phoneDisplay($locale->language, true);
$landlineTel = $company->value('landlinePhoneTel');
$mobile = $company->phoneDisplay($locale->language);
$mobileTel = $company->value('primaryPhoneTel');
?>
<div class="site-utility">
  <div class="container">
    <div class="site-utility__inner">
      <div class="site-utility__group">
        <?php if ($hours !== ''): ?>
          <span class="site-utility__item">
            <?= Icons::render('clock') ?>
            <span><?= $this->e($hours) ?></span>
          </span>
        <?php endif; ?>
      </div>

      <div class="site-utility__group site-utility__group--secondary">
        <?php if ($landline !== '' && $landlineTel !== ''): ?>
          <a class="site-utility__item" href="<?= $this->e($landlineTel) ?>">
            <?= Icons::render('phone') ?>
            <span dir="ltr"><?= $this->e($landline) ?></span>
          </a>
        <?php endif; ?>
        <?php if ($mobile !== '' && $mobileTel !== ''): ?>
          <a class="site-utility__item site-utility__item--keep" href="<?= $this->e($mobileTel) ?>">
            <?= Icons::render('whatsapp') ?>
            <span dir="ltr"><?= $this->e($mobile) ?></span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
