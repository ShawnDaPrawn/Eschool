import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });

for (const width of [375, 1280]) {
  test('school notification list and recipient chooser work independently at ' + width + 'px', async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 850 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    for (const path of ['/notifications', '/notifications?view=create']) {
      expect((await page.goto(path))?.status()).toBe(200);
      await page.waitForLoadState('networkidle');
      expect(await horizontalOverflow(page), path).toBeLessThanOrEqual(1);
      expect(await seriousViolations(page), path).toEqual([]);
    }
    let writes = 0;
    page.on('request', request => {
      if (request.method() === 'POST' && new URL(request.url()).pathname === '/notifications') writes++;
    });
    await page.locator('#create_notification [type="submit"]').click();
    await expect(page.locator('#create_notification')).toBeVisible();
    expect(writes).toBe(0);
    const recipients = page.waitForResponse(response => response.url().includes('recipient_type=students') && response.request().method() === 'GET');
    await page.locator('#send_to').selectOption('3');
    expect((await recipients).status()).toBe(200);
    await expect(page.locator('#tab-link-students')).toHaveAttribute('aria-selected', 'true');
    await expect(page.locator('#tab-students')).toBeVisible();
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(errors).toEqual([]);
  });
}
