import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
test.beforeAll(() => execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/ai-review-fixture.php');"], { cwd: path.resolve(process.cwd(), '..') }));
for (const width of [390, 820, 1280]) {
  test(`safeguarding reads and reviews withheld content privately at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, 'safeguarding.a@demo.navigateed.test');
    const errors = collectErrors(page, baseURL!);
    await page.goto('/ai-flags');
    await page.getByRole('link', { name: /^Review flag \d+$/ }).first().click();
    await expect(page.getByText('Synthetic learning disclosure <script>providerUnsafe()</script>', { exact: true })).toBeVisible();
    expect(await page.evaluate(() => typeof (window as unknown as Record<string, unknown>).providerUnsafe)).toBe('undefined');
    const form = page.getByRole('form', { name: 'Record safeguarding review', exact: true });
    await form.getByRole('combobox', { name: 'Review decision', exact: true }).selectOption('escalated');
    const reason = `Synthetic private follow-up ${width}`;
    await form.getByRole('textbox', { name: 'Private review note', exact: true }).fill(reason);
    await form.getByRole('button', { name: 'Record review', exact: true }).click();
    await expect(page.locator('article').filter({ hasText: reason })).toBeVisible();
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(errors).toEqual([]);
    await page.evaluate(() => {
      Object.defineProperty(document, 'hidden', { configurable: true, value: true });
      document.dispatchEvent(new Event('visibilitychange'));
    });
    await expect(page.locator('[data-ai-private-review]')).toBeEmpty();
  });
}
