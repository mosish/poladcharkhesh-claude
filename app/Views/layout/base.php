<?php

declare(strict_types=1);

/**
 * @var App\Core\View $this
 * @var App\Core\App $app
 * @var App\Core\Locale $locale
 * @var App\Storage\CompanyRepository $company
 * @var App\Support\Assets $assets
 * @var string $content
 * @var array{title:string,description:string} $meta
 * @var string $canonical
 * @var array<string,string> $alternates
 * @var list<array<string,mixed>> $schemas
 * @var string $bodyClass
 */

$schemas ??= [];
$alternates ??= [];
$bodyClass ??= '';
$canonical ??= '';
$ogImage = $app->seo()->ogImage($locale->language);
$verification = $app->seoConfig()->value('googleSiteVerification');
?>
<!DOCTYPE html>
<html lang="<?= $this->e($locale->htmlLang()) ?>" dir="<?= $this->e($locale->dir()) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $this->e($meta['title']) ?></title>
<meta name="description" content="<?= $this->e($meta['description']) ?>">
<meta name="theme-color" content="#0d1233">
<meta name="format-detection" content="telephone=no">

<?php if ($canonical !== ''): ?>
<link rel="canonical" href="<?= $this->e($canonical) ?>">
<?php endif; ?>
<?php foreach ($alternates as $altLang => $altUrl): ?>
<link rel="alternate" hreflang="<?= $this->e($altLang === 'fa' ? 'fa-IR' : 'en') ?>" href="<?= $this->e($altUrl) ?>">
<?php endforeach; ?>
<?php if (isset($alternates['en'])): ?>
<link rel="alternate" hreflang="x-default" href="<?= $this->e($alternates['en']) ?>">
<?php endif; ?>

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $this->e($company->name($locale->language)) ?>">
<meta property="og:locale" content="<?= $this->e($locale->language === 'fa' ? 'fa_IR' : 'en_US') ?>">
<meta property="og:title" content="<?= $this->e($meta['title']) ?>">
<meta property="og:description" content="<?= $this->e($meta['description']) ?>">
<?php if ($canonical !== ''): ?>
<meta property="og:url" content="<?= $this->e($canonical) ?>">
<?php endif; ?>
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= $this->e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<meta name="twitter:title" content="<?= $this->e($meta['title']) ?>">
<meta name="twitter:description" content="<?= $this->e($meta['description']) ?>">
<?php if ($verification !== ''): ?>
<meta name="google-site-verification" content="<?= $this->e($verification) ?>">
<?php endif; ?>

<?php
// Only the two faces used above the fold are preloaded. Preloading the whole
// family would compete with the critical CSS for bandwidth.
$preload = $locale->isRtl()
    ? ['/assets/fonts/IRANSansWeb-Regular.woff2', '/assets/fonts/IRANSansWeb-Bold.woff2']
    : ['/assets/fonts/inter-var-latin.woff2'];
foreach ($preload as $font):
?>
<link rel="preload" href="<?= $this->e($font) ?>" as="font" type="font/woff2" crossorigin>
<?php endforeach; ?>

<link rel="stylesheet" href="<?= $this->e($assets->url('/assets/css/site.css')) ?>">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<?php if ($assets->exists('assets/apple-touch-icon.png')): ?>
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<?php endif; ?>

<script>document.documentElement.classList.remove('no-js');</script>

<?php foreach ($schemas as $schema): ?>
<script type="application/ld+json"><?= $this->jsonBlock($schema) ?></script>
<?php endforeach; ?>
</head>
<body class="<?= $this->e($bodyClass) ?>">

<a class="skip-link" href="#main"><?= $this->e($locale->t('meta.skipToContent')) ?></a>

<?= $this->partial('partials/utility-bar') ?>
<?= $this->partial('partials/header') ?>

<main id="main">
<?= $content ?>
</main>

<?= $this->partial('partials/footer') ?>
<?= $this->partial('partials/floating-actions') ?>

<script type="module" src="<?= $this->e($assets->url('/assets/js/site.js')) ?>"></script>
</body>
</html>
