<?php

declare(strict_types=1);

/**
 * The shared page for every terminal state: not found, data unavailable,
 * unexpected error. Never a blank screen — each state says what happened, what
 * was asked for, and offers a way onward.
 *
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var string $heading
 * @var string $message
 * @var string $statusCode
 * @var string $requestedPath
 */

use App\Support\Icons;

$requestedPath ??= '';
?>
<section class="section section--paper">
  <div class="container">
    <div class="message-page">
      <p class="eyebrow">
        <span class="eyebrow__index"><?= $this->e($statusCode) ?></span>
        <span><?= $this->e($app->company()->name($locale->language)) ?></span>
      </p>

      <h1 class="section-title"><?= $this->e($heading) ?></h1>
      <p class="section-lead"><?= $this->e($message) ?></p>

      <?php if ($requestedPath !== '' && $requestedPath !== '/'): ?>
        <p class="message-page__path">
          <span class="code code--chip"><?= $this->e($requestedPath) ?></span>
        </p>
      <?php endif; ?>

      <div class="cluster" style="margin-block-start:var(--s-8)">
        <a class="btn btn--primary btn--lg" href="<?= $this->e($app->url('/catalog')) ?>">
          <?= $this->e($locale->t('action.browseCatalog')) ?>
          <?= Icons::render('arrow', 'btn__icon btn__icon--arrow') ?>
        </a>
        <a class="btn btn--secondary btn--lg" href="<?= $this->e($app->url('/#contact')) ?>">
          <?= Icons::render('headset', 'btn__icon') ?>
          <?= $this->e($locale->t('action.contactEngineering')) ?>
        </a>
      </div>
    </div>
  </div>
</section>
