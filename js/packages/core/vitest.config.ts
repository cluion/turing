import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'happy-dom',
    include: ['test/**/*.test.ts'],
  },
  // The cross-language vector tests read php/tests/vectors/*.json, which lives
  // outside the js workspace; vite 6 restricts fs access by default, so relax it
  // for the test run (nothing here is served in production).
  server: { fs: { strict: false } },
});
