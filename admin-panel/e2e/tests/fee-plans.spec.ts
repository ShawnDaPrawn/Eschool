import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Fixture = { year: number; next: number; date: string; learner: string };
function runFixture(action = 'prepare'): string {
  return execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-plans-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, FEE_PLAN_FIXTURE_ACTION: action },
  }).trim();
}
function fixture(): Fixture {
  return JSON.parse(runFixture());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
test.afterAll(() => {
  expect(JSON.parse(runFixture('cleanup')).active_fixture_years).toBe(0);
});
for (const width of [390, 820, 1280]) {
  test(`finance creates, previews and copies an annual fee plan at ${width}px`, async ({ page, baseURL }) => {
    const data = fixture();
    const name = `Synthetic annual tuition ${width}-${Date.now()}`;
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.finance);
    const response = await page.goto('/fee-plans');
    expect(response!.status()).toBe(200);
    expect(response!.headers()['cache-control']).toContain('no-store');
    await page.getByRole('link', { name: 'New fee plan', exact: true }).click();
    await page.getByRole('textbox', { name: 'Plan name', exact: true }).fill(name);
    await page.getByRole('main').getByRole('combobox', { name: 'School year', exact: true }).selectOption(String(data.year));
    await page.getByRole('textbox', { name: 'Annual amount', exact: true }).fill('10.01');
    await page.getByRole('textbox', { name: 'Currency code', exact: true }).fill('ZAR');
    await page.getByRole('combobox', { name: 'Applies to', exact: true }).selectOption('all:0');
    await page.getByRole('combobox', { name: 'Status', exact: true }).selectOption('active');
    await check(page);
    await page.getByRole('button', { name: 'Save fee plan', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Fee plan saved.');
    await page.getByRole('combobox', { name: 'Target school year', exact: true }).selectOption(String(data.next));
    await page.getByRole('textbox', { name: 'Copied plan name', exact: true }).fill(`${name} next year`);
    await page.getByRole('textbox', { name: 'Uplift percentage', exact: true }).fill('5.25');
    await check(page);
    await page.getByRole('button', { name: 'Create draft copy', exact: true }).click();
    await expect(page.getByRole('textbox', { name: 'Annual amount', exact: true })).toHaveValue('10.54');
    await expect(page.getByRole('combobox', { name: 'Status', exact: true })).toHaveValue('draft');
    await page.goto(`/fee-plans?year_id=${data.year}`);
    await page.getByRole('textbox', { name: 'Preview date', exact: true }).fill(data.date);
    await page.getByRole('button', { name: 'Preview enrolment matches', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Preview enrolment matches', exact: true })).toBeVisible();
    await expect(page.getByRole('table')).toContainText(data.learner);
    await expect(page.getByRole('table')).toContainText(name);
    await expect(page.getByRole('table')).toContainText('ZAR 10.01');
    await check(page);
    expect(errors).toEqual([]);
  });
}

test('ordinary teacher cannot open finance planning', async ({ page }) => {
  await signIn(page, accounts.teacher);
  const response = await page.goto('/fee-plans');
  expect(response!.status()).toBe(403);
  await expect(page.locator('body')).not.toContainText('Synthetic annual tuition');
});
