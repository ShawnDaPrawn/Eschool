import { expect, type Page } from '@playwright/test';

/**
 * The demo accounts DemoSchoolSeeder creates (database/seeders/DemoSchoolSeeder.php).
 * They are public demo sign-ins, not secrets.
 */
export const PASSWORD = 'NavigateDemo2026';

export const accounts = {
  admin: 'admin.a@demo.navigateed.test',
  teacher: 'teacher.a@demo.navigateed.test',
  teacher2: 'teacher2.a@demo.navigateed.test',
  finance: 'finance.a@demo.navigateed.test',
  parent: 'parent.a@demo.navigateed.test',
  // School B (Baobab Demo High) and the people at both schools.
  adminB: 'admin.b@demo.navigateed.test',
  adminBoth: 'admin.both@demo.navigateed.test',
} as const;

/** Signs in through the panel's login form and waits for Home. */
export async function signIn(page: Page, email: string, password = PASSWORD): Promise<void> {
  await page.goto('/login');
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await Promise.all([
    page.waitForURL(/\/home/),
    page.locator('form button[type="submit"], form input[type="submit"]').first().click(),
  ]);
  await expect(page.locator('body[data-panel-layout]')).toBeVisible();
}

/** The CSRF token of the page currently open. */
export async function csrfToken(page: Page): Promise<string> {
  return (await page.locator('meta[name="csrf-token"]').getAttribute('content')) ?? '';
}
