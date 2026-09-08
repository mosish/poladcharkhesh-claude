/**
 * Hero type-ahead.
 *
 * The form underneath works on its own: submitting goes to the catalogue with
 * a `q` parameter, and the server does the same ranking. This adds suggestions
 * on top of that, never in place of it.
 *
 * Ranking mirrors the PHP implementation deliberately — an exact or prefix
 * match on the designation outranks everything, and digits are compared
 * separately so "6204" finds "6204-2RSH / 2RS1". Persian and Arabic digits are
 * folded to Latin, because an engineer typing on a Persian keyboard is typing
 * the same bearing number.
 */

interface IndexEntry {
  code: string;
  name: string;
  slug: string;
  family: string;
  dims: string;
  url: string;
}

const DIGIT_MAP: Record<string, string> = {
  '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
  '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
  '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
  '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
};

function normalise(value: string): string {
  return value
    .toLowerCase()
    .replace(/[۰-۹٠-٩]/g, (ch) => DIGIT_MAP[ch] ?? ch)
    .replace(/[\s\-_./,]+/g, '');
}

function score(entry: IndexEntry, needle: string, needleDigits: string): number {
  const code = normalise(entry.code);
  const codeDigits = code.replace(/\D+/g, '');
  let total = 0;

  if (code === needle) total += 1000;
  else if (code.startsWith(needle)) total += 600;
  else if (code.includes(needle)) total += 400;

  if (needleDigits.length >= 3) {
    if (codeDigits === needleDigits) total += 500;
    else if (codeDigits.startsWith(needleDigits)) total += 300;
    else if (codeDigits.includes(needleDigits)) total += 120;
  }

  if (normalise(entry.name).includes(needle)) total += 90;
  if (normalise(entry.family).includes(needle)) total += 50;
  if (normalise(entry.dims).includes(needle)) total += 40;

  return total;
}

export function initHeroSearch(): void {
  const form = document.querySelector<HTMLFormElement>('[data-search]');
  const input = document.querySelector<HTMLInputElement>('[data-search-input]');
  const results = document.querySelector<HTMLElement>('[data-search-results]');
  const source = document.querySelector<HTMLScriptElement>('[data-search-index]');

  if (!form || !input || !results || !source) return;

  let index: IndexEntry[] = [];
  try {
    const parsed: unknown = JSON.parse(source.textContent ?? '[]');
    if (Array.isArray(parsed)) index = parsed as IndexEntry[];
  } catch {
    return; // No suggestions; the plain form still works.
  }
  if (index.length === 0) return;

  let active = -1;

  const emptyMessage = results.dataset.emptyMessage ?? '';

  const close = (): void => {
    results.hidden = true;
    results.replaceChildren();
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    active = -1;
  };

  const render = (matches: IndexEntry[]): void => {
    active = -1;
    results.replaceChildren();

    if (matches.length === 0) {
      if (emptyMessage !== '') {
        const empty = document.createElement('p');
        empty.className = 'hero-search__empty';
        empty.textContent = emptyMessage;
        results.append(empty);
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
      } else {
        close();
      }
      return;
    }

    matches.forEach((entry, position) => {
      const link = document.createElement('a');
      link.className = 'hero-search__result';
      link.href = entry.url;
      link.id = `hero-search-option-${position}`;
      link.setAttribute('role', 'option');
      link.setAttribute('aria-selected', 'false');

      const code = document.createElement('span');
      code.className = 'hero-search__result-code';
      code.textContent = entry.code;

      const meta = document.createElement('span');
      meta.className = 'hero-search__result-meta';
      meta.textContent = entry.dims !== '' ? entry.dims : entry.family;

      link.append(code, meta);
      results.append(link);
    });

    results.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  };

  const highlight = (next: number): void => {
    const options = Array.from(results.querySelectorAll<HTMLAnchorElement>('.hero-search__result'));
    if (options.length === 0) return;

    active = (next + options.length) % options.length;
    options.forEach((option, position) => {
      const isActive = position === active;
      option.classList.toggle('is-active', isActive);
      option.setAttribute('aria-selected', isActive ? 'true' : 'false');
      if (isActive) {
        input.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
      }
    });
  };

  const search = (): void => {
    const raw = input.value.trim();
    if (raw.length < 2) {
      close();
      return;
    }

    const needle = normalise(raw);
    const needleDigits = needle.replace(/\D+/g, '');

    const matches = index
      .map((entry) => ({ entry, value: score(entry, needle, needleDigits) }))
      .filter((row) => row.value > 0)
      .sort((a, b) => b.value - a.value)
      .slice(0, 7)
      .map((row) => row.entry);

    render(matches);
  };

  let timer = 0;
  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(search, 90);
  });

  input.addEventListener('keydown', (event) => {
    if (results.hidden) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        highlight(active + 1);
        break;
      case 'ArrowUp':
        event.preventDefault();
        highlight(active - 1);
        break;
      case 'Enter': {
        const options = results.querySelectorAll<HTMLAnchorElement>('.hero-search__result');
        if (active >= 0 && options[active]) {
          event.preventDefault();
          options[active].click();
        }
        break;
      }
      case 'Escape':
        event.preventDefault();
        close();
        break;
    }
  });

  document.addEventListener('click', (event) => {
    if (!form.contains(event.target as Node)) close();
  });

  input.addEventListener('focus', () => {
    if (input.value.trim().length >= 2) search();
  });
}
