import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/finance-ui-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, SCHOOL_FINANCE_UI_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, i, all) => all.indexOf(id) !== i))).toEqual([]);
}
test.describe.configure({ mode: 'serial', retries: 0 });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 1280]) {
  test(`School Finance reviews private bursaries, giving, sponsorship and issued subscription at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(240_000);
    const data = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/finance/giving?section=create');
    await page.getByRole('textbox', { name: 'Campaign name', exact: true }).fill('Synthetic browser giving');
    await page.getByRole('textbox', { name: 'Fundraising goal', exact: true }).fill('500.01');
    await page.locator('[name="starts_on"]').fill(data.today);
    await page.locator('[name="ends_on"]').fill(data.through);
    await check(page);
    await Promise.all([page.waitForURL(/\/finance\/giving\/\d+\?section=overview$/, { waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Open campaign', exact: true }).click()]);
    await expect(page.getByRole('heading', { name: 'Campaign overview', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Record pledge', exact: true }).click();
    await page.getByRole('textbox', { name: 'Donor name', exact: true }).fill('Private synthetic donor');
    await page.getByRole('textbox', { name: 'Amount (ZAR)', exact: true }).fill('200.00');
    await page.locator('[name="pledged_on"]').fill(data.today);
    await Promise.all([page.waitForURL(/section=donors$/, { waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Save pledge acknowledgement', exact: true }).click()]);
    await expect(page.getByRole('table', { name: 'Pledges', exact: true })).toContainText('Private synthetic donor');
    await page.getByRole('link', { name: 'Record verified receipt', exact: true }).click();
    await page.getByRole('textbox', { name: 'Donor name', exact: true }).fill('Private synthetic donor');
    await page.getByRole('textbox', { name: 'Amount (ZAR)', exact: true }).fill('90.12');
    await page.locator('[name="received_on"]').fill(data.today);
    await page.getByRole('textbox', { name: 'Unique bank transaction reference', exact: true }).fill(`UI-GIFT-${data.school}`);
    await page.getByRole('checkbox').check();
    const form = page.locator('#giving-receipt');
    const replay = await form.evaluate(node => ({ url: (node as HTMLFormElement).action, fields: Object.fromEntries(new FormData(node as HTMLFormElement).entries()) }));
    await Promise.all([page.waitForURL(/\/finance\/giving\/receipts\/\d+$/, { waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Post verified donation once', exact: true }).click()]);
    await expect(page.getByRole('main')).toContainText('ZAR 90.12');
    expect((await page.request.post(replay.url, { form: replay.fields as Record<string, string> })).ok()).toBeTruthy();
    await check(page);
    await page.goto(`/finance/bursaries/${data.application}?section=decline`);
    await page.getByRole('textbox', { name: 'Private decision reasons', exact: true }).fill('Reviewed synthetic application did not satisfy the published criteria.');
    await Promise.all([page.waitForURL(/section=history$/, { waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Record committee decision', exact: true }).click()]);
    await expect(page.getByRole('heading', { name: 'Retained decision history', exact: true })).toBeVisible();
    await expect(page.getByRole('main')).toContainText('Reviewed synthetic application');
    await check(page);
    await page.goto(`/finance/sponsored-places/${data.sponsor}?section=assign`);
    await page.getByRole('combobox', { name: 'Learner', exact: true }).selectOption(String(data.learners[0].id));
    await Promise.all([page.waitForURL(new RegExp(`/finance/sponsored-places/${data.sponsor}$`), { waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Assign learner', exact: true }).click()]);
    await expect(page.getByRole('table', { name: 'Assigned learners', exact: true })).toContainText(data.learners[0].name);
    await page.getByRole('link', { name: 'Review release', exact: true }).click();
    await page.getByRole('checkbox').check();
    await Promise.all([page.waitForURL(new RegExp(`/finance/sponsored-places/${data.sponsor}$`), { waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Release this learner’s place', exact: true }).click()]);
    await expect(page.getByRole('main')).toContainText('No learners assigned yet.');
    await page.goto(`/finance/subscription-invoices/${data.subscription_invoice}`);
    await expect(page.getByRole('main')).toContainText('Issued synthetic subscription package');
    await expect(page.getByRole('main')).toContainText('ZAR 115.00');
    await expect(page.getByRole('main')).not.toContainText('PRIVATE HQ DIAGNOSTIC');
    await check(page);
    await page.screenshot({ path: test.info().outputPath('school-subscription-invoice.png'), fullPage: true });
    expect(fixture('stats', data.school)).toEqual({ donations: 1, donated_cents: 9012, pledged_cents: 20000, sponsored_learners: 0, bursary_state: 'declined', healthy: true });
    expect(errors).toEqual([]);
  });
}
