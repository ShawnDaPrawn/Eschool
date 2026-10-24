import { expect, test } from '@playwright/test';
import { accounts, csrfToken, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });

test('admin sets grade calendars and previews then applies a school template', async ({ page, baseURL }) => {
  test.setTimeout(120_000);
  await signIn(page, accounts.adminB);
  const errors = collectErrors(page, baseURL!);
  const year = new Date().getFullYear() + 3;
  const stamp = String(Date.now());
  const createYear = async (number: number) => {
    const response = await page.request.post('/new-year/year', {
      headers: { 'X-CSRF-TOKEN': await csrfToken(page), Accept: 'application/json' },
      data: { name: `Calendar ${number} ${stamp}`, start_date: `${number}-01-01`, end_date: `${number}-12-31`,
        fee_due_date: `${number}-02-01`, fee_due_charges: 0, terms: [] },
    });
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.error).toBe(false);
    return body.data.id as number;
  };
  const source = await createYear(year);
  await page.goto(`/term?session_year_id=${source}`);
  await expect(page.getByRole('heading', { level: 1, name: 'School terms' })).toBeVisible();
  for (const width of [1024, 1280]) {
    await page.setViewportSize({ width, height: 800 });
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  }
  const addTerm = async (name: string, from: string, to: string) => {
    await page.locator('#new-term-name').fill(name);
    await page.locator('#new-term-start').fill(`${year}-01-14`);
    await page.locator('#new-term-end').fill(`${year}-03-20`);
    await page.locator('#new-term-from').selectOption(from);
    await page.locator('#new-term-to').selectOption(to);
    await page.locator('#new-term-lock').fill(`${year}-03-18`);
    await page.locator('#new-term-reports').fill(`${year}-03-25`);
    await page.getByRole('button', { name: 'Add term', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Term saved' })).toBeVisible();
  };
  await addTerm('Term 1 lower grades', '0', '6');
  await addTerm('Term 1 upper grades', '7', '12');
  await expect(page.locator('[data-term-id]')).toHaveCount(2);
  await page.locator('#template-name').fill(`Calendar template ${stamp}`);
  await page.getByRole('button', { name: 'Save calendar template', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Calendar template saved' })).toBeVisible();
  const target = await createYear(year + 1);
  await page.goto(`/term?session_year_id=${target}`);
  await page.locator('#apply-template').selectOption({ label: `Calendar template ${stamp}` });
  await page.getByRole('button', { name: 'Preview calendar', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Calendar preview', exact: true })).toBeVisible();
  await expect(page.locator('[data-term-id]')).toHaveCount(0);
  await page.getByRole('button', { name: 'Apply calendar template', exact: true }).click();
  await expect(page.locator('[data-term-id]')).toHaveCount(2);
  await expect(page.locator('[data-term-id]').first()).toContainText(`${year + 1}-03-25`);
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(errors).toEqual([]);
});

test('a teacher can read calendar metadata but cannot open management', async ({ page, baseURL }) => {
  await signIn(page, accounts.teacher);
  expect((await page.goto('/term'))?.status()).toBe(403);
  const context = await page.request.get('/api/v2/me/context', { headers: { Origin: baseURL! } });
  const yearId = (await context.json()).data.current_school.session_year.id;
  expect((await page.request.get(`/api/v2/academics/terms?session_year_id=${yearId}`, { headers: { Origin: baseURL! } })).status()).toBe(200);
  expect((await page.request.get('/api/v2/academics/term-templates', { headers: { Origin: baseURL! } })).status()).toBe(403);
});
