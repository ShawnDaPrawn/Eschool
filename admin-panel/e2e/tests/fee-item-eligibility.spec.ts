import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-item-eligibility-fixture.php'));"], {
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
  test(`Finance configures dated route billing and posts one family occurrence at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture(); await page.setViewportSize({ width, height: 1000 }); await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto(`/fee-items/${data.item}/rules`);
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('combobox', { name: 'Item charging mode', exact: true }).selectOption('membership');
    await page.getByRole('combobox', { name: 'Membership source', exact: true }).selectOption(`activity_group:${data.group}`);
    await check(page); await submitOnceAndReplay(page, 'Save item eligibility');
    await page.getByRole('link', { name: 'Prepare automatic item batch', exact: true }).click();
    await page.getByRole('textbox', { name: 'Item charge date', exact: true }).fill(data.date);
    await page.getByRole('textbox', { name: 'Invoice due date', exact: true }).fill(data.due);
    await page.getByRole('textbox', { name: 'Unique occurrence reference', exact: true }).fill(`SYNTHETIC-ROUTE-${data.school}`);
    await check(page); await page.getByRole('button', { name: 'Review automatic item batch', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('ZAR 10.01');
    await expect(page.getByRole('row')).toHaveCount(2);
    for (const learner of data.learners) await expect(page.getByRole('table')).toContainText(learner.name);
    expect(fixture('stats', data.school).charges).toBe(0); await check(page);
    await page.getByRole('checkbox', { name: 'I confirm these reviewed item charges and payer allocations should create invoices.', exact: true }).check();
    await submitOnceAndReplay(page, 'Post automatic item batch once');
    await expect(page.getByRole('main')).toContainText('Posted item invoices'); await check(page);
    expect(fixture('stats', data.school)).toMatchObject({ batches:1,charges:1,item_lines:2,entries:2,receivable_cents:3003,income_cents:3003 });
    expect(fixture('disable_memberships', data.school)).toEqual({ memberships_disabled:true });
    await page.goto(`/fee-item-batches/new?fee_item_id=${data.item}`);
    await page.getByRole('textbox', { name: 'Item charge date', exact: true }).fill(data.date);
    await page.getByRole('textbox', { name: 'Invoice due date', exact: true }).fill(data.due);
    await page.getByRole('textbox', { name: 'Unique occurrence reference', exact: true }).fill(`NEXT-ROUTE-${data.school}`);
    await page.getByRole('button', { name: 'Review automatic item batch', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Post automatic item batch once', exact: true })).toBeDisabled();
    expect(fixture('stats', data.school).charges).toBe(1); await check(page); expect(errors).toEqual([]);
  });
}
test('Teacher cannot configure billing memberships or see batch history', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  for (const url of [`/fee-items/${data.item}/rules`, '/fee-item-batches', '/fee-item-batches/new']) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic receipt family');
  }
});
