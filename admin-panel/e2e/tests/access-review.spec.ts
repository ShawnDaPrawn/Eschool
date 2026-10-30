import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

// Who can see what (engineering/specs/platform/access-review.md).
test.use({ storageState: { cookies: [], origins: [] } });

for (const width of [1024, 1280]) {
  test(`access review of a role at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/access-review');
    const form = page.getByRole('form', { name: 'Review a role', exact: true });
    await form.getByRole('combobox', { name: 'Role' }).selectOption({ label: 'Teacher' });
    await form.getByRole('button', { name: 'Review role', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Teacher', exact: true })).toBeVisible();
    await expect(page.getByRole('table', { name: 'Access in each module' })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}

test('teachers cannot open the access review', async ({ page }) => {
  await signIn(page, accounts.teacher);
  expect((await page.goto('/access-review'))?.status()).toBe(403);
});
