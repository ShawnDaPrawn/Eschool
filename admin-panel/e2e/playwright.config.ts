import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright checks for the admin panel (wiki: Testing-and-CI, "Playwright
 * (admin panel)").
 *
 * The panel must already be running at E2E_BASE_URL (default
 * http://127.0.0.1:8000) on a database seeded with InstallationSeeder and
 * DemoSchoolSeeder. global-setup.ts signs in the demo accounts and lists the
 * panel pages. Projects:
 *
 * - pages-1280, pages-1024: every panel page loads for the Super Admin without
 *   console errors or horizontal scroll and passes axe (serious and critical);
 * - flows: navigation, search and the year switcher;
 * - teacher: a Teacher is refused admin-only pages;
 * - removed: the URLs Phase 0 removed answer 404;
 * - wizard: the new-year wizard, last, because it changes the current year
 *   (and puts it back).
 */
const baseURL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000';
const desktop = devices['Desktop Chrome'];

export default defineConfig({
  testDir: './tests',
  globalSetup: './global-setup.ts',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: process.env.E2E_WORKERS ? Number(process.env.E2E_WORKERS) : 2,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    locale: 'en-ZA',
  },
  projects: [
    {
      name: 'pages-1280',
      testMatch: /pages\.spec\.ts/,
      use: { ...desktop, viewport: { width: 1280, height: 800 }, storageState: '.auth/admin-pages.json' },
    },
    {
      name: 'pages-1024',
      testMatch: /pages\.spec\.ts/,
      use: { ...desktop, viewport: { width: 1024, height: 768 }, storageState: '.auth/admin-pages.json' },
    },
    {
      name: 'flows',
      // These workflows share one seeded school and mutate its catalogues,
      // module settings and preview fingerprints. Keep their writes ordered.
      workers: 1,
      testMatch: /(bank-statements|fee-items|fee-credits|fee-payments|family-billing|fee-plans|navigation|search|year-switcher|school-switcher|modules|notification-rules|profile-fields|school-information|school-terms|subject-standards|curriculum-plans|teaching-groups|academic-timetable|academic-registers|academic-reports|academic-transitions|academic-content|academic-sasams|academic-languages|communication-messages|relief-cover|activity-groups|activity-material-files|mastery|support-plans|public-reporting|progress-notes|wellbeing|leadership-dashboard|school-transfers|risk-interventions|school-discipline|recognition|admissions|consultations|private-gallery|weekly-digests|school-cards|card-offline|card-encoding|foundation-classroom(?:-backend)?|family-forms|family-visits|group-conversations|pathways|peer-tutoring|school-assistance|ai-flag-review|ai-controls|saas-panel)\.spec\.ts/,
      use: { ...desktop, viewport: { width: 1280, height: 800 } },
    },
    {
      name: 'teacher',
      testMatch: /teacher\.spec\.ts/,
      use: { ...desktop, viewport: { width: 1280, height: 800 }, storageState: '.auth/teacher.json' },
    },
    {
      name: 'removed',
      testMatch: /removed-urls\.spec\.ts/,
      use: { ...desktop },
    },
    {
      name: 'wizard',
      testMatch: /new-year-wizard\.spec\.ts/,
      dependencies: ['pages-1280', 'pages-1024', 'flows', 'teacher'],
      use: { ...desktop, viewport: { width: 1280, height: 800 }, storageState: '.auth/admin-flows.json' },
    },
  ],
});
