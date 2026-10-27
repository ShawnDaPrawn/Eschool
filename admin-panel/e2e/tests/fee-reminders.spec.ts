import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-reminders-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}
async function submitOnceAndReplay(page: Page, label: string) {
  const button = page.getByRole('button', { name: label, exact: true });
  const form = button.locator('xpath=ancestor::form');
  const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
  const action = await form.getAttribute('action');
  await button.click();
  await expect(page.getByRole('status')).toBeVisible();
  expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
}
test.describe.configure({ mode: 'serial', retries: 0 });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`Finance configures private fee reminders at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(150_000);
    const data = fixture(); await page.setViewportSize({ width, height: 1000 }); await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/fee-reminders');
    expect(response!.headers()['cache-control']).toContain('no-store');
    await expect(page.getByRole('checkbox', { name: 'Enable scheduled fee reminders', exact: true })).not.toBeChecked();
    await check(page);
    await page.getByRole('checkbox', { name: 'Enable scheduled fee reminders', exact: true }).check();
    await page.getByRole('spinbutton', { name: 'Earliest sending hour in school time', exact: true }).fill('0');
    await submitOnceAndReplay(page, 'Save reminder schedule');
    expect(fixture('run', data.school).created).toBe(2);
    expect(fixture('run', data.school).created).toBe(0);
    await page.reload();
    await expect(page.getByRole('table', { name: 'Fee reminder history', exact: true })).toBeVisible();
    await expect(page.getByRole('table').getByRole('link', { name: 'Synthetic receipt family', exact: true })).toHaveCount(2);
    await check(page);
    const stats = fixture('stats', data.school);
    expect(stats.reminders).toBe(2); expect(stats.policy_revision).toBe(1);
    expect(stats.entries).toBe(2); expect(stats.payments).toBe(0); expect(stats.allocations).toBe(0);
    expect(stats.receivable_cents).toBe(3003);
    expect(errors).toEqual([]);
  });
}
test('Teacher cannot configure or see Finance reminder history', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  expect((await page.goto('/fee-reminders'))!.status()).toBe(403);
  await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
});
