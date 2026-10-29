import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

// Short days (engineering/specs/academics/short-days.md): the planning form on
// the timetable workbench is labelled, accessible and fits both panel widths.
test.use({ storageState: { cookies: [], origins: [] } });

for (const width of [1024, 1280]) {
  test(`short-day planning form at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-timetable?week_on=2026-11-02');
    await page.getByRole('link', { name: 'Short days', exact: true }).click();
    await page.locator('#short-days > summary').click();
    const form = page.getByRole('form', { name: 'Plan a short day', exact: true });
    await expect(form).toBeVisible();
    await expect(form.getByLabel('Short day date')).toBeVisible();
    await expect(form.getByRole('textbox', { name: 'Reason for the short day' })).toBeVisible();
    await expect(form.getByRole('button', { name: 'Save short day', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
