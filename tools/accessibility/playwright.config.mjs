import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 45_000,
  expect: {
    timeout: 10_000,
  },
  fullyParallel: false,
  workers: 1,
  reporter: [['line']],
  use: {
    baseURL: process.env.UC_A11Y_BASE_URL || 'http://uc-web',
    browserName: 'chromium',
    headless: true,
    trace: 'retain-on-failure',
  },
});
