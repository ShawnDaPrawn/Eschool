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

for (const width of [375, 1280]) {
  test(`unsaved native learner form cancels school and year changes at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.adminBoth);
    await page.goto('/students/create');
    const firstName = page.locator('#student-registration-form input[name="first_name"]');
    await firstName.fill('Unsaved private learner fixture');
    if (width < 992) await page.locator('[data-panel-nav-open]').click();
    const year = page.locator('.js-year-switcher:visible');
    const school = page.locator('.js-school-switcher:visible');
    const initialYear = await year.inputValue();
    const initialSchool = await school.inputValue();
    const otherYear = await year.locator('option').evaluateAll((options, selected) => options.map(option => (option as HTMLOptionElement).value).find(value => value !== selected), initialYear);
    const otherSchool = await school.locator('option').evaluateAll((options, selected) => options.map(option => (option as HTMLOptionElement).value).find(value => value !== selected), initialSchool);
    expect(otherYear).toBeTruthy();
    expect(otherSchool).toBeTruthy();
    const writes: string[] = [];
    page.on('request', request => { if (request.method() === 'POST') writes.push(new URL(request.url()).pathname); });
    for (const [control, value, initial] of [[year, otherYear!, initialYear], [school, otherSchool!, initialSchool]] as const) {
      await control.selectOption(value);
      const dialog = page.getByRole('alertdialog');
      await expect(dialog).toContainText('Your changes have not been saved');
      await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
      await expect(control).toHaveValue(initial);
      await expect(page).toHaveURL(/\/students\/create$/);
      await expect(firstName).toHaveValue('Unsaved private learner fixture');
      expect(writes).toEqual([]);
    }
    expect(await page.evaluate(() => JSON.stringify(sessionStorage))).not.toContain('Unsaved private learner fixture');
  });
}
