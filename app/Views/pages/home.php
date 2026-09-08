<?php

declare(strict_types=1);

/**
 * Home page composition.
 *
 * The rhythm alternates deliberately: a dark instrument-led hero, a light
 * measured strip, a wide split for the company, a dense catalogue grid, a dark
 * technical band for the tools, a quiet list for industries, and a contact
 * block that ends on the actual phone number. No two adjacent sections share a
 * layout.
 *
 * @var App\Core\View $this
 */

$sections = [
    'sections/hero',
    'sections/capabilities',
    'sections/instrument',
    'sections/about',
    'sections/catalog',
    'sections/tools',
    'sections/industries',
    'sections/why-us',
    'sections/contact',
];

foreach ($sections as $section) {
    echo $this->partial($section, get_defined_vars());
}
