import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, csrfToken, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });

test.beforeAll(() => {
  execFileSync('php', ['artisan', 'academics:import-subject-standards', 'database/data/dbe-ncs-2015-subject-standards.json', '--publish'], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});

test('admin browses the sourced catalogue and maps or creates a school subject', async ({ page, baseURL }) => {
  await signIn(page, accounts.adminB);
  const errors = collectErrors(page, baseURL!);
  await page.goto('/subject-standards?grade=7&q=Mathematics');
  await expect(page.getByRole('heading', { level: 1, name: 'Subject standards' })).toBeVisible();
  await expect(page.locator('[data-standard-id]')).toHaveCount(1);
  const standard = page.locator('[data-standard-id]').first();
  const id = (await standard.getAttribute('data-standard-id'))!;
  await expect(standard).toContainText('19331432');
  await expect(standard).toContainText('DBE-NCS-2015');
  for (const width of [1024, 1280]) {
    await page.setViewportSize({ width, height: 800 });
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  }
  const mapping = page.locator('select[id^="subject-standard-"]').first();
  await mapping.selectOption(id);
  await mapping.locator('xpath=ancestor::form').getByRole('button', { name: 'Save subject reference' }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Subject standard saved' })).toBeVisible();
  await expect(page.locator('select[id^="subject-standard-"]').first()).toHaveValue(id);
  await page.goto('/subject-standards?grade=7&q=Mathematics');
  await page.locator('[data-standard-id]').first().getByText('Create school subject: Mathematics', { exact: true }).click();
  await page.locator(`#standard-colour-${id}`).fill('#22577a');
  await page.locator('[data-standard-id]').first().getByRole('button', { name: 'Create school subject', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'School subject created' })).toBeVisible();
  expect(errors).toEqual([]);
});

test('teacher reads reference metadata but is refused school subject management', async ({ page, baseURL }) => {
  await signIn(page, accounts.teacher);
  const csrf = await csrfToken(page);
  expect((await page.goto('/subject-standards'))?.status()).toBe(403);
  const catalogue = await page.request.get('/api/v2/academics/subject-standards?grade=7', { headers: { Origin: baseURL! } });
  expect(catalogue.status()).toBe(200);
  expect((await catalogue.json()).data.total).toBeGreaterThan(0);
  expect((await page.request.post('/api/v2/academics/subjects/from-standard', { headers: { Origin: baseURL!, 'X-CSRF-TOKEN': csrf }, data: {} })).status()).toBe(403);
});
