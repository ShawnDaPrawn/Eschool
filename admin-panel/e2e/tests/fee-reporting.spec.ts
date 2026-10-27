import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { readFileSync } from 'node:fs';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-reporting-fixture.php'));"], {
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
  test(`Finance reviews balances and downloads reconciled exports at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(150_000);
    const data = fixture(); await page.setViewportSize({ width, height: 1000 }); await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/fee-reporting');
    expect(response!.headers()['cache-control']).toContain('no-store');
    await expect(page.getByRole('main')).toContainText('ZAR 20.02');
    await expect(page.getByRole('main')).toContainText('ZAR 4.00');
    await check(page);
    await page.getByRole('combobox', { name: 'Families shown', exact: true }).selectOption('overdue');
    await page.getByRole('button', { name: 'Show balances', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('No families in this report filter');
    await page.getByRole('combobox', { name: 'Families shown', exact: true }).selectOption('credit');
    await page.getByRole('button', { name: 'Show balances', exact: true }).click();
    await expect(page.getByRole('link', { name: 'Synthetic receipt family', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Accounting export mapping', exact: true }).click();
    await page.getByRole('textbox', { name: 'Xero organisation base currency', exact: true }).fill('ZAR');
    await page.getByRole('textbox', { name: 'QuickBooks organisation base currency', exact: true }).fill('ZAR');
    await page.getByRole('textbox', { name: 'Exact Xero zero-tax label', exact: true }).fill('No Tax');
    for (let i = 0; i < 5; i++) {
      await page.getByRole('textbox', { name: 'Xero account code', exact: true }).nth(i).fill(`CODE-${i + 1}`);
      await page.getByRole('textbox', { name: 'QuickBooks account name', exact: true }).nth(i).fill(`Chart ${i + 1}`);
    }
    await check(page); await submitOnceAndReplay(page, 'Save accounting mapping');
    await expect(page.getByRole('status')).toContainText('Accounting export mapping saved.');
    await page.getByRole('link', { name: 'Back to arrears and accounting', exact: true }).click();
    const expected = { ageing: 'AsOf,FamilyReference', charges: 'AsOf,FamilyReference,Invoice', journal: 'Journal,PostedAtUTC', xero: 'Narration,Date,Description,AccountCode', quickbooks: 'JournalNo,JournalDate,AccountName' };
    for (const [layout, header] of Object.entries(expected)) {
      await page.getByRole('combobox', { name: 'Export layout', exact: true }).selectOption(layout);
      const [download] = await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Download private CSV', exact: true }).click()]);
      const csv = readFileSync((await download.path())!, 'utf8');
      expect(csv.startsWith(header)).toBeTruthy();
      if (layout !== 'xero') expect(csv).toContain('ZAR');
      expect(csv).not.toContain('Baobab');
      if (layout === 'xero') expect(csv).toContain('-20.02');
    }
    await check(page);
    expect(fixture('stats', data.school)).toEqual({ payments: 1, allocations: 0, runs: 0, entries: 2, receivable_cents: 2002, income_cents: 2002, bank_cents: 400, credit_cents: 400, inactive_learners: 2, mapping_revision: 1 });
    expect(errors).toEqual([]);
  });
}
test('Teacher is refused Finance reports and export settings', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  for (const url of ['/fee-reporting', '/fee-reporting/settings']) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
  }
});
