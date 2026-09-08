<?php

declare(strict_types=1);

/**
 * Contact.
 *
 * The commercial model ends here: a visitor who has identified a part talks to
 * a person. Every value on this block comes from the one authoritative company
 * record, so the page, the footer and the structured data cannot disagree.
 *
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 * @var App\Storage\ContentRepository $content
 */

use App\Support\Icons;

$section = $content->section('contact');
$tag = $locale->pick($section, 'tag');
$title = $locale->pick($section, 'title');
$description = $locale->pick($section, 'description');
$lang = $locale->language;

$cards = [];

if ($company->value('landlinePhoneTel') !== '') {
    $cards[] = [
        'icon' => 'phone',
        'label' => $locale->t('contact.phone'),
        'value' => $company->phoneDisplay($lang, true),
        'href' => $company->value('landlinePhoneTel'),
        'meta' => $company->workingHours($lang),
        'tel' => true,
    ];
}

if ($company->value('primaryPhoneTel') !== '') {
    $cards[] = [
        'icon' => 'whatsapp',
        'label' => $locale->t('contact.mobile'),
        'value' => $company->phoneDisplay($lang),
        'href' => $company->whatsappUrl($lang) ?: $company->value('primaryPhoneTel'),
        'meta' => $locale->t('action.whatsapp'),
        'tel' => true,
    ];
}

if ($company->address($lang) !== '') {
    $maps = $company->get()['maps'] ?? [];
    $cards[] = [
        'icon' => 'map-pin',
        'label' => $locale->t('contact.address'),
        'value' => $company->address($lang),
        'href' => is_array($maps) && is_string($maps['google'] ?? null) ? $maps['google'] : '',
        'meta' => is_array($maps) && ($maps['google'] ?? '') !== '' ? $locale->t('contact.mapLink') : '',
        'tel' => false,
    ];
}

if ($company->value('email') !== '') {
    $cards[] = [
        'icon' => 'mail',
        'label' => $locale->t('contact.email'),
        'value' => $company->value('email'),
        'href' => 'mailto:' . $company->value('email'),
        'meta' => '',
        'tel' => true,
    ];
}
?>
<section class="section section--sunken section--ruled" id="contact">
  <div class="container">

    <div class="section-head">
      <p class="eyebrow">
        <span class="eyebrow__index">09</span>
        <span><?= $this->e($tag !== '' ? $tag : $locale->t('nav.contact')) ?></span>
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
        <?php $el = $card['href'] !== '' ? 'a' : 'div'; ?>
        <<?= $el ?> class="contact-card reveal"
          data-delay="<?= (string) min(4, intdiv($index, 2)) ?>"
          <?php if ($card['href'] !== ''): ?>
            href="<?= $this->e($card['href']) ?>"
            <?= str_starts_with($card['href'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>
          <?php endif; ?>>
          <span class="contact-card__icon"><?= Icons::render($card['icon']) ?></span>
          <span>
            <span class="contact-card__label"><?= $this->e($card['label']) ?></span>
            <span class="contact-card__value <?= $card['tel'] ? 'contact-card__value--tel' : '' ?>">
              <?= $this->e($card['value']) ?>
            </span>
            <?php if ($card['meta'] !== ''): ?>
              <span class="contact-card__meta"><?= $this->e($card['meta']) ?></span>
            <?php endif; ?>
          </span>
        </<?= $el ?>>
      <?php endforeach; ?>
    </div>

  </div>
</section>
