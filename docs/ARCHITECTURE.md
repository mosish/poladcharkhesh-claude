# Architecture

This describes what the codebase does today. Where a phase has not been built,
it says so rather than describing an intention.

---

## 1. Request path

```
Browser
  │
  ├── /assets/…                     served by Apache directly, never touches PHP
  │
  └── everything else
        │  .htaccess rewrite
        ▼
      public/index.php              front controller
        │
        ├── Request::fromGlobals()  method, path, host, query, body, cookies
        ├── App::boot()             container; resolves Locale from the request
        ├── Router::dispatch()      pattern match → controller
        │     └── HomeController
        │           ├── ProductRepository   catalogue reads
        │           ├── ContentRepository   editable copy
        │           ├── CompanyRepository   the authoritative company record
        │           └── Seo                 meta, canonical, JSON-LD
        │
        └── Response::send()        status, security headers, body
```

Two failure modes are handled explicitly at the top level. A `StoreException`
renders a 503 that says the data could not be read; any other throwable renders
a 500 in production and re-throws in development. Neither ever renders an empty
catalogue, which would look like a successful load of nothing.

## 2. Storage

There is no database on the target host, so each collection is a JSON document
under `data/store/`.

`JsonStore` provides the guarantees that make this safe for the workload:

- **Atomic writes.** Content goes to a temporary file in the same directory and
  is then `rename()`d into place. A reader never sees a half-written document,
  and a crash mid-write cannot truncate the live file.
- **Locked read-modify-write.** `mutate()` holds an exclusive lock on a sidecar
  file for the whole cycle, so two concurrent writers cannot overwrite each
  other.
- **Loud corruption.** A document that does not parse raises `StoreException`
  rather than returning an empty array.
- **Per-request memoisation.** Each collection is decoded at most once.

The surface is deliberately repository-shaped. If a host turns out to provide
`pdo_sqlite`, a SQLite driver can replace `JsonStore` without touching anything
above it.

Sizing: 68 products is roughly 180 kB of JSON, decoded once per request.
Filtering 68 records in PHP is cheaper than the indirection a query layer would
add. This stops being true in the low thousands, which is where the SQLite
driver would earn its place.

## 3. The engineering model

`Domain\Families` is the axis that governs behaviour. Family determines which
load directions a bearing accepts, which Lundberg–Palmgren exponent applies
(3 for ball, 10/3 for roller), and which cross-section to draw. It is kept
separate from the coarse `category` used for catalogue grouping.

Fourteen families are defined: the thirteen the brief lists, plus
`toroidal-roller`, because the catalogue contains an SKF CARB and that is
genuinely its own family rather than a cylindrical roller bearing.

Each family carries the flags the calculator will need in Phase F:

| Flag | Meaning |
|---|---|
| `radial` | accepts radial load |
| `axial` | `both`, `one`, `limit` or `none` |
| `exponent` | life exponent, or null where life is not defined |
| `calculable` | whether a rating life applies at all |
| `axialNote` / `radialNote` | the caveat to show instead of a number |

A housing, an oil seal and a lubricant are `calculable: false`. Asking the
calculator for the rating life of a grease should produce a refusal, not a
figure.

Family is derived from the ISO/DIN designation at migration time and stored,
with a confidence and the rule that produced it. See `docs/DATA_MODEL.md`.

## 4. Localisation

`Core\Locale` resolves language in this order: an explicit `?lang=`, then the
visitor's stored preference, then the domain (`.ir` → Persian, `.com` →
English), then Persian. A manual switch always beats the domain, which is what
the brief asks for.

Direction, the HTML `lang`, digit rendering and the `…Fa` / `…En` field picker
all hang off the resolved locale. The CSS uses logical properties throughout
(`margin-inline`, `inset-inline-start`, `border-inline-end`), so RTL is
structural rather than a set of overrides.

Two typographic decisions worth recording:

- **IRANSans is scoped by `unicode-range`** to the Arabic and Persian blocks.
  A Latin bearing designation inside a Persian sentence therefore renders in
  Inter rather than IRANSans's weaker Latin glyphs, with no markup.
- **Engineering values keep Latin digits in both languages**, isolated LTR, in
  a monospace face with tabular figures. A designation or a millimetre value is
  something an engineer copies into a calculation. Prose numbers in Persian use
  Persian digits.

## 5. Client islands

`src/main.ts` initialises each island in a try/catch. There is no framework and
no runtime dependency; the whole bundle is about 22 kB minified.

| Island | Job |
|---|---|
| `nav` | sticky header state, mobile panel with focus trap and scroll lock, back-to-top, section spy |
| `reveal` | IntersectionObserver reveal; shows everything immediately under reduced motion |
| `search` | hero type-ahead over an inline index, ranked the same way the PHP does |
| `bearing` | the canvas renderer |
| `instrument` | wires the visualiser's modes, slider and readout |

**The renderer** draws four views from the catalogue dimensions: the running
assembly, a half section, the section with ISO dimension leaders, and a
speed-versus-temperature estimate. Three details are deliberate:

- Rotation uses the real kinematics of a bearing with a stationary outer ring —
  the cage turns at roughly half shaft speed reduced by the element-to-pitch
  ratio, and each element spins much faster than the shaft. Arbitrary speeds
  would look plausible and be wrong.
- Animation stops when the canvas leaves the viewport and under
  `prefers-reduced-motion`.
- Canvas text is forced to `direction: 'ltr'` and picks its face by script,
  because a monospace stack has no Persian glyphs and would render Persian
  unshaped.

## 6. SEO

`Support\Seo` builds canonicals from the configured production hosts, never
from the request host. Persian resolves to the `.ir` host and English to
`.com`, with reciprocal `hreflang` alternates and `x-default` on the English
URL.

Structured data is generated from the same repositories the page reads, so the
Organization telephone in JSON-LD cannot disagree with the footer. Product
schema carries genuine engineering properties as `PropertyValue` entries —
bore, outside diameter, width, load ratings, limiting speeds — with units.

There is deliberately no `offers`, no price, no review and no
`aggregateRating`. The site has no prices to publish and no reviews to report.

## 7. Security posture

What exists now:

- Production secrets are required, and the application exits if they are
  missing or too short.
- Proxy headers are ignored unless `APP_TRUST_PROXY` is set, so a client cannot
  spoof its own address or scheme.
- The Host header is validated to a hostname shape and never reaches a
  canonical.
- Output escaping is centralised in `View::e()` and every template uses it.
- JSON embedded in the page has its angle brackets and ampersands escaped, so
  data cannot break out of a `<script>` block.
- Baseline security headers ship from both PHP and `.htaccess`.
- The data store and application code sit outside the web root, with
  `.htaccess` rules as a second line if a host layout forces them inside it.

What does not exist yet, because the features do not: authentication, sessions,
rate limiting, upload handling, audit logging. Those arrive with the admin
panel in Phase G. A Content-Security-Policy is deliberately deferred until
there is a full page inventory to test it against.

## 8. Build

`scripts/build.mjs` concatenates the numbered CSS layers in filename order —
the cascade is a property of the filenames rather than of imports scattered
through the files — minifies with esbuild, bundles `src/main.ts` to an ES
module, fingerprints both by content hash, and writes
`public/assets/manifest.json`. `Support\Assets` reads that manifest, so a
cached stylesheet cannot outlive a release. Without a manifest it falls back to
an mtime query string, which is what happens during development.

Built output is committed. Deploying to shared hosting is a file upload, and
the host cannot run a build.

## 9. Verification

`scripts/screenshots.mjs` renders both languages at five breakpoints, captures
the visualiser in each mode, and fails loudly on console errors, requests
returning 400 or worse, and any horizontal overflow of the document.

`scripts/audit_assets.py` decodes every asset the catalogue references and
records the usable set in `data/store/media.json`. It exists because none of
the reference repository's product photography decodes — see
`docs/DATA_MODEL.md`.
