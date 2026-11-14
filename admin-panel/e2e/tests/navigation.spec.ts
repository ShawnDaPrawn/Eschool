import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
import { prepareUiReworkSchool } from '../support/ui-rework';

/** The approved school-management navigation and real destination journeys. */
test.use({ storageState: '.auth/admin-flows.json' });
test.beforeAll(prepareUiReworkSchool);

function menu(page: Page) {
  return page.getByRole('navigation', { name: 'Main menu' });
}

const sections = [
  'home', 'people', 'attendance', 'leave', 'academics', 'extracurriculars',
  'awards', 'discipline', 'community', 'vendors', 'finance', 'settings',
];

test('the twelve school modules appear in the approved order', async ({ page }) => {
  await page.goto('/home');
  await expect(menu(page).getByRole('link', { name: 'Home', exact: true })).toHaveAttribute('aria-current', 'page');
  expect(await menu(page).locator('[data-nav-section]').evaluateAll(nodes => nodes.map(node => node.getAttribute('data-nav-section')))).toEqual(sections);
  await expect(menu(page).locator('[data-panel-nav-section]')).toHaveCount(2);
  await menu(page).getByRole('button', { name: 'People', exact: true }).click();
  await expect(menu(page).locator('#nav-section-people a')).toHaveText(['Learners', 'Parents', 'Teachers', 'Facilitators', 'Admin staff', 'Alumni']);
  await menu(page).getByRole('button', { name: 'Community', exact: true }).click();
  await expect(menu(page).locator('#nav-section-community a')).toHaveText(['Gallery', 'Newsletter', 'Events', 'Voting']);
});

test('an administrator reaches learner creation through the learner workspace', async ({ page }) => {
  const started = Date.now();
  await page.goto('/home');
  await menu(page).getByRole('button', { name: 'People', exact: true }).click();
  await menu(page).getByRole('link', { name: 'Learners', exact: true }).click();
  await expect(page).toHaveURL(/\/students(?:\?.*)?$/);
  await page.locator('main a[href$="/students/create"]').first().click();
  await expect(page).toHaveURL(/\/students\/create$/);
  expect(Date.now() - started).toBeLessThan(30_000);
});

test('school terms are reached from global settings configuration', async ({ page }) => {
  await page.goto('/home');
  await menu(page).getByRole('link', { name: 'Settings', exact: true }).click();
  const configuration = page.locator('main a[href*="view=configuration"]').first();
  if (await configuration.count()) await configuration.click();
  await page.locator('main a[href$="/term"]').first().click();
  await expect(page).toHaveURL(/\/term$/);
  await expect(menu(page).getByRole('link', { name: 'Settings', exact: true })).toHaveAttribute('aria-current', 'page');
});

test('school notices belong to the Community newsletter workspace', async ({ page }) => {
  await page.goto('/home');
  await menu(page).getByRole('button', { name: 'Community', exact: true }).click();
  await menu(page).getByRole('link', { name: 'Newsletter', exact: true }).click();
  if (!(await page.locator('main a[href$="/announcement"]').count())) {
    await page.locator('main a[href*="/workspace/community/newsletter"]').first().click();
  }
  await page.locator('main a[href$="/announcement"]').first().click();
  await expect(page).toHaveURL(/\/announcement$/);
});

test('the People dropdown and learner destination work from the keyboard', async ({ page }) => {
  await page.goto('/home');
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
  const people = menu(page).getByRole('button', { name: 'People', exact: true });
  await people.focus();
  await page.keyboard.press('Enter');
  await expect(people).toHaveAttribute('aria-expanded', 'true');
  await page.keyboard.press('Tab');
  await expect(menu(page).getByRole('link', { name: 'Learners', exact: true })).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/\/students(?:\?.*)?$/);
});

// Who can see what (engineering/specs/platform/access-review.md).
for (const width of [320, 375, 768, 1024, 1280]) {
  test(`access review of a role at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = collectErrors(page, baseURL!);
    await page.goto('/access-review');
    const form = page.getByRole('form', { name: 'Review a role', exact: true });
    await form.getByRole('combobox', { name: 'Role' }).selectOption({ label: 'Teacher' });
    await form.getByRole('button', { name: 'Review role', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Teacher', exact: true })).toBeVisible();
    const accessTable = page.getByRole('table', { name: 'Access in each module' });
    await expect(accessTable).toBeVisible();
    expect(await accessTable.locator('tbody tr').count()).toBeGreaterThan(5);
    const tableRegion = page.getByRole('region', { name: 'Access in each module' });
    await tableRegion.focus();
    await expect(tableRegion).toBeFocused();
    const regionBox = await tableRegion.boundingBox();
    expect(regionBox!.x).toBeGreaterThanOrEqual(0);
    expect(regionBox!.x + regionBox!.width).toBeLessThanOrEqual(width + 1);
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
