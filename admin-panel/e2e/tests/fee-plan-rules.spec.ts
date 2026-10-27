import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-plan-rules-fixture.php'));"], {
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
  test(`Finance reviews rule-driven installment billing at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture(); await page.setViewportSize({ width, height: 1000 }); await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto(`/fee-plans/${data.plan}/edit`);
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.locator('summary').filter({ hasText: 'Additional matching rules' }).click();
    await page.getByRole('combobox', { name: 'Rule field', exact: true }).first().selectOption(`profile:${data.campus}`);
    await page.getByRole('textbox', { name: 'Matching values, one per line', exact: true }).first().fill('North');
    await page.locator('summary').filter({ hasText: 'Payment schedules' }).click();
    await page.getByRole('textbox', { name: 'Option code', exact: true }).nth(0).fill('terms');
    await page.getByRole('textbox', { name: 'Option name', exact: true }).nth(0).fill('Four installments');
    await page.getByRole('textbox', { name: 'Installment dates and percentages', exact: true }).nth(0).fill('01-15 25.00\n04-15 25.00\n07-15 25.00\n12-15 25.00');
    await page.getByRole('textbox', { name: 'Option code', exact: true }).nth(1).fill('annual');
    await page.getByRole('textbox', { name: 'Option name', exact: true }).nth(1).fill('Annual');
    await check(page); await submitOnceAndReplay(page, 'Save fee plan');
    await page.goto(`/fee-accounts/${data.account}/edit`);
    const choice = page.locator(`form[action$="/${data.account}/payment-choice"]`).first();
    await choice.getByRole('combobox', { name: 'Fee plan for payment choice', exact: true }).selectOption(String(data.plan));
    await choice.getByRole('combobox', { name: 'Payment option', exact: true }).selectOption('annual');
    await check(page);
    const choiceButton = choice.getByRole('button', { name: 'Save learner payment option', exact: true });
    const fields = await choice.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
    const action = await choice.getAttribute('action'); await choiceButton.click();
    await expect(page.getByRole('status')).toContainText('Payment option saved.');
    expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
    await page.goto('/fee-bill-runs/new');
    await page.getByRole('main').getByRole('combobox', { name: 'School year', exact: true }).selectOption(String(data.year));
    await page.getByRole('textbox', { name: 'Enrolment date for this bill run', exact: true }).fill(data.date);
    await page.getByRole('textbox', { name: 'Invoice due date', exact: true }).fill(data.due);
    await page.getByRole('checkbox', { name: 'I confirm no VAT is charged on these annual tuition fees.', exact: true }).check();
    await page.getByRole('button', { name: 'Prepare bill preview', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Bill preview prepared.');
    await expect(page.getByRole('main')).toContainText('ZAR 20.02');
    const schedules = page.locator('summary').filter({ hasText: 'Reviewed installment schedule' });
    await expect(schedules).toHaveCount(2); await schedules.nth(0).click(); await schedules.nth(1).click();
    await expect(page.getByRole('main')).toContainText(`${data.date.slice(0, 4)}-12-15`);
    await check(page);
    await page.getByRole('button', { name: 'Approve bill preview', exact: true }).click();
    await page.getByRole('checkbox', { name: 'I confirm these approved charges should be invoiced.', exact: true }).check();
    await submitOnceAndReplay(page, 'Post approved invoices');
    expect(fixture('stats', data.school)).toEqual({ invoices: 1, lines: 5, entries: 1, receivable_cents: 2002, income_cents: 2002, inactive_learners: 2, choices: 1, scheduled_lines: 5 });
    await page.getByRole('link', { name: /^BILL-/ }).click();
    await expect(page.getByRole('main')).toContainText(`${data.date.slice(0, 4)}-12-15`);
    await check(page); expect(errors).toEqual([]);
  });
}
test('Teacher cannot configure financial matching rules or payment choices', async ({ page }) => {
  const data = fixture(); await signIn(page, data.teacher);
  for (const url of [`/fee-plans/${data.plan}/edit`, `/fee-accounts/${data.account}/edit`]) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic scheduled family');
  }
});
