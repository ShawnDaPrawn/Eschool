import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(): {thread: string; text: string} {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/assistance-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: process.env,
  }).trim());
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`office claim, original messages and approved source at ${width}px`, async ({ page, baseURL }) => {
    const setup = fixture();
    const errors = collectErrors(page, baseURL!);
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, accounts.admin);
    await page.goto('/ask-school');
    await expect(page.getByText(setup.text, { exact: true })).toHaveCount(0);
    const card = page.locator(`[data-assistance-thread="${setup.thread}"]`);
    await card.getByRole('button', { name: 'Claim request', exact: true }).click();
    await expect(page).toHaveURL(new RegExp(`/ask-school/threads/${setup.thread}$`));
    await expect(page.getByText(setup.text, { exact: true })).toBeVisible();
    await page.getByLabel('Your reply', { exact: true }).fill('Synthetic office reply <script>notExecutable()</script>');
    await page.getByRole('button', { name: 'Send reply', exact: true }).click();
    await expect(page.getByText('Synthetic office reply <script>notExecutable()</script>', { exact: true })).toBeVisible();
    const parentMessage = page.locator('article').filter({ has: page.getByText(setup.text, { exact: true }) });
    await parentMessage.getByRole('button', { name: 'Review message translation', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Review before sharing', exact: true })).toBeVisible();
    await expect(page.getByText(setup.text, { exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Request reviewed translation', exact: true })).toBeDisabled();
    await page.getByRole('link', { name: 'Close private review', exact: true }).click();
    const title = `Synthetic approved source ${width} ${crypto.randomUUID().slice(0, 8)}`;
    await page.getByLabel('Document title', { exact: true }).fill(title);
    await page.getByLabel('Approved school text', { exact: true }).fill('The school office opens at 08:00.');
    await page.getByLabel('Document source or maintenance reference', { exact: true }).fill('Synthetic school handbook');
    await page.getByLabel('I approve this exact school document and reviewed identifying details', { exact: true }).check();
    await page.getByRole('button', { name: 'Publish approved document', exact: true }).click();
    await expect(page.getByRole('heading', { name: title, exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Withdraw this document version', exact: true }).click();
    await expect(page.getByText('withdrawn', { exact: false }).first()).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
