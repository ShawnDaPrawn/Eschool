import { expect, test as base, type BrowserContext } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { allowedAxe, allowedConsole, allowedScroll, allowlist, collectErrors, horizontalOverflow, panelPages, seriousViolations } from '../support/checks';
import { accounts, signIn } from '../support/accounts';

// Laravel intentionally serializes requests within a session. Each page-sweep
// worker therefore signs in separately, just as separate administrators do;
// concurrent page checks must not compete over a copied session cookie.
const test = base.extend<{}, { isolatedAdminState: Awaited<ReturnType<BrowserContext['storageState']>> }>({
  isolatedAdminState: [async ({ browser }, use, workerInfo) => {
    const context = await browser.newContext({ baseURL: workerInfo.project.use.baseURL });
    try {
      await signIn(await context.newPage(), accounts.admin);
      await use(await context.storageState());
    } finally {
      await context.close();
    }
  }, { scope: 'worker' }],
  storageState: async ({ isolatedAdminState }, use) => use(isolatedAdminState),
});

/**
 * Every panel page, for the Super Admin, at the project's width (1280,
 * 1024 and 375 px): it loads, logs no JavaScript or console errors and requests nothing
 * that fails, has no horizontal scroll, and has no serious or critical axe
 * violation. Known legacy problems are in allowlist.json, each with a reason.
 */
const pages = panelPages();

test('the panel pages were found', () => {
  expect(pages.length, 'global-setup.ts found no panel pages; is the panel running and seeded?').toBeGreaterThan(50);
  expect(pages).toContain('/home');
  expect(pages).toContain('/new-year');
  expect(pages).toContain('/search');
});

test('server errors cannot silently remove routes from page discovery', () => {
  const file = path.join(__dirname, '..', '.cache', 'panel-discovery-failures.json');
  expect(existsSync(file), 'Run the current global setup before the page sweep.').toBe(true);
  expect(JSON.parse(readFileSync(file, 'utf8')), 'GET routes returned server errors during discovery').toEqual([]);
});

test('every allowlist entry says why', () => {
  for (const entry of [...allowlist.axe, ...allowlist.console, ...allowlist.scroll]) {
    expect(entry.reason?.trim().length ?? 0, JSON.stringify(entry)).toBeGreaterThan(20);
    expect(entry.pages.length).toBeGreaterThan(0);
  }
});

for (const uri of pages) {
  test(uri, async ({ page, baseURL }) => {
    const errors = collectErrors(page, baseURL!);

    const response = await page.goto(uri);
    expect(response?.status(), `${uri} did not load`).toBe(200);
    await expect(page.locator('body[data-panel-layout]')).toBeVisible();
    // Lists and widgets load after the page; give them a moment to finish.
    await page.waitForLoadState('networkidle', { timeout: 8_000 }).catch(() => undefined);

    const unexpected = errors.filter((text) => !allowedConsole(uri, text));
    expect(unexpected, 'errors in the browser console').toEqual([]);

    if (!allowedScroll(uri)) {
      expect(await horizontalOverflow(page), 'the page scrolls sideways').toBeLessThanOrEqual(1);
    }

    const violations = (await seriousViolations(page)).filter((v) => !allowedAxe(uri, v.id));
    expect(
      violations.map((v) => `${v.id} (${v.impact}): ${v.help} at ${v.targets.join(' | ')}`),
      'serious or critical accessibility problems',
    ).toEqual([]);
  });
}
