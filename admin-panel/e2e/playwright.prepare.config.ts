import { defineConfig } from '@playwright/test';
import base from './playwright.config';

export default defineConfig({
  ...base,
  testDir: './tool',
  testMatch: /prepare-panel\.spec\.ts/,
  projects: [{ name: 'prepare', use: base.use }],
  reporter: 'list',
});
