import { defineConfig } from '@playwright/test';

// Boots the testbench dev server (which serves the demo form and the built
// widget from the core package dist). Build the widget first:
//   cd js/packages/core && pnpm build
export default defineConfig({
  testDir: './tests',
  timeout: 30_000,
  use: { baseURL: 'http://127.0.0.1:8000' },
  projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
  webServer: {
    command: 'vendor/bin/testbench serve --port=8000',
    cwd: '..',
    url: 'http://127.0.0.1:8000/captcha-demo',
    timeout: 120_000,
    reuseExistingServer: !process.env.CI,
  },
});
