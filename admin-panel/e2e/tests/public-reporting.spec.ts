import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let reportPath: string;
test.beforeAll(() => {
  const fixture = JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/public-reporting-fixture.php'));"], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' }));
  reportPath = `/school/${fixture.slug}/report`;
});
for (const width of [390, 1280]) {
  test(`anonymous reporting and private code follow-up at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    const paths: string[] = [];
    page.on('request', request => paths.push(request.url()));
    const response = await page.goto(reportPath);
    expect(response?.status()).toBe(200);
    expect(response?.headers()['cache-control']).toBe('no-store, private');
    expect(response?.headers()['referrer-policy']).toBe('no-referrer');
    await expect(page.getByRole('heading', { name: 'If you are unsafe', exact: true })).toBeVisible();
    const code = await page.getByLabel('Save your private code before sending', { exact: true }).inputValue();
    expect(code).toMatch(/^[a-f0-9]{64}$/);
    const body = `Synthetic anonymous concern ${width} ${Date.now()}`;
    await page.getByLabel('Report category', { exact: true }).selectOption({ label: 'Browser safety concern' });
    await page.getByLabel('What happened?', { exact: true }).fill(body);
    await page.getByRole('button', { name: 'Send anonymous report', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Report received.');
    await expect(page.getByText(body, { exact: true })).toBeVisible();
    await page.getByLabel('Add a follow-up', { exact: true }).fill('Synthetic private follow-up from browser.');
    await page.getByRole('button', { name: 'Send follow-up', exact: true }).click();
    await expect(page.getByText('Synthetic private follow-up from browser.', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Your private code', { exact: true })).toHaveValue(code);
    expect(await page.context().cookies()).toEqual([]);
    expect(await page.evaluate(() => ({ local: localStorage.length, session: sessionStorage.length }))).toEqual({ local: 0, session: 0 });
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.length).toBe(new Set(ids).size);
    expect(paths.every(url => new URL(url).origin === new URL(baseURL!).origin)).toBe(true);
    expect(paths.every(url => !url.includes(code))).toBe(true);
    await page.getByRole('link', { name: 'Start another report', exact: true }).click();
    await page.getByLabel('Enter your private code', { exact: true }).fill(code);
    await page.getByRole('button', { name: 'Read school replies', exact: true }).click();
    await expect(page.getByText(body, { exact: true })).toBeVisible();
    expect(await page.context().cookies()).toEqual([]);
  });
}
