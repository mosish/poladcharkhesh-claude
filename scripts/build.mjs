/**
 * Asset build.
 *
 * The deployment target is cPanel shared hosting, which has PHP and nothing
 * else — no Node, no build step on the server. So assets are compiled here and
 * the compiled output is committed: deploying is then a file upload.
 *
 * Output is content-fingerprinted and recorded in a manifest that the PHP
 * `Assets` helper reads, so a cached stylesheet can never outlive a release.
 *
 *   node scripts/build.mjs           one-off build
 *   node scripts/build.mjs --watch   rebuild on change
 */

import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import { mkdir, readdir, readFile, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import * as esbuild from 'esbuild';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const stylesDir = path.join(root, 'src/styles');
const outCss = path.join(root, 'public/assets/css');
const outJs = path.join(root, 'public/assets/js');
const manifestPath = path.join(root, 'public/assets/manifest.json');

const watch = process.argv.includes('--watch');

const fingerprint = (contents) =>
  createHash('sha256').update(contents).digest('hex').slice(0, 10);

async function ensureDirs() {
  await mkdir(outCss, { recursive: true });
  await mkdir(outJs, { recursive: true });
}

/** Remove previous fingerprinted builds so the directory does not accumulate. */
async function clean(dir, extension) {
  if (!existsSync(dir)) return;
  for (const entry of await readdir(dir)) {
    if (entry.endsWith(extension)) {
      await rm(path.join(dir, entry), { force: true });
    }
  }
}

/**
 * Stylesheets are authored as numbered layers and concatenated in order, so
 * the cascade is a property of the filenames rather than of import statements
 * scattered through the files.
 */
async function buildCss() {
  const files = (await readdir(stylesDir)).filter((f) => f.endsWith('.css')).sort();

  const parts = [];
  for (const file of files) {
    parts.push(`/* ${file} */`);
    parts.push(await readFile(path.join(stylesDir, file), 'utf8'));
  }

  const result = await esbuild.transform(parts.join('\n'), {
    loader: 'css',
    minify: true,
    target: ['chrome100', 'firefox100', 'safari15', 'edge100'],
  });

  const hash = fingerprint(result.code);
  const name = `site.${hash}.css`;
  await clean(outCss, '.css');
  await writeFile(path.join(outCss, name), result.code, 'utf8');

  return { logical: '/assets/css/site.css', built: `/assets/css/${name}`, bytes: result.code.length, layers: files.length };
}

async function buildJs() {
  const result = await esbuild.build({
    entryPoints: [path.join(root, 'src/main.ts')],
    bundle: true,
    format: 'esm',
    target: ['es2022'],
    minify: true,
    sourcemap: false,
    write: false,
    legalComments: 'none',
  });

  const output = result.outputFiles[0];
  const hash = fingerprint(output.contents);
  const name = `site.${hash}.js`;
  await clean(outJs, '.js');
  await writeFile(path.join(outJs, name), output.contents);

  return { logical: '/assets/js/site.js', built: `/assets/js/${name}`, bytes: output.contents.length };
}

async function build() {
  await ensureDirs();
  const [css, js] = await Promise.all([buildCss(), buildJs()]);

  const manifest = {
    [css.logical]: css.built,
    [js.logical]: js.built,
  };
  await writeFile(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');

  const kb = (n) => `${(n / 1024).toFixed(1)} kB`;
  console.log(`  css  ${css.built.padEnd(34)} ${kb(css.bytes).padStart(9)}  (${css.layers} layers)`);
  console.log(`  js   ${js.built.padEnd(34)} ${kb(js.bytes).padStart(9)}`);
}

await build();

if (watch) {
  const { watch: watchFs } = await import('node:fs');
  let pending = null;
  const rebuild = () => {
    clearTimeout(pending);
    pending = setTimeout(() => {
      build().catch((error) => console.error(error.message));
    }, 120);
  };
  watchFs(path.join(root, 'src'), { recursive: true }, rebuild);
  console.log('  watching src/ …');
}
