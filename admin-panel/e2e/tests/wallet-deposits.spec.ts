import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/wallet-deposits-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
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
  test(`Finance verifies actual original-parent money and retains clean receipts at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture();
    expect(fixture('stats', data.school)).toMatchObject({ claims: 2, entries: 0, parent_cents: 0, master_accounts: 0 });
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/wallet-deposits');
    expect(response?.headers()['cache-control']).toContain('no-store');
    await check(page);
    await page.getByRole('link', { name: `WD-${data.claim}`, exact: true }).click();
    await check(page);
    await page.getByRole('combobox', { name: 'Actual received method', exact: true }).selectOption('eft');
    await page.getByRole('textbox', { name: 'Actual received amount', exact: true }).fill('90.12');
    await page.getByRole('textbox', { name: 'Verified outside receipt reference', exact: true }).fill('BANK-ACTUAL-001');
    await page.getByRole('textbox', { name: 'Private independent receipt verification', exact: true }).fill('Private synthetic independent Finance proof');
    await page.getByRole('checkbox', { name: 'I verified that this deposit belongs to the original parent holder shown above.', exact: true }).check();
    await page.getByRole('checkbox', { name: 'I checked the actual money received, amount, date and reference. For cash, I received it and hold it at my desk.', exact: true }).check();
    const button = page.getByRole('button', { name: 'Verify and credit parent deposit', exact: true });
    const form = button.locator('xpath=ancestor::form');
    const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
    const action = await form.getAttribute('action');
    await button.click();
    await expect(page.getByRole('main')).toContainText('Verified parent deposit credited to its original holder.');
    expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
    expect(fixture('stats', data.school)).toMatchObject({ verified: 1, entries: 1, parent_cents: 9012, master_accounts: 0, healthy: true });
    await check(page);
    await page.getByRole('link', { name: 'Open parent deposit receipt', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('90.12');
    await expect(page.getByRole('main')).not.toContainText('Private synthetic');
    await check(page);
    await page.goto(`/wallet-deposits/${data.reject_claim}`);
    await page.getByRole('combobox', { name: 'Parent-visible outcome', exact: true }).selectOption('not_verified');
    await page.getByRole('textbox', { name: 'Private rejection reason', exact: true }).fill('Private synthetic insufficient bank evidence');
    await page.getByRole('checkbox', { name: 'Reject this request without moving any wallet money.', exact: true }).check();
    await page.getByRole('button', { name: 'Reject parent deposit request', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Parent deposit request rejected without moving money.');
    expect(fixture('stats', data.school)).toMatchObject({ verified: 1, rejected: 1, entries: 1, parent_cents: 9012, healthy: true });
    fixture('disable', data.school);
    await page.goto('/wallet-deposits');
    await expect(page.getByRole('button', { name: 'Save parent deposit methods', exact: true })).toBeDisabled();
    await expect(page.getByRole('link', { name: `WD-${data.claim}`, exact: true })).toBeVisible();
    await check(page);
    expect(errors).toEqual([]);
  });
}

test('Teacher is refused Finance parent proofs and receipt screens', async ({ page }) => {
  const data = fixture();
  await signIn(page, data.teacher);
  for (const route of ['/wallet-deposits', `/wallet-deposits/${data.claim}`, `/wallet-deposits/${data.claim}/receipt`]) {
    expect((await page.goto(route))?.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Private synthetic parent-supplied proof');
  }
});
