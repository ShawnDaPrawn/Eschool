import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/vendor-catalogues-fixture.php'));"], {
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
  test(`Finance configures vendor products and counted stock at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    const response = await page.goto('/vendor-catalogues');
    expect(response!.headers()['cache-control']).toContain('no-store');
    let form = page.getByRole('form', { name: 'Save vendor', exact: true });
    await form.getByRole('textbox', { name: 'Vendor name', exact: true }).fill('Synthetic independent tuckshop');
    await form.getByRole('textbox', { name: 'Legal trading name', exact: true }).fill('Synthetic independent merchant');
    await form.getByRole('combobox', { name: 'Vendor ownership', exact: true }).selectOption('independent');
    await form.getByRole('combobox', { name: 'Vendor status', exact: true }).selectOption('active');
    await form.getByRole('combobox', { name: 'Vendor VAT registration', exact: true }).selectOption('0');
    await form.getByRole('combobox', { name: 'Who bears refunds', exact: true }).selectOption('vendor');
    await form.getByRole('combobox', { name: 'Who bears chargebacks', exact: true }).selectOption('school');
    await form.getByRole('combobox', { name: 'Settlement method', exact: true }).selectOption('eft');
    await form.getByRole('combobox', { name: 'Settlement frequency', exact: true }).selectOption('weekly');
    await form.getByRole('spinbutton', { name: 'Settlement delay in days', exact: true }).fill('2');
    await form.getByRole('textbox', { name: 'Agreed settlement terms', exact: true }).fill('Reviewed synthetic settlement agreement for completed orders.');
    await form.getByRole('textbox', { name: 'Private vendor bank instructions', exact: true }).fill('Private synthetic original merchant bank details');
    await check(page);
    await form.getByRole('button', { name: 'Save vendor', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Vendor catalogue change saved.');
    await expect(page.getByRole('textbox', { name: 'Private vendor bank instructions', exact: true })).toHaveValue('');
    form = page.getByRole('form', { name: 'Save vendor product', exact: true });
    await form.getByRole('textbox', { name: 'Permanent product SKU', exact: true }).fill('SYNTHETIC-SANDWICH');
    await form.getByRole('textbox', { name: 'Product name', exact: true }).fill('Synthetic sandwich');
    await form.getByRole('textbox', { name: 'Unit label', exact: true }).fill('each');
    await form.getByRole('textbox', { name: 'Price including VAT', exact: true }).fill('12.50');
    await form.getByRole('spinbutton', { name: 'VAT rate in basis points; zero unless standard rated', exact: true }).fill('0');
    await form.getByRole('combobox', { name: 'Permanent stock mode', exact: true }).selectOption('1');
    await form.getByRole('combobox', { name: 'Product status', exact: true }).selectOption('active');
    await form.getByRole('button', { name: 'Save vendor product', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Units on hand: 0');
    form = page.getByRole('form', { name: 'Record stock adjustment', exact: true });
    await form.getByRole('textbox', { name: 'Unique physical stock reference', exact: true }).fill('SYNTHETIC-COUNTED-DELIVERY');
    await form.getByRole('spinbutton', { name: 'Unit change; negative to remove', exact: true }).fill('10');
    await form.getByRole('textbox', { name: 'Private stock adjustment reason', exact: true }).fill('Synthetic physical stock counted against supplier delivery.');
    const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
    const action = await form.getAttribute('action');
    await check(page);
    await form.getByRole('button', { name: 'Record stock adjustment', exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Units on hand: 10');
    expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
    expect(fixture('stats', data.school)).toMatchObject({ vendors: 1, products: 1, movements: 1, on_hand: 10, entries: 0, healthy: true });
    await check(page);
    fixture('disable', data.school);
    await page.reload();
    await expect(page.getByRole('status')).toContainText('Vendor history is read-only');
    await expect(page.getByRole('button', { name: 'Record stock adjustment', exact: true })).toBeDisabled();
    expect(errors).toEqual([]);
  });
}
test('Teacher cannot open Finance vendor and stock controls', async ({ page }) => {
  const data = fixture();
  await signIn(page, data.teacher);
  expect((await page.goto('/vendor-catalogues'))?.status()).toBe(403);
});


test('Scoped CSP runs trusted workbench scripts and blocks injected inline code', async ({ page }) => {
  const data = fixture();
  await page.addInitScript(() => {
    (window as any).__cspViolations = [];
    document.addEventListener('securitypolicyviolation', event => {
      (window as any).__cspViolations.push(event.effectiveDirective);
    });
  });
  await signIn(page, data.finance);
  const response = await page.goto('/vendor-catalogues');
  expect(response!.status()).toBe(200);
  const scriptPolicy = response!.headers()['content-security-policy'].split(';').find(value => value.trim().startsWith('script-src'))!;
  expect(scriptPolicy).toContain("'nonce-");
  expect(scriptPolicy).not.toContain("'unsafe-inline'");
  expect(await page.evaluate(() => Boolean((window as any).panelShell))).toBe(true);
  await page.evaluate(() => {
    (window as any).__injectedScriptRan = false;
    const script = document.createElement('script');
    script.textContent = 'window.__injectedScriptRan = true';
    document.body.append(script);
  });
  await expect.poll(() => page.evaluate(() => (window as any).__cspViolations)).toContain('script-src-elem');
  expect(await page.evaluate(() => (window as any).__injectedScriptRan)).toBe(false);
});
