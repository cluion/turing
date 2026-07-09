import { defineConfig } from 'tsup';

export default defineConfig([
  {
    entry: { index: 'src/index.ts' },
    format: ['esm'],
    dts: true,
    clean: true,
    minify: true,
    target: 'es2022',
  },
  {
    // tsup appends `.global` to IIFE outputs, so entry `turing-element` -> `turing-element.global.js`.
    entry: { 'turing-element': 'src/index.ts' },
    format: ['iife'],
    globalName: 'TuringElement',
    dts: false,
    clean: false,
    minify: true,
    target: 'es2022',
  },
]);
