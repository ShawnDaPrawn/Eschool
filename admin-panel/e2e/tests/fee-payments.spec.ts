import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Fixture = { school: number; account: number; finance: string; teacher: string; parents: number[]; learners: { id: number; name: string }[] };
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-payments-fixture.php'));"], {
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
  const data = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
  const action = await form.getAttribute('action');
  await button.click();
  await expect(page.getByRole('status')).toBeVisible();
  expect((await page.request.post(action!, { form: data })).status()).toBe(200);
}

test.describe.configure({ mode: 'serial', retries: 0 });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`Finance records, allocates and reviews a split-family receipt once at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    const data: Fixture = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto(`/fee-payments/new?account_id=${data.account}`);
    expect(response!.status()).toBe(200);
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('combobox', { name: 'Holder whose share this pays', exact: true }).selectOption(String(data.parents[0]));
    await page.getByRole('combobox', { name: 'Receipt method', exact: true }).selectOption('eft');
    await page.getByRole('textbox', { name: 'Unique cash receipt or bank transaction reference', exact: true }).fill(`SYNTHETIC-BANK-${data.school}`);
    await page.getByRole('textbox', { name: 'Amount received', exact: true }).fill('20.00');
    await page.getByRole('checkbox', { name: 'I verified the received funds and confirm this receipt has not already been recorded.', exact: true }).check();
    await check(page);
    await submitOnceAndReplay(page, 'Record receipt once');
    await expect(page.getByRole('status')).toContainText('Verified fee receipt recorded once.');
    const receiptUrl = page.url();
    await check(page);
    await page.getByRole('link', { name: 'Review credit allocation', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Choose the school allocation rule before allocating this credit.');
    expect(fixture('stats', data.school).credit_cents).toBe(2000);
    await page.goto('/fee-payments');
    await page.getByRole('combobox', { name: 'Allocation order', exact: true }).selectOption('oldest_first');
    await page.getByRole('button', { name: 'Save allocation rule', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Fee allocation rule saved.');
    await check(page);
    await page.goto(`${receiptUrl}/allocation`);
    await expect(page.getByRole('main')).toContainText('ZAR 10.02');
    await expect(page.getByRole('main')).toContainText('ZAR 9.98');
    for (const learner of data.learners) await expect(page.getByRole('table')).toContainText(learner.name);
    await page.getByRole('checkbox', { name: 'I confirm this held credit should pay the invoice shares shown above.', exact: true }).check();
    await check(page);
    await submitOnceAndReplay(page, 'Post reviewed allocation');
    await expect(page.getByRole('status')).toContainText('Payment allocated once.');
    await check(page);
    await page.getByRole('link', { name: 'Account statement and receipts', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('ZAR 10.00');
    await expect(page.getByRole('main')).toContainText('ZAR 9.98');
    for (const learner of data.learners) await expect(page.getByRole('main')).toContainText(learner.name);
    await check(page);
    expect(fixture('stats', data.school)).toEqual({ payments: 1, allocations: 2, runs: 1, entries: 3, receivable_cents: 1000, income_cents: 2002, bank_cents: 2000, credit_cents: 998, inactive_learners: 2 });
    expect(errors).toEqual([]);
  });
}

test('Teacher is refused receipt, allocation policy and family statement screens', async ({ page }) => {
  const data: Fixture = fixture();
  await signIn(page, data.teacher);
  for (const url of ['/fee-payments', '/fee-payments/new', `/fee-payments/accounts/${data.account}/statement`]) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
  }
});
