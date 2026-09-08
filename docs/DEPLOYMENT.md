# Deployment — cPanel shared hosting

The target is Iranian cPanel shared hosting: PHP, Apache, no Composer, no
database, no shell build step. Deployment is a file upload.

---

## 1. Requirements

- PHP **8.1 or newer** (8.2+ preferred)
- Extensions: `mbstring`, `json`, `fileinfo` — all standard on cPanel
- Apache with `mod_rewrite`
- Write access to a directory **outside** `public_html`

Check the PHP version in cPanel under *MultiPHP Manager*, and the extensions
under *Select PHP Version → Extensions*.

## 2. Directory layout on the host

The application and the data store must not be reachable over HTTP.

```
/home/<account>/
├── public_html/            ← the repo's public/ directory
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── polad-app/              ← the repo's app/ directory
├── polad-data/             ← the repo's data/ directory
└── .env                    ← secrets, never committed
```

`public/index.php` resolves the application by `dirname(__DIR__)`, so if you
keep the repository structure intact — `public_html` as a sibling of `app` and
`data` — nothing needs changing. If your host forces a different shape, set
`APP_DATA_PATH` in `.env` and adjust the `require` path at the top of
`index.php`.

The shipped `.htaccess` also denies `/app`, `/data`, `/src`, `/scripts`,
`/tests` and `/docs` over HTTP, as a second line of defence if a host layout
forces them inside the web root.

## 3. Build before uploading

The host cannot run Node, so assets are compiled locally and committed.

```bash
npm install
npm run typecheck
npm run build
python3 scripts/audit_assets.py
```

Confirm `public/assets/manifest.json` points at the freshly hashed filenames
before you upload. Uploading `index.php` without the matching asset build will
serve a page referencing a stylesheet that is not there.

## 4. Secrets

Generate both, once, and keep them stable:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # APP_SESSION_SECRET
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # APP_COOKIE_SECRET
```

Put them in `.env` at the application root with `APP_ENV=production`.

The application **exits** in production if either is missing or shorter than 32
characters. This is deliberate: generating a fallback secret at runtime would
rotate it on every restart and silently sign every administrator out.

`.env` must never be committed. The `.htaccess` denies dotfiles, but the file
belongs outside `public_html` regardless.

## 5. Permissions

```bash
chmod 755 ~/polad-app ~/polad-data ~/polad-data/store
chmod 644 ~/polad-data/store/*.json
chmod 600 ~/.env
```

PHP must be able to write to `data/store/` — enquiries and admin edits go
there. If the host runs PHP as the account owner (usual on cPanel with
suPHP/FPM), 755/644 is enough.

## 6. Domains

Both domains point at the same `public_html`:

- `poladcharkhesh.ir` → Persian by default
- `poladcharkhesh.com` → English by default

Add the second as an *Addon Domain* or *Alias* in cPanel pointing at the same
document root. The application reads the Host header, resolves the default
language from the TLD, and emits the canonical for that language from
`data/store/seo.json` — never from the request host, so a staging hostname can
never be published as a canonical URL.

Issue certificates for both through *SSL/TLS Status* (AutoSSL). Force HTTPS by
adding this above the existing rewrite block in `public/.htaccess` once the
certificate is live:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

If the host terminates TLS at a proxy, also set `APP_TRUST_PROXY=true` so the
application reads `X-Forwarded-Proto`. Leave it false otherwise — trusting
those headers unconditionally lets any client dictate the scheme and its own
rate-limit identity.

## 7. Release procedure

1. Build and audit locally (step 3).
2. Upload `public/` → `public_html/`, `app/` → `polad-app/`.
3. Upload `data/store/*.json` **only on a first install**. On an update these
   files hold live content edited through the admin panel — overwriting them
   discards that work.
4. Load the site in both languages and check one product URL.

For a rollback, keep the previous `public/assets/` directory. Asset filenames
are content-hashed, so old and new can coexist while you swap `index.php`.

## 8. Backups

The whole application state is `data/store/`. Backing it up is copying that
directory:

```bash
tar czf polad-backup-$(date +%F).tar.gz -C ~ polad-data/store
```

There are no database credentials, no dump step and no secrets in the store —
`.env` is separate and is not part of a content backup.

## 9. Deliberately not included

**No Content-Security-Policy yet.** The brief asks not to break the application
with an untested policy. It should be added once the full page inventory
exists, tested in report-only mode first.

**No admin, sessions or rate limiting yet.** Those arrive with Phase G. Do not
create an `/admin` route or credentials by hand in the meantime.
