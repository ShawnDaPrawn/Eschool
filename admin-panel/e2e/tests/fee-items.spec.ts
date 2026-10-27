import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-items-fixture.php'));"], {
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
for (const width of [390, 820, 1280]) for (const scope of ['learner', 'family']) {
  test(`Finance creates an item and posts one reviewed ${scope} charge at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    const data = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/fee-items/new');
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('textbox', { name: 'Item name', exact: true }).fill(`Synthetic ${scope} activity fee`);
    await page.getByRole('combobox', { name: 'Charged once per', exact: true }).selectOption(scope);
    await page.getByRole('textbox', { name: 'Item amount', exact: true }).fill('10.01');
    await page.getByRole('combobox', { name: 'Tax treatment', exact: true }).selectOption('no_vat');
    await page.getByRole('combobox', { name: 'Status', exact: true }).selectOption('active');
    await check(page);
    await submitOnceAndReplay(page, 'Save fee item');
    await expect(page.getByRole('status')).toContainText('Fee item saved.');
    await page.goto('/fee-items');
    await page.getByRole('link', { name: 'Prepare item charge', exact: true }).click();
    await page.getByRole('combobox', { name: 'Family account', exact: true }).selectOption(String(data.account));
    await page.getByRole('button', { name: 'Load charge details', exact: true }).click();
    if (scope === 'learner') await page.getByRole('combobox', { name: 'Learner receiving this charge', exact: true }).selectOption(String(data.learners[0].id));
    else for (const learner of data.learners) await expect(page.getByRole('main')).toContainText(learner.name);
    await page.getByRole('textbox', { name: 'Unique occurrence reference', exact: true }).fill(`SYNTHETIC-ACTIVITY-${data.school}`);
    await check(page);
    await page.getByRole('button', { name: 'Review item charge', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Recorded holder totals');
    await expect(page.getByRole('main')).toContainText('ZAR 10.01');
    expect(fixture('stats', data.school).charges).toBe(0);
    await check(page);
    await page.getByRole('checkbox', { name: 'I confirm this item, occurrence and payer allocation should create the invoice shown above.', exact: true }).check();
    await submitOnceAndReplay(page, 'Post item invoice once');
    await expect(page.getByRole('status')).toContainText('Fee item charged once.');
    await check(page);
    await page.getByRole('link', { name: 'Open original invoice', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('ZAR 10.01');
    await expect(page.getByRole('main')).toContainText('No VAT charged on this invoice.');
    await check(page);
    await page.getByRole('link', { name: 'Back to fee charge', exact: true }).click();
    await page.getByRole('link', { name: 'Account statement and receipts', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('ZAR 30.03');
    await expect(page.getByRole('main')).toContainText(`Synthetic ${scope} activity fee`);
    await check(page);
    expect(fixture('stats', data.school)).toEqual({ payments: 0, allocations: 0, runs: 0, entries: 2, receivable_cents: 3003, income_cents: 3003, bank_cents: 0, credit_cents: 0, inactive_learners: 2, items: 1, charges: 1, invoices: 2, annual_lines: 2, item_lines: scope === 'family' ? 2 : 1 });
    expect(errors).toEqual([]);
  });
}
test('Teacher is refused fee item catalogue and charge workflows', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  for (const url of ['/fee-items', '/fee-items/new', '/fee-item-charges', `/fee-item-charges/new?fee_account_id=${data.account}`]) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
  }
});
