import { expect, test } from '@playwright/test';
import { accounts, csrfToken, signIn } from '../support/accounts';

/**
 * The year switcher (a Phase 0 UX acceptance path): with last year selected,
 * every page shows the read-only banner and lists last year's data, write
 * buttons are disabled and the server refuses writes. Runs as the second demo
 * teacher, in a session of its own, so the switch affects no other test.
 */
test.use({ storageState: { cookies: [], origins: [] } });

test('last year is read-only until you switch back', async ({ page }) => {
  const year = new Date().getFullYear();
  await signIn(page, accounts.teacher2);

  // This year: no banner, the homeroom's learners, writes allowed.
  await page.goto('/students');
  await expect(page.locator('.year-banner')).toHaveCount(0);
  await expect(page.getByText('Kabelo Dlamini', { exact: true })).toBeVisible();

  // Switch to last year from the header.
  await Promise.all([page.waitForEvent('load'), page.getByRole('combobox', { name: 'School year' }).selectOption({ label: String(year - 1) })]);
  const banner = page.locator('.year-banner');
  await expect(banner).toBeVisible();
  await expect(banner).toContainText(`${year - 1} is a past year`);
  await expect(page.getByRole('combobox', { name: 'School year' })).toHaveValue(/\d+/);

  // The list follows the year: last year this homeroom had other learners.
  await expect(page.getByText('Kabelo Dlamini', { exact: true })).toHaveCount(0);

  // The banner is on every page, and write buttons are disabled.
  await page.goto('/announcement');
  await expect(banner).toBeVisible();
  await expect(page.locator('form.create-form input[type="submit"]')).toBeDisabled();

  // The server refuses a write with a clear message.
  const response = await page.request.post('/announcement', {
    headers: { 'X-CSRF-TOKEN': await csrfToken(page), Accept: 'application/json' },
    form: { title: 'Should not be saved', description: 'Read-only year' },
  });
  expect(response.status()).toBe(423);
  expect((await response.json()).message).toContain(String(year - 1));

  // Back to this year from the banner.
  await page.getByRole('button', { name: `Back to ${year}` }).click();
  await expect(page.locator('.year-banner')).toHaveCount(0);
  await expect(page.locator('form.create-form input[type="submit"]')).toBeEnabled();
});
