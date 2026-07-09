import { defineConfig } from 'tsup';

export default defineConfig([
  {
    entry: { index: 'src/index.ts', worker: 'src/worker.ts' },
    format: ['esm'],
    dts: true,
    clean: true,
    minify: true,
    target: 'es2022',
  },
  {
    // tsup appends `.global` to IIFE outputs, so entry `turing` -> `turing.global.js`.
    entry: { turing: 'src/index.ts' },
    format: ['iife'],
    globalName: 'Turing',
    dts: false,
    clean: false,
    minify: true,
    target: 'es2022',
    // The auto-mount side effect in index.ts runs when the classic script loads.
  },
]);
