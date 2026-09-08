/**
 * Reveal elements as they enter the viewport.
 *
 * If the browser lacks IntersectionObserver, or the visitor has asked for
 * reduced motion, everything is shown immediately. Content must never depend
 * on an animation having run.
 */
export function initReveal(): void {
  const items = Array.from(document.querySelectorAll<HTMLElement>('.reveal'));
  if (items.length === 0) return;

  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

  if (reduce || typeof IntersectionObserver !== 'function') {
    items.forEach((item) => item.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
  );

  items.forEach((item) => observer.observe(item));
}
