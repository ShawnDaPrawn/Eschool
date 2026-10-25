import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { horizontalOverflow, seriousViolations } from '../support/checks';

// Run with the isolated synthetic scanner documented in incident-photos-http.md.
// This proves HTTP/browser handling, not malware-engine qualification.
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let reportPath: string;
test.beforeAll(() => {
  const fixture = JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/public-reporting-fixture.php'));"], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' }));
  reportPath = `/school/${fixture.slug}/report`;
});
for (const width of [390, 1280]) {
  test(`private sanitized photo upload and download at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    const paths: string[] = [];
    page.on('request', request => paths.push(request.url()));
    await page.goto(reportPath);
    const secret = await page.getByLabel('Save your private code before sending', { exact: true }).inputValue();
    await page.getByLabel('Report category', { exact: true }).selectOption({ label: 'Browser safety concern' });
    await page.getByLabel('What happened?', { exact: true }).fill(`Synthetic photo concern ${width} ${Date.now()}`);
    await page.getByRole('button', { name: 'Send anonymous report', exact: true }).click();
    const pixels = await page.evaluate(() => {
      const canvas = document.createElement('canvas');
      canvas.width = 80; canvas.height = 40;
      const context = canvas.getContext('2d')!;
      context.fillStyle = '#304b80'; context.fillRect(0, 0, 80, 40);
      return canvas.toDataURL('image/jpeg').split(',')[1];
    });
    await page.getByLabel('Add a report photo', { exact: true }).setInputFiles({ name: 'synthetic-device-name.jpg', mimeType: 'image/jpeg', buffer: Buffer.from(pixels, 'base64') });
    const uploaded = page.waitForResponse(response => response.request().method() === 'POST' && response.url().endsWith('/photos'));
    await page.getByRole('button', { name: 'Send photo', exact: true }).click();
    expect((await uploaded).status()).toBe(200);
    await expect(page.getByRole('status')).toContainText('Your photo was received.');
    const image = page.getByRole('img', { name: 'Photo attached to this report', exact: true });
    await expect(image).toBeVisible();
    expect(await image.evaluate((element: HTMLImageElement) => ({ width: element.naturalWidth, height: element.naturalHeight }))).toEqual({ width: 80, height: 40 });
    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Download report photo', exact: true }).click();
    expect((await download).suggestedFilename()).toBe('report-photo.jpg');
    await expect(page.getByLabel('Your private code', { exact: true })).toHaveValue(secret);
    expect(await page.context().cookies()).toEqual([]);
    expect(await page.evaluate(() => ({ local: localStorage.length, session: sessionStorage.length }))).toEqual({ local: 0, session: 0 });
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.length).toBe(new Set(ids).size);
    expect(paths.every(url => !url.includes(secret))).toBe(true);
    expect(paths.filter(url => !url.startsWith('data:')).every(url => new URL(url).origin === new URL(baseURL!).origin)).toBe(true);
  });
}
