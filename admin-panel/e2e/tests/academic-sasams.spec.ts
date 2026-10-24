import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let profile: Record<string, unknown>;
test.beforeAll(() => {
  const output = execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/sasams-fixture.php');"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
  });
  profile = JSON.parse(output.slice(output.indexOf('{'), output.lastIndexOf('}') + 1));
});

for (const width of [1024, 1280]) {
  test(`private versioned learner export at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-sasams');
    await expect(page.getByRole('heading', { name: 'SA-SAMS exports', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const form = page.getByRole('form', { name: 'Prepare private SA-SAMS export', exact: true });
    const upload = form.getByLabel('Versioned JSON layout', { exact: false });
    await upload.setInputFiles({ name: 'synthetic-layout.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify(profile)) });
    const pending = page.waitForEvent('download');
    await form.getByRole('button', { name: 'Download private export and manifest', exact: true }).click();
    const download = await pending;
    expect(download.suggestedFilename()).toBe('sasams-export.zip');
    expect(await download.failure()).toBeNull();
    expect(errors).toEqual([]);
  });
}

test('another school manager cannot use the first schools layout', async ({ page }) => {
  await signIn(page, accounts.adminB);
  await page.goto('/academic-sasams');
  const form = page.getByRole('form', { name: 'Prepare private SA-SAMS export', exact: true });
  await form.getByLabel('Versioned JSON layout', { exact: false }).setInputFiles({
    name: 'wrong-school.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify(profile)),
  });
  const response = page.waitForResponse(r => r.url().endsWith('/academic-sasams/download') && r.request().method() === 'POST');
  await form.getByRole('button', { name: 'Download private export and manifest', exact: true }).click();
  expect((await response).status()).toBe(404);
});
