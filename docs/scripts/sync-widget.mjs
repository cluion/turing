// Copies the freshly built browser widget into the docs public/ dir so the live
// demo loads a same-origin bundle (no dependency on the published CDN). Build
// the core first: cd js/packages/core && pnpm build.
import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const src = fileURLToPath(new URL('../../js/packages/core/dist/turing.global.js', import.meta.url));
const destDir = fileURLToPath(new URL('../public/', import.meta.url));
const dest = destDir + 'turing.global.js';

if (!existsSync(src)) {
  console.error('sync-widget: missing ' + src + '\n  Build it first: cd js/packages/core && pnpm build');
  process.exit(1);
}
mkdirSync(destDir, { recursive: true });
copyFileSync(src, dest);
console.log('sync-widget: copied turing.global.js into docs/public/');
