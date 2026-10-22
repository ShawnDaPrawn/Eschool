import { expect, test } from '@playwright/test';

/**
 * A Teacher sees only what their permissions open, and is refused the admin-only
 * pages when they type the address (a redirect to Home with the "no permission"
 * message, or 403).
 */
const ADMIN_ONLY = ['/settings', '/roles', '/staff', '/fees-type', '/session-years', '/new-year', '/students/create', '/email-settings', '/language'];

test('the navigation leaves out what a teacher cannot open', async ({ page }) => {
  await page.goto('/home');
  const menu = page.getByRole('navigation', { name: 'Main menu' });
  await expect(menu.getByRole('button', { name: 'Academics', exact: true })).toBeVisible();
  await expect(menu.getByRole('button', { name: 'Settings', exact: true })).toHaveCount(0);
  await expect(menu.getByRole('button', { name: 'Money', exact: true })).toHaveCount(0);
  await expect(page.locator('[data-setup-checklist]')).toHaveCount(0);
});

for (const path of ADMIN_ONLY) {
  test(`a teacher is refused ${path}`, async ({ page }) => {
    const response = await page.goto(path);
    const refusedByStatus = response?.status() === 403;
    const sentHome = /\/home$/.test(new URL(page.url()).pathname);
    expect(refusedByStatus || sentHome, `${path} opened for a teacher (status ${response?.status()}, at ${page.url()})`).toBe(true);
  });
}
