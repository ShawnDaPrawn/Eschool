import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

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

// The role editor by module and level (engineering/specs/platform/role-editor.md).
for (const width of [1024, 1280]) {
  test(`role editor by module at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/roles-by-module/new');
    const form = page.getByRole('form', { name: 'Role access by module', exact: true });
    await expect(form).toBeVisible();
    await expect(form.getByRole('textbox', { name: 'Role name' })).toBeVisible();
    await expect(form.getByRole('radio', { name: 'People and guardian approval: View', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}

test('teachers cannot open the role editor', async ({ page }) => {
  await signIn(page, accounts.teacher);
  expect((await page.goto('/roles-by-module/new'))?.status()).toBe(403);
});
