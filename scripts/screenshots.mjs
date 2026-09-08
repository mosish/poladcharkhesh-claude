/**
 * Visual verification.
 *
 * Renders the site at each breakpoint in both languages and captures the
 * visualiser in each of its four modes, so the review is done against actual
 * pixels rather than against an assumption that the CSS worked.
 */

import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8080';
const OUT = path.resolve('screenshots');

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 1000 },
  { name: 'laptop', width: 1280, height: 900 },
  { name: 'tablet', width: 834, height: 1112 },
  { name: 'mobile', width: 390, height: 844 },
  { name: 'mobile-small', width: 320, height: 720 },
];

const LANGS = ['fa', 'en'];

async function settle(page) {
  await page.waitForLoadState('networkidle');
  // Let the reveal observers fire and fonts settle.
  await page.evaluate(() => {
    document.querySelectorAll('.reveal').forEach((el) => el.classList.add('is-visible'));
  });
  await page.evaluate(() => document.fonts?.ready);
  await page.waitForTimeout(450);
}

async function run() {
  await mkdir(OUT, { recursive: true });
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || undefined,
  });
  const problems = [];

  for (const lang of LANGS) {
    for (const vp of VIEWPORTS) {
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
        deviceScaleFactor: 2,
        reducedMotion: 'no-preference',
      });
      const page = await context.newPage();

      page.on('console', (msg) => {
        if (msg.type() === 'error') problems.push(`[${lang}/${vp.name}] console: ${msg.text()}`);
      });
      page.on('pageerror', (err) => problems.push(`[${lang}/${vp.name}] pageerror: ${err.message}`));
      page.on('response', (res) => {
        if (res.status() >= 400) problems.push(`[${lang}/${vp.name}] ${res.status()} ${res.url()}`);
      });

      await page.goto(`${BASE}/?lang=${lang}`, { waitUntil: 'domcontentloaded' });
      await settle(page);

      // Horizontal overflow is a bug, not a style choice.
      const overflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
      );
      if (overflow > 1) {
        problems.push(`[${lang}/${vp.name}] horizontal overflow: ${overflow}px`);
      }

      await page.screenshot({
        path: path.join(OUT, `${lang}-${vp.name}-full.png`),
        fullPage: true,
      });
      await page.screenshot({ path: path.join(OUT, `${lang}-${vp.name}-fold.png`) });

      await context.close();
    }
  }

  // The visualiser, in each mode.
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
    deviceScaleFactor: 2,
  });
  const page = await context.newPage();
  await page.goto(`${BASE}/?lang=fa`, { waitUntil: 'domcontentloaded' });
  await settle(page);

  const panel = page.locator('[data-instrument]');
  await panel.scrollIntoViewIfNeeded();
  await page.waitForTimeout(300);

  for (const mode of ['assembly', 'cutaway', 'dimensions', 'thermal']) {
    await page.locator(`[data-instrument-mode="${mode}"]`).click();
    await page.waitForTimeout(500);
    await panel.screenshot({ path: path.join(OUT, `instrument-${mode}.png`) });
  }

  // Hero type-ahead.
  await page.goto(`${BASE}/?lang=fa`, { waitUntil: 'domcontentloaded' });
  await settle(page);
  await page.locator('[data-search-input]').fill('6204');
  await page.waitForTimeout(400);
  await page.locator('.hero-search').screenshot({ path: path.join(OUT, 'search-suggestions.png') });

  // Mobile navigation panel.
  const mobile = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
  });
  const mobilePage = await mobile.newPage();
  await mobilePage.goto(`${BASE}/?lang=fa`, { waitUntil: 'domcontentloaded' });
  await settle(mobilePage);
  await mobilePage.locator('[data-nav-toggle]').click();
  await mobilePage.waitForTimeout(400);
  await mobilePage.screenshot({ path: path.join(OUT, 'mobile-nav.png') });

  await context.close();
  await mobile.close();
  await browser.close();

  if (problems.length > 0) {
    console.log('\nPROBLEMS');
    for (const problem of [...new Set(problems)]) console.log('  ' + problem);
  } else {
    console.log('\nNo console errors, failed requests or horizontal overflow.');
  }
}

await run();
