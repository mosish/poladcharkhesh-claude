/**
 * Entry point.
 *
 * Everything here is progressive: the page is complete server-rendered HTML,
 * and each island only adds behaviour to markup that already works. A failure
 * in one island must not take down the others, so each is initialised in
 * isolation.
 */

import { initBackToTop, initMobileNav, initSectionSpy, initStickyHeader } from './islands/nav';
import { initReveal } from './islands/reveal';
import { initHeroSearch } from './islands/search';
import { initBearingVisuals, initInstrument } from './islands/instrument';

function safely(name: string, fn: () => void): void {
  try {
    fn();
  } catch (error) {
    // A broken enhancement should never break the page.
    console.warn(`[polad] ${name} failed to initialise`, error);
  }
}

function boot(): void {
  safely('sticky header', initStickyHeader);
  safely('mobile navigation', initMobileNav);
  safely('back to top', initBackToTop);
  safely('section spy', initSectionSpy);
  safely('reveal', initReveal);
  safely('hero search', initHeroSearch);
  safely('bearing visuals', () => {
    initBearingVisuals();
    initInstrument();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}
