import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

/**
 * Global search (a Phase 0 UX acceptance path): typing in the search box opens
 * a learner, parent, teacher, class or settings page within ten seconds.
 */
test.use({ storageState: '.auth/admin-flows.json' });

test('"/" opens the search box and a learner opens from it', async ({ page }) => {
  await page.goto('/home');
  const started = Date.now();
  await page.keyboard.press('/');
  const box = page.getByRole('combobox', { name: 'Search the panel' });
  await expect(box).toBeFocused();

  await page.keyboard.type('Lerato');
  await expect(page.getByRole('option', { name: /Lerato Dlamini/ })).toBeVisible();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');

  await expect(page).toHaveURL(/\/student-report\/\d+/);
  await expect(page.getByText('Lerato').first()).toBeVisible();
  expect(Date.now() - started).toBeLessThan(10_000);
});

test('Ctrl+K and the results page open a settings page', async ({ page }) => {
  await page.goto('/announcement');
  await page.keyboard.press('Control+k');
  const box = page.getByRole('combobox', { name: 'Search the panel' });
  await expect(box).toBeFocused();

  await box.fill('school profile');
  await box.press('Enter');
  await expect(page).toHaveURL(/\/search\?q=school\+profile/);
  await expect(page.getByRole('heading', { name: 'Pages and settings' })).toBeVisible();
  await page.getByRole('link', { name: /School profile and settings/ }).click();
  await expect(page).toHaveURL(/\/settings$/);
});

test('a parent, a teacher and a homeroom are found too', async ({ page }) => {
  await page.goto('/home');
  const box = page.getByRole('combobox', { name: 'Search the panel' });
  for (const [term, result] of [
    ['Sipho', /Sipho Dlamini/],
    ['Thandi', /Thandi Mokoena/],
    ['Grade 5', /Grade 5 A/],
  ] as const) {
    await box.fill(term);
    await expect(page.getByRole('option', { name: result })).toBeVisible();
  }
});

test.describe('mobile global utilities', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  for (const width of [320, 375]) {
    test(`search, language and all modules remain reachable at ${width}px`, async ({ page, baseURL }, testInfo) => {
      test.setTimeout(150_000);
      await page.setViewportSize({ width, height: 812 });
      await signIn(page, accounts.admin);
      await page.goto('/search');
      const errors = collectErrors(page, baseURL!);
      const drawer = page.locator('#sidebar');
      const opener = page.locator('[data-panel-nav-open]');
      await opener.click();
      await expect(drawer).toHaveClass(/active/);
      await expect(drawer.locator('#sidebar-close')).toBeVisible();
      await expect(drawer.locator('#filter_session_year_global_mobile')).toBeVisible();
      await expect(drawer.locator('[data-nav-section]')).toHaveCount(12);

      const language = drawer.locator('[data-panel-language-switch]');
      await expect(language).toBeVisible();
      const originalLanguage = await language.inputValue();
      const options = await language.locator('option').evaluateAll(nodes => nodes.map(node => (node as HTMLOptionElement).value));
      expect(options.length).toBeGreaterThan(0);
      const selectedLanguage = options.find(value => value !== originalLanguage) ?? originalLanguage;
      expect(new URL(selectedLanguage).pathname).toMatch(/^\/set-language\/[^/]+$/);
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        language.selectOption(selectedLanguage),
      ]);
      await opener.click();
      await expect(language).toHaveValue(selectedLanguage);
      if (selectedLanguage !== originalLanguage) {
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          language.selectOption(originalLanguage),
        ]);
        await opener.click();
      }

      const settings = drawer.locator('[data-nav-section="settings"] a');
      await settings.scrollIntoViewIfNeeded();
      await expect(settings).toBeInViewport();
      const searchLink = drawer.locator('[data-panel-nav-search]');
      await searchLink.scrollIntoViewIfNeeded();
      await expect(searchLink).toBeInViewport();
      await expect(drawer.locator('#sidebar-close')).toBeInViewport();
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      expect(await seriousViolations(page)).toEqual([]);
      await page.screenshot({ path: testInfo.outputPath(`global-utilities-${width}.png`), animations: 'disabled' });
      await searchLink.click();
      await expect(page).toHaveURL(/\/search$/);
      await page.locator('#search-page-input').fill('Lerato');
      await Promise.all([
        page.waitForURL(/\/search\?q=Lerato/),
        page.locator('main form[role="search"] button[type="submit"]').click(),
      ]);
      await page.locator('[data-search-result]').filter({ hasText: 'Lerato Dlamini' }).click();
      await expect(page).toHaveURL(/\/student-report\/\d+/);
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      expect(errors).toEqual([]);
    });
  }
});
