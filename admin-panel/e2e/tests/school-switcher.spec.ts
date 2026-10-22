import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';

/**
 * The school switcher (Phase 1, tenancy): someone with memberships at both
 * demo schools picks the school in the header, and the lists follow it. A
 * one-school admin sees no switcher. Runs in sessions of its own, so the
 * switch affects no other test.
 */
test.use({ storageState: { cookies: [], origins: [] } });

test('a one-school admin sees no school switcher', async ({ page }) => {
  await signIn(page, accounts.admin);
  await expect(page.getByRole('combobox', { name: 'School', exact: true })).toHaveCount(0);
});

test('an admin at two schools switches school and the lists follow', async ({ page }) => {
  await signIn(page, accounts.adminBoth);
  const school = page.locator('.panel-topbar').getByRole('combobox', { name: 'School', exact: true });
  await expect(school).toBeVisible();
  await expect(school.locator('option')).toHaveText(['NavigateEd Demo School', 'Baobab Demo High']);

  // School A: its homerooms, none of School B's.
  await page.goto('/class');
  await expect(page.getByText('Grade 5', { exact: false }).first()).toBeVisible();
  await expect(page.getByText('Grade 8')).toHaveCount(0);

  // Switch to School B from the header.
  await Promise.all([page.waitForURL(/\/home/), school.selectOption({ label: 'Baobab Demo High' })]);
  await expect(page.locator('.panel-topbar').getByRole('combobox', { name: 'School', exact: true })).toHaveValue(/\d+/);
  await page.goto('/class');
  await expect(page.getByText('Grade 8', { exact: false }).first()).toBeVisible();
  await expect(page.getByText('Grade 5')).toHaveCount(0);

  // And back.
  await Promise.all([
    page.waitForURL(/\/home/),
    page.locator('.panel-topbar').getByRole('combobox', { name: 'School', exact: true }).selectOption({ label: 'NavigateEd Demo School' }),
  ]);
});
