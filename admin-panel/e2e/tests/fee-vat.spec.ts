import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-vat-fixture.php'));"], {
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
  test(`Finance declares VAT and reviews original tax invoice and credit at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture(); await page.setViewportSize({ width, height: 1000 }); await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/fee-items/tax-settings');
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('checkbox', { name: 'Enable registered VAT for new fee item charges', exact: true }).check();
    for (const [label, value] of [['Registered country code','ZA'],['VAT registration number','VAT-SYNTHETIC'],['Legal seller name','Synthetic registered seller'],['Legal seller address','1 Synthetic School Road']]) {
      await page.getByRole('textbox', { name: label, exact: true }).fill(value);
    }
    await check(page); await submitOnceAndReplay(page, 'Save school VAT details');
    await page.goto('/fee-items/new');
    await page.getByRole('textbox', { name: 'Item name', exact: true }).fill('Synthetic taxed excursion');
    await page.getByRole('combobox', { name: 'Charged once per', exact: true }).selectOption('family');
    await page.getByRole('textbox', { name: 'Item amount', exact: true }).fill('10.01');
    await page.getByRole('combobox', { name: 'Tax treatment', exact: true }).selectOption('vat_inclusive');
    await page.getByRole('textbox', { name: 'VAT percentage', exact: true }).fill('15.00');
    await page.getByRole('combobox', { name: 'Status', exact: true }).selectOption('active');
    await check(page); await submitOnceAndReplay(page, 'Save fee item');
    await page.goto('/fee-items'); await page.getByRole('link', { name: 'Prepare item charge', exact: true }).click();
    await page.getByRole('combobox', { name: 'Family account', exact: true }).selectOption(String(data.account));
    await page.getByRole('button', { name: 'Load charge details', exact: true }).click();
    await page.getByRole('textbox', { name: 'Unique occurrence reference', exact: true }).fill(`SYNTHETIC-VAT-${data.school}`);
    await page.getByRole('button', { name: 'Review item charge', exact: true }).click();
    for (const amount of ['ZAR 8.70','ZAR 1.31','ZAR 10.01','VAT-SYNTHETIC']) await expect(page.getByRole('main')).toContainText(amount);
    await check(page);
    await page.getByRole('checkbox', { name: 'I confirm this item, occurrence and payer allocation should create the invoice shown above.', exact: true }).check();
    await submitOnceAndReplay(page, 'Post item invoice once');
    await page.getByRole('link', { name: 'Open original invoice', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Synthetic registered seller');
    await expect(page.getByRole('main')).toContainText('ZAR 1.31'); await check(page);
    const invoiceNumber = await page.getByRole('heading', { name: /^ITEM-/ }).innerText();
    await page.getByRole('link', { name: 'Prepare credit note', exact: true }).click();
    await page.getByRole('row').filter({ hasText: invoiceNumber }).first().getByRole('link', { name: 'Credit this share', exact: true }).click();
    await page.getByRole('textbox', { name: 'Credit amount', exact: true }).fill('1.00');
    await page.getByRole('combobox', { name: 'Correction category', exact: true }).selectOption('billing_error');
    await page.getByRole('textbox', { name: 'Private Finance explanation', exact: true }).fill('Synthetic original VAT correction.');
    await page.getByRole('button', { name: 'Review credit note', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('VAT included in this credit');
    await expect(page.getByRole('main')).toContainText('ZAR 0.13'); await check(page);
    await page.getByRole('checkbox', { name: 'I confirm this credit should correct the recorded share and restore any paid amount as shown.', exact: true }).check();
    await submitOnceAndReplay(page, 'Post credit note once');
    await expect(page.getByRole('main')).toContainText('ZAR 0.87'); await check(page);
    const stats = fixture('stats', data.school);
    expect(stats).toMatchObject({ charges:1,notes:1,entries:3,charge_vat:131,vat_cents:118,receivable_cents:2903,income_cents:2785 });
    expect(errors).toEqual([]);
  });
}
test('Teacher cannot access school VAT registration', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  expect((await page.goto('/fee-items/tax-settings'))!.status()).toBe(403);
  await expect(page.locator('body')).not.toContainText('Legal seller address');
});
