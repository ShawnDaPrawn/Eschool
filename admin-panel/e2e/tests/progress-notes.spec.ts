import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
function prepare() {
  execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/progress-notes-fixture.php');"],
    { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
}
for (const width of [390, 1280]) {
  test(`private notes and principal-only assignment controls at ${width}px`, async ({ page, baseURL }) => {
    prepare();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, accounts.teacher);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/learner-notes');
    let choose = page.getByRole('form', { name: 'Choose learner notes', exact: true });
    const group = await choose.locator('select[name=group_id] option').filter({ hasText: 'Mastery browser group' }).getAttribute('value');
    await choose.getByRole('combobox', { name: 'Teaching group', exact: true }).selectOption(group!);
    await choose.getByRole('button', { name: 'View notes', exact: true }).click();
    choose = page.getByRole('form', { name: 'Choose learner notes', exact: true });
    const learner = await choose.locator('select[name=learner_id] option').nth(1).getAttribute('value');
    await choose.getByRole('combobox', { name: 'Learner', exact: true }).selectOption(learner!);
    await choose.getByRole('button', { name: 'View notes', exact: true }).click();
    const draft = `Synthetic teaching observation ${width} <script>window.privateNoteExecuted=1</script>`;
    const create = page.getByRole('form', { name: 'Create learner note', exact: true });
    await create.getByRole('textbox', { name: 'Private note text', exact: true }).fill(draft);
    await create.getByRole('button', { name: 'Save new note', exact: true }).click();
    await expect(page.getByLabel('Current saved note', { exact: true })).toHaveText(draft);
    expect(await page.evaluate(() => (window as unknown as Record<string, unknown>).privateNoteExecuted)).toBeUndefined();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const duplicateIds = await page.evaluate(() => {
      const ids = [...document.querySelectorAll('[id]')].map((n) => n.id);
      return ids.filter((id, i) => ids.indexOf(id) !== i);
    });
    expect(duplicateIds).toEqual([]);
    await page.goto(`/learner-notes?mode=counselling&learner_id=${learner}`);
    await page.locator('.note-list-item a').first().click();
    await expect(page.getByLabel('Current saved note', { exact: true })).toHaveText('Synthetic confidential counselling observation');
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.context().clearCookies(); await signIn(page, accounts.admin);
    await page.goto(`/learner-notes?mode=assignments&learner_id=${learner}`);
    await expect(page.getByText('Synthetic confidential counselling observation', { exact: true })).toHaveCount(0);
    await expect(page.getByRole('textbox', { name: 'Private note text', exact: true })).toHaveCount(0);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.getByRole('button', { name: 'End note access now', exact: true }).click();
    await expect(page.getByText('Access ended', { exact: false }).first()).toBeVisible();
    expect(errors).toEqual([]);
    await page.context().clearCookies(); await signIn(page, accounts.teacher);
    const denied = await page.goto(`/learner-notes?mode=counselling&learner_id=${learner}`);
    expect(denied?.status()).toBe(404);
    await expect(page.getByText('Synthetic confidential counselling observation', { exact: true })).toHaveCount(0);
  });
}
