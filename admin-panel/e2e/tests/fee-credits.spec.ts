import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-credits-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}

test.describe.configure({ mode: 'serial', retries: 0 });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`Finance reviews a paid correction and restores the original held credit once at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    const data = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    await page.goto(`/fee-credit-notes/new?account_id=${data.account}`);
    await expect(page.getByRole('table')).toContainText(data.learners[0].name);
    await check(page);
    const response = await page.goto(`/fee-credit-notes/new?invoice_line_id=${data.line}&holder_user_id=${data.parents[0]}`);
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('textbox', { name: 'Credit amount', exact: true }).fill('3.00');
    await page.getByRole('combobox', { name: 'Correction category', exact: true }).selectOption('billing_error');
    await page.getByRole('textbox', { name: 'Private Finance explanation', exact: true }).fill('Synthetic Finance-only billing correction.');
    await check(page);
    await page.getByRole('button', { name: 'Review credit note', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Restored held credit');
    await expect(page.getByRole('main')).toContainText('ZAR 3.00');
    expect(fixture('stats', data.school).notes).toBe(0);
    await check(page);
    await page.getByRole('checkbox', { name: 'I confirm this credit should correct the recorded share and restore any paid amount as shown.', exact: true }).check();
    const button = page.getByRole('button', { name: 'Post credit note once', exact: true });
    const form = button.locator('xpath=ancestor::form');
    const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
    const action = await form.getAttribute('action');
    await button.click();
    await expect(page.getByRole('status')).toBeVisible();
    expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
    await expect(page.getByRole('main')).toContainText('Funds restored to original receipts');
    await check(page);
    await page.getByRole('link', { name: 'Account statement and receipts', exact: true }).click();
    for (const amount of ['ZAR 20.02', 'ZAR 3.00', 'ZAR 17.02', 'ZAR 11.02']) await expect(page.getByRole('main')).toContainText(amount);
    await check(page);
    expect(fixture('stats', data.school)).toEqual({ payments: 1, allocations: 2, runs: 1, entries: 4, receivable_cents: 1102, income_cents: 1702, bank_cents: 900, credit_cents: 300, inactive_learners: 2, notes: 1, releases: 1, gross_invoice_cents: 2002 });
    expect(errors).toEqual([]);
  });
}
test('Teacher is refused credit notes and private explanations', async ({ page }) => {
  const data = fixture();
  await signIn(page, data.teacher);
  for (const url of ['/fee-credit-notes', '/fee-credit-notes/new', `/fee-credit-notes/new?invoice_line_id=${data.line}&holder_user_id=${data.parents[0]}`]) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
  }
});
