# Polad Charkhesh — industrial bearing platform

A bilingual (Persian / English) technical catalogue and engineering reference for
Polad Charkhesh, a Tehran industrial bearing supplier.

It is **not** a shop. There are no prices, no cart and no checkout anywhere in
the codebase. The commercial flow is: find the part → read its specification →
talk to someone.

---

## Stack

The deployment target is Iranian cPanel shared hosting — PHP, no Composer, no
database, no Node on the server. The architecture follows from that constraint.

| Layer | Choice |
|---|---|
| Server | PHP 8.1+, no framework, single front controller |
| Storage | JSON documents on disk behind a repository interface, with `flock` and atomic rename |
| Pages | Server-rendered PHP templates — real HTML per URL |
| Interactivity | TypeScript compiled to small ES modules, zero runtime dependencies |
| Build | esbuild, run here; compiled assets are committed |
| Web server | Apache with `.htaccess` |

Node is a build-time tool only. The host never runs it.

## Getting started

```bash
npm install          # esbuild + typescript, build-time only
npm run build        # compiles src/ into public/assets/, writes the manifest
npm run dev          # build, then serve on http://127.0.0.1:8080
```

Other commands:

```bash
npm run watch        # rebuild assets on change
npm run typecheck    # tsc --noEmit
npm run serve        # PHP dev server only, no rebuild
python3 scripts/audit_assets.py    # verify every referenced image decodes
```

Copy `.env.example` to `.env` before running in production. The two secrets it
names are mandatory there: the application refuses to boot without them rather
than inventing values that would rotate on every restart.

## Layout

```
app/                     PHP application (belongs outside the web root in production)
  bootstrap.php          PSR-4 autoloader, config load, error mode
  Core/                  Config, Request, Response, Router, Locale, View, App container
  Domain/                Product, Families, Industries — the engineering model
  Storage/               JsonStore and the repositories over it
  Support/               Seo, Assets, Icons, Schematics, BearingPayload
  Resources/lang/        Interface strings, written per language
  Views/                 Layout, partials, page and section templates
  Http/                  Controllers
data/
  store/                 The JSON store: products, company, content, seo, media
  migration-review.json  Records flagged during the catalogue migration
public/                  The web root (maps to public_html)
  index.php              Front controller
  .htaccess              Rewrites, security headers, caching
  assets/                Built CSS and JS, self-hosted fonts, images
src/
  styles/                Numbered CSS layers, concatenated in order by the build
  islands/               TypeScript enhancements
  main.ts                Entry point
scripts/                 Build, migration, asset audit, screenshot verification
docs/                    Architecture, data model, deployment
```

## How the site is put together

**Progressive by construction.** Every page is complete server-rendered HTML.
The hero search is a real GET form that works with JavaScript switched off; the
type-ahead is added on top. The bearing visualiser degrades to the
specification table beneath it. No island failing can take the page down —
each is initialised in isolation.

**One source of truth per fact.** The company's phone number exists in
`data/store/company.json` and nowhere else. The page, the footer, the WhatsApp
link and the JSON-LD all read it from the same record.

**Language is not translation.** Persian and English are stored as separate
fields and never derived from one another. Interface strings live in
`app/Resources/lang/`, written in each language rather than translated from the
other. `.ir` serves Persian and `.com` serves English by default; a manual
switch always wins and is remembered.

**Canonicals come from configuration, not from the request.** The production
hosts are configured in `data/store/seo.json`, so a development or staging
hostname can never be published as a canonical URL.

## Data honesty

Three rules the code enforces, all of them from the project brief:

1. **Engineering values are never regenerated.** The migration copies d, D, B,
   load ratings, speeds and calculation factors verbatim and verifies them
   field by field. Where the imported data looked wrong, it was flagged in
   `data/migration-review.json` for a human to approve — not corrected.

2. **Derived values are drawn, never labelled.** The visualiser needs a rolling
   element diameter and count, which the catalogue does not publish. It derives
   them from d and D so the drawing is proportionate, and never presents either
   as a specification.

3. **Nothing is claimed that the data cannot support.** The hero figures are
   counted from the catalogue at render time. No years in business, no customer
   counts, no certification claims.

## Status

Phase B complete: design system, home page, catalogue data, bilingual
architecture, SEO foundation. The catalogue listing, product pages, engineering
tools, admin and CMS are the phases that follow. `docs/ARCHITECTURE.md` records
what exists today; it is not a plan for what might.
