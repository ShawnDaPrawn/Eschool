import { expect, test } from '@playwright/test';
import { accounts, csrfToken, signIn } from '../support/accounts';

test.use({ storageState: { cookies: [], origins: [] } });

/**
 * The new-year wizard (a Phase 0 UX acceptance path): setting up next year,
 * picking what to copy across and switching to it is done in less than five
 * minutes, and nothing in the current year changes before the switch. Runs last
 * (the wizard project depends on the others) and makes this year current again
 * at the end, so the suite can run again on the same database.
 */
test('the wizard creates next year and makes it current', async ({ page }) => {
  test.setTimeout(360_000);
  const started = Date.now();
  const year = new Date().getFullYear();
  const next = String(year + 1);

  await signIn(page, accounts.admin);
  await page.goto('/new-year');
  await expect(page.getByRole('heading', { level: 1, name: 'Start the new school year' })).toBeVisible();

  const originalYearId = await page.getByRole('combobox', { name: 'School year' }).locator('option').filter({ hasText: '(current)' }).getAttribute('value');
  expect(originalYearId).toMatch(/^\d+$/);

  try {
    // 1. Next year: the form is filled in from this year; create it (or it exists from an earlier run).
    const form = page.locator('#new-year-form');
    if (await form.count()) {
      await expect(form.getByLabel('Name of the year')).toHaveValue(next);
      await expect(page.getByRole('combobox', { name: 'School year' })).toContainText(`${year} (current)`);
      await Promise.all([page.waitForURL(/\/new-year$/), form.getByRole('button', { name: 'Create next year' }).click()]);
      await page.waitForLoadState('load');
    }
    await expect(page.locator(`[data-next-year]`)).toContainText(`${next} is ready`);
    // Creating it changed nothing for the current year.
    await expect(page.getByRole('combobox', { name: 'School year' })).toContainText(`${year} (current)`);

    // 2. Carry forward.
    await expect(page.getByRole('heading', { name: '2. Carry forward' })).toBeVisible();
    const subjects = page.getByLabel('Subjects per grade, with electives');
    await expect(subjects).toBeVisible();
    await page.getByRole('button', { name: 'Next', exact: true }).filter({ visible: true }).click();

    // 3. Year-end checks.
    await expect(page.getByRole('heading', { name: '3. Year-end checks' })).toBeFocused();
    await expect(page.locator('[data-year-end-check="promotions"]')).toBeVisible();
    await page.getByRole('button', { name: 'Next', exact: true }).filter({ visible: true }).click();

    // 4. Preview.
    await expect(page.getByRole('heading', { name: '4. Preview' })).toBeVisible();
    await expect(page.locator('[data-new-year-preview]')).toContainText(`${next} becomes the current year`);
    await page.getByRole('button', { name: 'Next', exact: true }).filter({ visible: true }).click();

    // 5. Make it current, after confirming.
    await page.getByRole('button', { name: `Make ${next} current` }).click();
    const dialog = page.getByRole('alertdialog');
    await expect(dialog).toBeVisible();
    await Promise.all([page.waitForURL(/\/new-year\/result$/), dialog.getByRole('button', { name: `Make ${next} current` }).click()]);
    await expect(page.getByRole('combobox', { name: 'School year' })).toContainText(`${next} (current)`);
    expect(Date.now() - started, 'the wizard took more than five minutes').toBeLessThan(300_000);

  } finally {
    // Restore through the same scoped source handler even if a later assertion fails.
    await page.goto('/home');
    const years = page.getByRole('combobox', { name: 'School year' });
    const activeYearId = await years.locator('option').filter({ hasText: '(current)' }).getAttribute('value');
    expect(activeYearId).toMatch(/^\d+$/);
    if (activeYearId !== originalYearId) {
      const back = await page.request.post(`/session-years/${originalYearId}/make-current`, {
        headers: { 'X-CSRF-TOKEN': await csrfToken(page), Accept: 'application/json' },
        data: { source_year_id: Number(activeYearId) },
      });
      expect(back.ok()).toBe(true);
      expect((await back.json()).error).toBe(false);
    }
    await page.goto('/home');
    await expect(page.getByRole('combobox', { name: 'School year' }).locator('option').filter({ hasText: '(current)' })).toHaveAttribute('value', originalYearId!);
  }
});
