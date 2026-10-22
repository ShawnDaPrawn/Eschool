import { expect, test } from '@playwright/test';

/**
 * Global search (a Phase 0 UX acceptance path): typing in the search box opens
 * a learner, parent, teacher, class or settings page within ten seconds.
 */
test.use({ storageState: '.auth/admin-flows.json' });

test('"/" opens the search box and a learner opens from it', async ({ page }) => {
  await page.goto('/home');
  const started = Date.now();
  await page.keyboard.press('/');
  const box = page.getByRole('combobox', { name: 'Search the panel' });
  await expect(box).toBeFocused();

  await page.keyboard.type('Lerato');
  await expect(page.getByRole('option', { name: /Lerato Dlamini/ })).toBeVisible();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');

  await expect(page).toHaveURL(/\/student-report\/\d+/);
  await expect(page.getByText('Lerato').first()).toBeVisible();
  expect(Date.now() - started).toBeLessThan(10_000);
});

test('Ctrl+K and the results page open a settings page', async ({ page }) => {
  await page.goto('/announcement');
  await page.keyboard.press('Control+k');
  const box = page.getByRole('combobox', { name: 'Search the panel' });
  await expect(box).toBeFocused();

  await box.fill('school profile');
  await box.press('Enter');
  await expect(page).toHaveURL(/\/search\?q=school\+profile/);
  await expect(page.getByRole('heading', { name: 'Pages and settings' })).toBeVisible();
  await page.getByRole('link', { name: /School profile and settings/ }).click();
  await expect(page).toHaveURL(/\/settings$/);
});

test('a parent, a teacher and a homeroom are found too', async ({ page }) => {
  await page.goto('/home');
  const box = page.getByRole('combobox', { name: 'Search the panel' });
  for (const [term, result] of [
    ['Sipho', /Sipho Dlamini/],
    ['Thandi', /Thandi Mokoena/],
    ['Grade 5', /Grade 5 A/],
  ] as const) {
    await box.fill(term);
    await expect(page.getByRole('option', { name: result })).toBeVisible();
  }
});
