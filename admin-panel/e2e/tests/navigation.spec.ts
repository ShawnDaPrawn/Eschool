import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

/**
 * Navigation by job (a Phase 0 UX acceptance path): starting on Home, an admin
 * reaches the pages for adding a learner, setting up terms and sending a notice
 * within 30 seconds each, by mouse and by keyboard.
 */
test.use({ storageState: '.auth/admin-flows.json' });

function menu(page: Page) {
  return page.getByRole('navigation', { name: 'Main menu' });
}

async function open(page: Page, section: string, item: string, url: RegExp): Promise<void> {
  const started = Date.now();
  await menu(page).getByRole('button', { name: section, exact: true }).click();
  await menu(page).getByRole('link', { name: item, exact: true }).click();
  await expect(page).toHaveURL(url);
  expect(Date.now() - started, `finding "${item}" took too long`).toBeLessThan(30_000);
}

test('the eight sections are there for the Super Admin', async ({ page }) => {
  await page.goto('/home');
  await expect(menu(page).getByRole('link', { name: 'Home', exact: true })).toHaveAttribute('aria-current', 'page');
  for (const section of ['People', 'Academics', 'Communication', 'Money', 'Activities', 'Wellbeing', 'Settings']) {
    await expect(menu(page).getByRole('button', { name: section, exact: true })).toBeVisible();
  }
});

test('a new admin finds "add a learner"', async ({ page }) => {
  await page.goto('/home');
  await open(page, 'People', 'Add a learner', /\/students\/create$/);
  await expect(menu(page).getByRole('link', { name: 'Add a learner', exact: true })).toHaveAttribute('aria-current', 'page');
});

test('a new admin finds "set up terms"', async ({ page }) => {
  await page.goto('/home');
  await open(page, 'Academics', 'Terms', /\/term$/);
  await expect(menu(page).getByRole('button', { name: 'Academics', exact: true })).toHaveAttribute('aria-expanded', 'true');
});

test('a new admin finds "send a notice"', async ({ page }) => {
  await page.goto('/home');
  await open(page, 'Communication', 'Notices', /\/announcement$/);
});

test('the navigation works from the keyboard', async ({ page }) => {
  await page.goto('/home');
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();

  const academics = menu(page).getByRole('button', { name: 'Academics', exact: true });
  await academics.focus();
  await page.keyboard.press('Enter');
  await expect(academics).toHaveAttribute('aria-expanded', 'true');
  await page.keyboard.press('Tab');
  await expect(menu(page).getByRole('link', { name: 'School years', exact: true })).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(menu(page).getByRole('link', { name: 'Terms', exact: true })).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/\/term$/);
});

// Who can see what (engineering/specs/platform/access-review.md).
for (const width of [1024, 1280]) {
  test(`access review of a role at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = collectErrors(page, baseURL!);
    await page.goto('/access-review');
    const form = page.getByRole('form', { name: 'Review a role', exact: true });
    await form.getByRole('combobox', { name: 'Role' }).selectOption({ label: 'Teacher' });
    await form.getByRole('button', { name: 'Review role', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Teacher', exact: true })).toBeVisible();
    await expect(page.getByRole('table', { name: 'Access in each module' })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}

test.describe('as a teacher', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('teachers cannot open the access review', async ({ page }) => {
    await signIn(page, accounts.teacher);
    expect((await page.goto('/access-review'))?.status()).toBe(403);
  });
});
