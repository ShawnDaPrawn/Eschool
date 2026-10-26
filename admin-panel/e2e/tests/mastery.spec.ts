import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
function php(code: string) { return execFileSync('php', ['artisan', 'tinker', '--execute', code], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' }); }
test.beforeAll(() => php("require base_path('e2e/support/mastery-fixture.php');"));
for (const width of [390, 820, 1280]) {
  test(`manager edits sourced prerequisites and allocated teacher reads guidance at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/growth-mastery');
    const chooser = page.getByRole('form', { name: 'Choose graph subject', exact: true });
    const [subjectId] = await chooser.getByRole('combobox', { name: 'Subject', exact: true }).selectOption({ label: 'Mastery browser rehearsal' });
    await Promise.all([
      page.waitForURL(url => url.searchParams.get('subject_id') === subjectId),
      chooser.getByRole('button', { name: 'Open subject graph', exact: true }).click(),
    ]);
    let editor = page.getByRole('form', { name: 'Edit sourced prerequisites', exact: true });
    for (const button of await editor.getByRole('button', { name: 'Remove prerequisite', exact: true }).all()) await button.click();
    await editor.getByRole('button', { name: 'Add prerequisite', exact: true }).click();
    let edge = editor.locator('[data-mastery-edge]').first();
    await edge.getByRole('combobox', { name: 'Topic', exact: true }).selectOption({ label: 'Mastery browser fractions' });
    await edge.getByRole('combobox', { name: 'Prerequisite topic', exact: true }).selectOption({ label: 'Mastery browser foundation' });
    await edge.getByRole('textbox', { name: 'Source citation', exact: true }).fill('Synthetic browser source citation');
    await editor.getByRole('button', { name: 'Save sourced graph', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Mastery graph saved' }).first()).toBeVisible();
    editor = page.getByRole('form', { name: 'Edit sourced prerequisites', exact: true });
    edge = editor.locator('[data-mastery-edge]').first();
    await expect(edge.getByRole('textbox', { name: 'Source citation', exact: true })).toHaveValue('Synthetic browser source citation');
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.context().clearCookies();
    await signIn(page, accounts.teacher);
    await page.goto('/growth-mastery');
    const group = page.getByRole('form', { name: 'Choose teaching group', exact: true });
    const groupId = await group.locator('select[name=group_id] option').filter({ hasText: 'Mastery browser group' }).first().getAttribute('value');
    await group.getByRole('combobox', { name: 'Teaching group', exact: true }).selectOption(groupId!);
    await group.getByRole('button', { name: 'Open group', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Mastery browser foundation', exact: true }).first()).toBeVisible();
    await expect(page.getByText('Level 0 / 4', { exact: false }).first()).toBeVisible();
    await expect(page.getByRole('form', { name: 'Edit sourced prerequisites', exact: true })).toHaveCount(0);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
