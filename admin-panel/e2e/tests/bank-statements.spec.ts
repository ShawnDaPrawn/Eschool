import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0, existing = false) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/bank-statements-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, BANK_FIXTURE_EXISTING: existing ? '1' : '0', BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
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
for (const width of [390, 820, 1280]) for (const existing of [false, true]) {
  test(`Finance imports, reviews and ${existing ? 'links an existing' : 'records a new'} EFT at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(150_000);
    const data = fixture('prepare', 0, existing);
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const csv = Buffer.from('received_on,transaction_reference,family_reference,description,credit,debit,currency\n' +
      `${data.date},${data.bank_reference},${data.family_reference},Synthetic family payment,4.00,,ZAR\n` +
      `${data.date},${data.bank_reference}-FEE,,Synthetic bank charge,,0.01,ZAR\n`);
    async function upload() {
      const response = await page.goto('/bank-statements/new');
      expect(response!.headers()['cache-control']).toContain('no-store');
      await page.getByLabel('Bank CSV file', { exact: true }).setInputFiles({ name: 'synthetic.csv', mimeType: 'text/csv', buffer: csv });
      await check(page);
      await page.getByRole('button', { name: 'Review bank import', exact: true }).click();
      await expect(page.getByRole('main')).toContainText('ZAR 4.00');
      await expect(page.getByRole('main')).toContainText('ZAR 0.01');
      await check(page);
      await page.getByRole('checkbox', { name: 'I confirm these bank rows should be retained for reconciliation. Importing does not record received fees.', exact: true }).check();
      await submitOnceAndReplay(page, 'Import reviewed bank rows once');
    }
    await upload();
    const before = fixture('stats', data.school);
    expect(before.payments).toBe(existing ? 1 : 0);
    expect(before.bank_lines).toBe(2);
    expect(before.imports).toBe(1);
    await upload(); // A reopened import uses another UUID but retains the same bank rows.
    await page.getByRole('row').filter({ has: page.getByRole('cell', { name: data.bank_reference, exact: true }) }).getByRole('link', { name: 'Open bank transaction', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Suggested family from exact payment reference');
    await page.getByRole('combobox', { name: 'Holder whose share this pays', exact: true }).selectOption(String(data.parents[0]));
    await page.getByRole('textbox', { name: 'Bank match verification note', exact: true }).fill('Verified synthetic bank evidence, payer and legacy records.');
    await check(page);
    await page.getByRole('button', { name: 'Review EFT match', exact: true }).click();
    await expect(page.getByRole('main')).toContainText(existing
      ? 'This bank transaction will link to its existing EFT receipt. No second receipt or posting will be created.'
      : 'Confirmation creates one verified EFT receipt as held credit. Review allocation separately before it reduces invoice debt.');
    expect(fixture('stats', data.school).payments).toBe(existing ? 1 : 0);
    await check(page);
    await page.getByRole('checkbox', { name: 'I verified the bank evidence, payer and receipt history and confirm this resolution. Resolved bank history cannot be edited.', exact: true }).check();
    await submitOnceAndReplay(page, 'Confirm bank resolution once');
    await check(page);
    await page.getByRole('link', { name: 'Open matched fee receipt', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('ZAR 4.00');
    await check(page);
    await page.goto('/bank-statements');
    await expect(page.getByRole('cell', { name: data.bank_reference, exact: true })).toHaveCount(0);
    await page.getByRole('link', { name: 'Open bank transaction', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Bank debits cannot become fee receipts.');
    await page.getByRole('textbox', { name: 'Reason this is not a fee receipt', exact: true }).fill('Verified synthetic bank charge unrelated to school fee receipts.');
    await page.getByRole('button', { name: 'Review bank exclusion', exact: true }).click();
    await check(page);
    await page.getByRole('checkbox', { name: 'I verified the bank evidence, payer and receipt history and confirm this resolution. Resolved bank history cannot be edited.', exact: true }).check();
    await submitOnceAndReplay(page, 'Confirm bank resolution once');
    await page.goto('/bank-statements');
    await expect(page.getByRole('main')).toContainText('No bank rows in this queue');
    await page.getByRole('combobox', { name: 'Bank queue', exact: true }).selectOption('all');
    await page.getByRole('button', { name: 'Show bank rows', exact: true }).click();
    await expect(page.getByRole('cell', { name: 'Matched', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Excluded', exact: true })).toBeVisible();
    await check(page);
    expect(fixture('stats', data.school)).toEqual({
      payments: 1, allocations: 0, runs: 0, entries: 2, receivable_cents: 2002, income_cents: 2002, bank_cents: 400, credit_cents: 400, inactive_learners: 2,
      imports: 1, bank_lines: 2, matched: 1, excluded: 1,
    });
    expect(errors).toEqual([]);
  });
}
test('Teacher is refused private bank imports and queue', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  for (const url of ['/bank-statements', '/bank-statements/new', '/bank-statements/template']) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
  }
});
