/**
 * Site chrome behaviour: sticky header state, the mobile navigation panel, and
 * the back-to-top control.
 *
 * The mobile panel is a modal surface, so it takes the full set of obligations:
 * focus moves into it, focus is trapped while it is open, Escape closes it,
 * the page behind it does not scroll, and focus returns to the button that
 * opened it.
 */

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function initStickyHeader(): void {
  const header = document.querySelector<HTMLElement>('[data-sticky-header]');
  if (!header) return;

  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px;';
  document.body.prepend(sentinel);

  if (typeof IntersectionObserver !== 'function') return;

  const observer = new IntersectionObserver(
    ([entry]) => header.classList.toggle('is-stuck', !entry.isIntersecting),
    { threshold: 0 },
  );
  observer.observe(sentinel);
}

export function initMobileNav(): void {
  const toggle = document.querySelector<HTMLButtonElement>('[data-nav-toggle]');
  const panel = document.querySelector<HTMLElement>('[data-mobile-nav]');
  if (!toggle || !panel) return;

  let lastFocused: HTMLElement | null = null;

  const focusables = (): HTMLElement[] =>
    Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
      (el) => el.offsetParent !== null,
    );

  const open = (): void => {
    lastFocused = document.activeElement as HTMLElement | null;
    panel.hidden = false;
    // Let the element paint before transitioning, so the animation runs.
    requestAnimationFrame(() => panel.classList.add('is-open'));
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('is-locked');
    focusables()[0]?.focus();
  };

  const close = (returnFocus = true): void => {
    panel.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('is-locked');
    window.setTimeout(() => {
      panel.hidden = true;
    }, 220);
    if (returnFocus) {
      (lastFocused ?? toggle).focus();
    }
  };

  toggle.addEventListener('click', () => {
    toggle.getAttribute('aria-expanded') === 'true' ? close() : open();
  });

  panel.addEventListener('click', (event) => {
    if ((event.target as HTMLElement).closest('a')) {
      close(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (panel.hidden) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }

    if (event.key !== 'Tab') return;

    const items = focusables();
    if (items.length === 0) return;

    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && active === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  });

  // A resize past the desktop breakpoint should not leave the page locked.
  window.addEventListener('resize', () => {
    if (!panel.hidden && window.innerWidth >= 1040) {
      close(false);
    }
  });
}

export function initBackToTop(): void {
  const button = document.querySelector<HTMLButtonElement>('[data-back-to-top]');
  if (!button) return;

  button.addEventListener('click', () => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    document.querySelector<HTMLElement>('.skip-link')?.focus();
  });

  let ticking = false;
  const update = (): void => {
    button.classList.toggle('is-visible', window.scrollY > 700);
    ticking = false;
  };

  window.addEventListener(
    'scroll',
    () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(update);
      }
    },
    { passive: true },
  );
  update();
}

/**
 * Mark the navigation item for the section currently in view.
 * Purely presentational, so it degrades to nothing if unsupported.
 */
export function initSectionSpy(): void {
  if (typeof IntersectionObserver !== 'function') return;

  const links = Array.from(document.querySelectorAll<HTMLAnchorElement>('.nav__link[href*="#"]'));
  if (links.length === 0) return;

  const byId = new Map<string, HTMLAnchorElement>();
  for (const link of links) {
    const id = link.hash.slice(1);
    if (id) byId.set(id, link);
  }

  const sections = Array.from(byId.keys())
    .map((id) => document.getElementById(id))
    .filter((el): el is HTMLElement => el !== null);

  if (sections.length === 0) return;

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        for (const link of links) link.removeAttribute('aria-current');
        byId.get(entry.target.id)?.setAttribute('aria-current', 'true');
      }
    },
    { rootMargin: '-45% 0px -50% 0px' },
  );

  sections.forEach((section) => observer.observe(section));
}
