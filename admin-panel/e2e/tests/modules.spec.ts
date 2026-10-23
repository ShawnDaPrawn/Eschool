import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';

test.use({ storageState: { cookies: [], origins: [] } });

test('school admin can switch messages off and on while required features stay on', async ({ page }) => {
  await signIn(page, accounts.adminB);
  await page.goto('/school-modules');
  const row = page.getByRole('row').filter({ hasText: 'Direct messages' });
  try {
    await row.getByRole('button', { name: 'Switch off Direct messages', exact: true }).click();
    await expect(row.getByRole('button', { name: 'Enable Direct messages', exact: true })).toBeVisible();
    await expect(page.getByRole('row').filter({ hasText: 'People and guardian approval' })).toContainText('Required');
    const blocked = await page.request.get('/chat-settings');
    expect(blocked.status()).toBe(404);
  } finally {
    await page.goto('/school-modules');
    const enable = row.getByRole('button', { name: 'Enable Direct messages', exact: true });
    if (await enable.count()) await enable.click();
  }
  await expect(row.getByRole('button', { name: 'Switch off Direct messages', exact: true })).toBeVisible();
  expect((await page.request.get('/chat-settings')).status()).toBe(200);
});
