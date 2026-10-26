import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
test.beforeAll(() => execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/digest-fixture.php');"], { cwd: path.resolve(process.cwd(), '..') }));
for (const width of [390, 820, 1280]) {
  test(`manager authors approves and withdraws private weekly wording at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/weekly-digest-templates');
    const form = page.getByRole('form', { name: 'Create a language version', exact: true });
    await form.getByRole('combobox', { name: 'Language', exact: true }).selectOption('en');
    const title = `Synthetic weekly wording ${width}`;
    await form.getByRole('textbox', { name: 'Digest title', exact: true }).fill(title);
    await form.getByRole('button', { name: 'Save language draft', exact: true }).click();
    const version = page.locator('article').filter({ hasText: title });
    await expect(version).toContainText('Not approved for delivery');
    await version.getByRole('button', { name: 'Approve this language version', exact: true }).click();
    await expect(version).toContainText('Approved for weekly delivery');
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    await version.getByRole('button', { name: 'Withdraw language approval', exact: true }).click();
    await expect(version).toContainText('Not approved for delivery');
    expect(errors).toEqual([]);
  });
}
