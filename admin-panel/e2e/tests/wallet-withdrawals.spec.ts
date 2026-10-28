import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/wallet-withdrawals-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, i, all) => all.indexOf(id) !== i))).toEqual([]);
}
test.describe.configure({ mode: 'serial', retries: 0 });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`Finance approves and records original-holder payout with retained history at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture();
    expect(fixture('stats', data.school)).toMatchObject({ withdrawals: 2, paid: 0, own_cents: 19000, healthy: true });
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance); const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/wallet-withdrawals');expect(response?.headers()['cache-control']).toContain('no-store');
    await check(page);
    await page.getByRole('combobox', { name: 'Method at exactly the boundary', exact: true }).selectOption('1');
    await page.getByRole('button', { name: 'Save withdrawal policy', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Withdrawal policy saved.');
    expect(fixture('stats', data.school).cash_at_boundary).toBe(true);
    await page.getByRole('link', { name: `WW-${data.bank_withdrawal}`, exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Private synthetic original parent bank instructions');
    await check(page);
    await page.getByRole('checkbox', { name: 'I verified the original adult holder and their cash pickup or bank instructions.', exact: true }).check();
    await page.getByRole('button', { name: 'Approve withdrawal', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Withdrawal approved; no outside payment has been sent.');
    await expect(page.getByRole('main')).toContainText('Approved, awaiting actual payment');
    await page.getByRole('textbox', { name: 'Actual cash or bank payout reference', exact: true }).fill('SYNTHETIC-BANK-PAID-001');
    await page.getByRole('textbox', { name: 'Private verified payout proof', exact: true }).fill('Private synthetic verified actual payout evidence');
    await page.getByRole('checkbox', { name: 'I verified that the payment went to this original adult holder.', exact: true }).check();
    await page.getByRole('checkbox', { name: 'I verified the completed bank payment, amount, date and reference.', exact: true }).check();
    const button = page.getByRole('button', { name: 'Record verified payout', exact: true });
    const form = button.locator('xpath=ancestor::form');
    const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
    const action = await form.getAttribute('action');await button.click();
    await expect(page.getByRole('main')).toContainText('Verified original-holder payout recorded.');
    expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
    expect(fixture('stats', data.school)).toMatchObject({ paid: 1, own_cents: 19000, healthy: true });
    await check(page);
    await page.getByRole('link', { name: 'Open withdrawal payout receipt', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('300.00');
    await expect(page.getByRole('main')).not.toContainText('Private synthetic');
    await expect(page.getByRole('main')).not.toContainText('SYNTHETIC-BANK-PAID-001');await check(page);
    await page.goto(`/wallet-withdrawals/${data.cash_withdrawal}`);
    await page.getByRole('textbox', { name: 'Private reservation release reason', exact: true }).fill('Synthetic request withdrawn before any payout');
    await page.getByRole('checkbox', { name: 'No outside payout has been made; return only the original reserved wallet money.', exact: true }).check();
    await page.getByRole('button', { name: 'Reject withdrawal', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Withdrawal reservation returned to its original wallet.');
    expect(fixture('stats', data.school)).toMatchObject({ paid: 1, own_cents: 20000, healthy: true });
    fixture('disable', data.school);await page.goto('/wallet-withdrawals');
    await expect(page.getByRole('button', { name: 'Save withdrawal policy', exact: true })).toBeDisabled();
    await expect(page.getByRole('link', { name: `WW-${data.bank_withdrawal}`, exact: true })).toBeVisible();await check(page);
    expect(errors).toEqual([]);
  });
}
test('Teacher cannot read Finance bank instructions or payout receipts', async ({ page }) => {
  const data = fixture();await signIn(page, data.teacher);
  for (const route of ['/wallet-withdrawals', `/wallet-withdrawals/${data.bank_withdrawal}`, `/wallet-withdrawals/${data.bank_withdrawal}/receipt`]) {
    expect((await page.goto(route))?.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Private synthetic original parent bank instructions');
  }
});
