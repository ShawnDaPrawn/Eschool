import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';

test.use({ storageState: { cookies: [], origins: [] } });
test.beforeEach(async ({ page }) => { await signIn(page, accounts.teacher); });

/**
 * A Teacher sees only what their permissions open, and is refused the admin-only
 * pages when they type the address (a redirect to Home with the "no permission"
 * message, or 403).
 */
const ADMIN_ONLY = ['/settings', '/roles', '/staff', '/fees-type', '/session-years', '/new-year', '/students/create', '/email-settings', '/language', '/profile-configuration'];

test('the navigation leaves out what a teacher cannot open', async ({ page }) => {
  await page.goto('/home');
  const menu = page.getByRole('navigation', { name: 'Main menu' });
  await expect(menu.getByRole('link', { name: 'Academics', exact: true })).toBeVisible();
  await expect(menu.getByRole('link', { name: 'Finance', exact: true })).toHaveCount(0);
  await expect(page.locator('[data-setup-checklist]')).toHaveCount(0);
  await menu.getByRole('link', { name: 'Settings', exact: true }).click();
  await expect(page).toHaveURL(/\/workspace\/settings$/);
  await expect(page.locator('main [data-workspace-item="notification-preferences"]')).toBeVisible();
  await expect(page.locator('main [data-workspace-item="profile-fields"]')).toHaveCount(0);
  await expect(page.locator('main [data-workspace-item="school-profile"]')).toHaveCount(0);
  await menu.getByRole('button', { name: 'People', exact: true }).click();
  await menu.getByRole('link', { name: 'Admin staff', exact: true }).click();
  await expect(page).toHaveURL(/\/workspace\/people\/admin-staff$/);
  await page.getByRole('link', { name: 'My profile', exact: true }).click();
  await expect(page).toHaveURL(/\/people-profiles$/);
  await expect(page.getByRole('heading', { level: 1, name: 'Person profile', exact: true })).toBeVisible();
});

for (const path of ADMIN_ONLY) {
  test(`a teacher is refused ${path}`, async ({ page }) => {
    const response = await page.goto(path);
    const refusedByStatus = response?.status() === 403;
    const sentHome = /\/home$/.test(new URL(page.url()).pathname);
    expect(refusedByStatus || sentHome, `${path} opened for a teacher (status ${response?.status()}, at ${page.url()})`).toBe(true);
  });
}
