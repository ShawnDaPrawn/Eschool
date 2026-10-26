import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(): { group: number; material: number } {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/activity-material-files-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
  }).trim());
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`private group document upload, authenticated download and removal at ${width}px`, async ({ page, baseURL }) => {
    const setup = fixture();
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.admin);
    const url = `/activity-material-files/groups/${setup.group}/materials/${setup.material}`;
    const first = await page.goto(url);
    expect(first!.status()).toBe(200);
    expect(first!.headers()['cache-control']).toContain('no-store');
    expect(first!.headers()['referrer-policy']).toBe('no-referrer');
    const bytes = Buffer.from('%PDF-1.4\nSynthetic private worksheet\n');
    const input = page.locator('#activity-material-document');
    await expect(input).toHaveAttribute('type', 'file');
    await expect(page.getByRole('button', { name: 'Private document', exact: true })).toBeVisible();
    await input.setInputFiles({ name: 'Synthetic worksheet.pdf', mimeType: 'application/pdf', buffer: bytes });
    await page.getByRole('button', { name: 'Upload document', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Synthetic worksheet.pdf', exact: true })).toBeVisible();
    const download = page.getByRole('link', { name: 'Download document', exact: true });
    const content = await page.request.get((await download.getAttribute('href'))!);
    expect(content.status()).toBe(200);
    expect(content.headers()['cache-control']).toContain('no-store');
    expect(content.headers()['referrer-policy']).toBe('no-referrer');
    expect(await content.body()).toEqual(bytes);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
    await page.getByText('Remove document', { exact: true }).click();
    await page.getByRole('button', { name: 'Confirm document removal', exact: true }).click();
    await expect(page.getByText('No documents have been attached.', { exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
