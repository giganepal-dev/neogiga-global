import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/E2E',
  timeout: 30000,
  retries: 1,
  use: {
    baseURL: 'https://neogiga.com',
    ignoreHTTPSErrors: true,
  },
  projects: [
    { name: 'desktop', use: { viewport: { width: 1280, height: 800 } } },
    { name: 'mobile', use: { viewport: { width: 390, height: 844 } } },
  ],
});
