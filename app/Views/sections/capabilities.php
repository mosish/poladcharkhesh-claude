<?php

declare(strict_types=1);

/**
 * Capability strip.
 *
 * Every claim here is CMS-managed and describes what the company does, not
 * how long it has done it or how many customers it has. Section 28 of the
 * brief rules out invented metrics, and the imported content contained
 * several — years in business, a "100% authenticity" absolute, and a 24/7
 * support claim that contradicted the company's own published hours. None of
 * them were carried over.
 *
 * @var App\Core\View $this
 * @var App\Core\Locale $locale
 * @var App\Storage\ContentRepository $content
 */

use App\Support\Icons;

$items = $content->items('capabilities');
if ($items === []) {
    return;
}
?>
<section class="section section--tight section--paper">
  <div class="container">
    <div class="capabilities">
      <?php foreach ($items as $item): ?>
        <?php
        $title = $locale->pick($item, 'title');
        $text = $locale->pick($item, 'text');
        $icon = is_string($item['icon'] ?? null) ? $item['icon'] : 'check';
        if ($title === '') {
            continue;
        }
        ?>
        <div class="capability reveal">
          <span class="capability__icon"><?= Icons::render(Icons::exists($icon) ? $icon : 'check') ?></span>
          <span>
            <span class="capability__title"><?= $this->e($title) ?></span>
            <span class="capability__text"><?= $this->e($text) ?></span>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
