import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/vendor-orders-fixture.php'));"], {
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
  test(`Finance validates typed and QR orders once and refunds original uncollected goods at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    const data = fixture();
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, data.finance);
    const errors = collectErrors(page, baseURL!);
    for (const [index, field] of [[0, 'Order number'], [1, 'Scanned wallet order QR']] as const) {
      const response = await page.goto(`/vendor-orders?vendor_id=${data.vendor}`);
      expect(response!.headers()['cache-control']).toContain('no-store');
      const form = page.getByRole('form', { name: 'Validate order collection', exact: true });
      await form.getByRole('textbox', { name: field, exact: true }).fill(index === 0 ? data.orders[index].number : data.orders[index].qr);
      await form.getByRole('checkbox').check();
      const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
      const action = (await form.getAttribute('action'))!;
      await check(page);
      await form.getByRole('button', { name: 'Validate order collection', exact: true }).click();
      await expect(page.getByRole('heading', { name: data.orders[index].number, exact: true })).toBeVisible();
      await expect(page.getByRole('status')).toContainText('Order validated for one collection.');
      await expect(page.getByRole('main')).toContainText('Synthetic sandwich');
      await expect(page.getByRole('button', { name: 'Validate this order collection', exact: true })).toHaveCount(0);
      expect((await page.request.post(action, { form: fields })).status()).toBe(200);
      expect((await page.request.post(action, { form: { ...fields, operation_id: crypto.randomUUID() } })).status()).toBe(409);
      await check(page);
    }
    await page.goto(`/vendor-orders/${data.orders[2].id}`);
    const cancel = page.getByRole('form', { name: 'Cancel uncollected original order', exact: true });
    await cancel.getByRole('textbox', { name: 'Private cancellation reason', exact: true }).fill('Synthetic goods confirmed not handed over.');
    await cancel.getByRole('checkbox').nth(0).check();
    await cancel.getByRole('checkbox').nth(1).check();
    await cancel.getByRole('button', { name: 'Cancel uncollected original order', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Money returned only to the original wallet.');
    await expect(page.getByRole('button', { name: 'Cancel uncollected original order', exact: true })).toHaveCount(0);
    expect(fixture('stats', data.school)).toEqual({ orders: 4, collected: 2, cancelled: 1, wallet_cents: 6250, vendor_cents: 3750, on_hand: 7, healthy: true });
    fixture('disable', data.school);
    await page.goto(`/vendor-orders?vendor_id=${data.vendor}`);
    await expect(page.getByRole('status')).toContainText('history is read-only');
    await expect(page.getByRole('button', { name: 'Validate order collection', exact: true })).toBeDisabled();
    await page.getByRole('link', { name: data.orders[3].number, exact: true }).click();
    await expect(page.getByRole('main')).toContainText('Synthetic sandwich');
    await expect(page.getByRole('button', { name: 'Validate this order collection', exact: true })).toHaveCount(0);
    await check(page);
    expect(errors).toEqual([]);
  });
}
test('Teacher cannot open Finance order collection or original receipts', async ({ page }) => {
  const data = fixture();
  await signIn(page, data.teacher);
  expect((await page.goto('/vendor-orders'))?.status()).toBe(403);
  expect((await page.goto(`/vendor-orders/${data.orders[0].id}`))?.status()).toBe(403);
});
