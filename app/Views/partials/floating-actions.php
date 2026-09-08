<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 */

use App\Support\Icons;

$whatsapp = $company->whatsappUrl($locale->language);
$phone = $company->value('landlinePhoneTel') ?: $company->value('primaryPhoneTel');
?>
<div class="floating-actions">
  <?php if ($whatsapp !== ''): ?>
    <a class="fab fab--whatsapp" href="<?= $this->e($whatsapp) ?>" rel="noopener" target="_blank"
       aria-label="<?= $this->e($locale->t('action.whatsapp')) ?>">
      <?= Icons::render('whatsapp') ?>
    </a>
  <?php endif; ?>

  <?php if ($phone !== ''): ?>
    <a class="fab" href="<?= $this->e($phone) ?>" aria-label="<?= $this->e($locale->t('action.call')) ?>">
      <?= Icons::render('phone') ?>
    </a>
  <?php endif; ?>

  <button class="fab fab--top" type="button" data-back-to-top
          aria-label="<?= $this->e($locale->t('action.backToTop')) ?>">
    <?= Icons::render('chevron-up') ?>
  </button>
</div>
