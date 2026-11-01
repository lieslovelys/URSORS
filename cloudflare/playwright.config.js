import { defineConfig } from '@playwright/test';
export default defineConfig({
  testDir: './browser-tests',
  workers: 1,
  use: { baseURL: 'http://localhost:8788', browserName: 'chromium' },
  webServer: { command: 'npm run dev -- --port 8788', url: 'http://localhost:8788', reuseExistingServer: true }
});
