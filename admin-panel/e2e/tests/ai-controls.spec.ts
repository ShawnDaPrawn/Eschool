import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
test.beforeAll(() => execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/ai-controls-fixture.php');"], { cwd: path.resolve(process.cwd(), '..') }));
for (const width of [390, 820, 1280]) {
  test(`school manager changes bounded AI caps at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/ai-settings');
    const form = page.getByRole('form', { name: 'School AI limits', exact: true });
    await form.getByRole('spinbutton', { name: 'School · Daily tokens', exact: true }).fill(String(width));
    await form.getByRole('combobox', { name: 'Learning inference', exact: true }).selectOption('1');
    await form.getByRole('button', { name: 'Save AI limits', exact: true }).click();
    await expect(page.getByRole('spinbutton', { name: 'School · Daily tokens', exact: true })).toHaveValue(String(width));
    await expect(page.getByRole('combobox', { name: 'Learning inference', exact: true })).toHaveValue('1');
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(errors).toEqual([]);
  });
  test(`explicitly granted platform operator sees aggregate school costs at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.finance);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/platform/ai-costs');
    await expect(page.getByRole('heading', { name: 'School AI costs', exact: true })).toBeVisible();
    await expect(page.getByText('USD', { exact: true })).toBeVisible();
    await expect(page.getByText('Synthetic private learning body never belongs in costs', { exact: true })).toHaveCount(0);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(errors).toEqual([]);
    await page.evaluate(() => { Object.defineProperty(document, 'hidden', { configurable: true, value: true }); document.dispatchEvent(new Event('visibilitychange')); });
    await expect(page.locator('[data-ai-private-review]')).toBeEmpty();
  });
}
