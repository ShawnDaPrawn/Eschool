import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

// Uses an isolated, explicitly synthetic INSTREAM fixture for the scanner.
// It verifies the upload protocol and application flow, not malware detection.
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
test.beforeAll(() => execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/gallery-fixture.php');"], { cwd: path.resolve(process.cwd(), '..') }));
for (const width of [390, 820, 1280]) {
  test(`private group photo upload tag and withdrawal at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/school-gallery');
    await page.getByRole('combobox', { name: 'Available group', exact: true }).selectOption({ label: 'Synthetic gallery group' });
    await page.getByRole('button', { name: 'Open group', exact: true }).click();
    const caption = `Synthetic private gallery caption ${width}`;
    await page.getByRole('button', { name: 'Photograph', exact: true }).setInputFiles({ name: 'sensitive-device-name.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', 'base64') });
    await page.getByRole('textbox', { name: 'Date photographed', exact: true }).fill(new Date().toISOString().slice(0, 10));
    await page.getByLabel('Caption', { exact: true }).fill(caption);
    await page.getByRole('button', { name: 'Save private photograph', exact: true }).click();
    const card = page.locator('article').filter({ hasText: caption });
    await expect(card).toBeVisible();
    const image = card.getByRole('img', { name: 'Private group photograph', exact: true });
    const url = (await image.getAttribute('src'))!;
    const response = await page.request.get(url);
    expect(response.status()).toBe(200);
    expect(response.headers()['cache-control']).toContain('no-store');
    expect(response.headers()['content-type']).toContain('image/jpeg');
    expect((await response.body()).subarray(0, 2).toString('hex')).toBe('ffd8');
    await card.getByRole('button', { name: 'Open photograph', exact: true }).click();
    await expect(page.getByRole('dialog', { name: 'Private photograph', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Close photograph', exact: true }).click();
    await card.getByRole('textbox', { name: 'Approved learner handle', exact: true }).fill('@gallery.browser');
    await card.getByRole('button', { name: 'Propose tag', exact: true }).click();
    await expect(page.locator('article').filter({ hasText: caption })).toBeVisible();
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    await page.locator('article').filter({ hasText: caption }).getByRole('button', { name: 'Withdraw photograph', exact: true }).click();
    await expect(page.getByText(caption, { exact: true })).toHaveCount(0);
    expect((await page.request.get(url)).status()).toBe(404);
    expect(errors).toEqual([]);
  });
}
