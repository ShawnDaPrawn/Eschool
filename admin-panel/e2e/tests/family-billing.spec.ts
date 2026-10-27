import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Fixture = { school: number; year: number; date: string; due: string; finance: string; teacher: string; parents: number[]; learners: { id: number; name: string }[] };
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/family-billing-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`finance creates a split family, approves and posts invoices once at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    const data: Fixture = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/fee-accounts/new');
    expect(response!.status()).toBe(200);
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('textbox', { name: 'Account name', exact: true }).fill(`Synthetic family ${width}`);
    await page.getByRole('listbox', { name: 'Learners on this account', exact: true }).selectOption(data.learners.map(l => String(l.id)));
    await page.getByRole('combobox', { name: 'Account holder 1', exact: true }).selectOption(String(data.parents[0]));
    await page.getByRole('combobox', { name: 'Account holder 2', exact: true }).selectOption(String(data.parents[1]));
    await page.getByRole('textbox', { name: 'Holder share percentage 1', exact: true }).fill('50');
    await page.getByRole('textbox', { name: 'Holder share percentage 2', exact: true }).fill('50');
    await check(page);
    await page.getByRole('button', { name: 'Save active family account', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Family account saved.');
    await check(page);
    await page.goto('/fee-bill-runs/new');
    await page.getByRole('main').getByRole('combobox', { name: 'School year', exact: true }).selectOption(String(data.year));
    await page.getByRole('textbox', { name: 'Enrolment date for this bill run', exact: true }).fill(data.date);
    await page.getByRole('textbox', { name: 'Invoice due date', exact: true }).fill(data.due);
    await page.getByRole('checkbox', { name: 'I confirm no VAT is charged on these annual tuition fees.', exact: true }).check();
    await check(page);
    await page.getByRole('button', { name: 'Prepare bill preview', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Bill preview prepared.');
    await expect(page.getByRole('main')).toContainText('ZAR 20.02');
    for (const learner of data.learners) await expect(page.getByRole('table')).toContainText(learner.name);
    expect(fixture('stats', data.school).invoices).toBe(0);
    await check(page);
    await page.getByRole('button', { name: 'Approve bill preview', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Bill run approved.');
    expect(fixture('stats', data.school).invoices).toBe(0);
    await page.getByRole('checkbox', { name: 'I confirm these approved charges should be invoiced.', exact: true }).check();
    const postButton = page.getByRole('button', { name: 'Post approved invoices', exact: true });
    const form = postButton.locator('xpath=ancestor::form');
    const postData = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
    const postUrl = await form.getAttribute('action');
    await check(page);
    await postButton.click();
    await expect(page.getByRole('status')).toContainText('Invoices posted once.');
    await check(page);
    const repeated = await page.request.post(postUrl!, { form: postData });
    expect(repeated.status()).toBe(200);
    expect(fixture('stats', data.school)).toEqual({ invoices: 1, lines: 2, entries: 1, receivable_cents: 2002, income_cents: 2002, inactive_learners: 2 });
    await page.getByRole('link', { name: /^BILL-/ }).click();
    await expect(page.getByRole('main')).toContainText('ZAR 20.02');
    await expect(page.getByRole('heading', { name: 'Recorded holder shares', exact: true })).toBeVisible();
    for (const learner of data.learners) await expect(page.getByRole('table')).toContainText(learner.name);
    await check(page);
    expect(errors).toEqual([]);
  });
}

test('ordinary Teacher cannot open family account or bill-run screens', async ({ page }) => {
  const data: Fixture = fixture();
  await signIn(page, data.teacher);
  for (const url of ['/fee-accounts', '/fee-bill-runs', '/fee-invoices/1']) {
    expect((await page.goto(url))!.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('Synthetic family');
  }
});
