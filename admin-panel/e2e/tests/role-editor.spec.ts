import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

// The role editor by module and level (engineering/specs/platform/role-editor.md).
test.use({ storageState: { cookies: [], origins: [] } });

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
