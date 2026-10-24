import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });

test('admin prepares and publishes an exact question-based term plan then copies a draft', async ({ page, baseURL }) => {
  await signIn(page, accounts.adminB);
  const errors = collectErrors(page, baseURL!);
  await page.goto('/curriculum-plans/new');
  await page.getByLabel('Curriculum title', { exact: true }).fill('Browser curriculum');
  await page.getByLabel('Grade', { exact: true }).selectOption({ label: 'Grade 7' });
  await page.getByRole('button', { name: 'Add a term', exact: true }).click();
  await page.getByRole('button', { name: 'Add topic', exact: true }).click();
  await page.getByLabel('Topic name', { exact: true }).fill('Fractions');
  await page.getByLabel('From week', { exact: true }).fill('1');
  await page.getByLabel('To week', { exact: true }).fill('3');
  await page.getByRole('button', { name: 'Add task', exact: true }).click();
  await page.getByLabel('Task name', { exact: true }).fill('Fractions task');
  await page.getByLabel('Task maximum mark', { exact: true }).fill('20.00');
  await page.getByRole('button', { name: 'Add question', exact: true }).click();
  await page.getByLabel('Question maximum mark', { exact: true }).first().fill('7.50');
  await page.getByRole('button', { name: 'Add question', exact: true }).click();
  await page.getByLabel('Question maximum mark', { exact: true }).nth(1).fill('12.50');
  for (const width of [1024, 1280]) {
    await page.setViewportSize({ width, height: 900 });
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  }
  await page.getByRole('button', { name: 'Save draft', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Curriculum draft saved' })).toBeVisible();
  await expect(page.getByLabel('Question maximum mark', { exact: true }).nth(1)).toHaveValue('12.50');
  await page.getByRole('button', { name: /^Publish term:/ }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Term assessment published' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Save draft', exact: true })).toBeDisabled();
  await page.getByRole('button', { name: 'Copy to draft', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Definitions copied to a draft' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Save draft', exact: true })).toBeEnabled();
  expect(errors).toEqual([]);
});

test('teacher gets an allocation-filtered list and has no publication control', async ({ page }) => {
  await signIn(page, accounts.teacher);
  await page.goto('/curriculum-plans');
  await expect(page.getByRole('heading', { level: 1, name: 'Curriculum plans' })).toBeVisible();
  await expect(page.getByRole('button', { name: /^Publish term:/ })).toHaveCount(0);
});


test('empty saved component maps can be reopened and repaired without losing weights', async ({ page }) => {
  await signIn(page, accounts.adminB);
  await page.goto('/curriculum-plans/new');
  await page.getByLabel('Curriculum title', { exact: true }).fill('Empty component repair');
  const annual = page.getByRole('group', { name: 'Annual component weights (optional until the annual plan is ready)', exact: true });
  await annual.getByLabel('New component name (sba, exam, oral)', { exact: true }).fill('sba');
  await annual.getByRole('button', { name: 'Add component', exact: true }).click();
  await annual.getByRole('button', { name: 'Remove sba', exact: true }).click();
  await page.getByRole('button', { name: 'Add a term', exact: true }).click();
  const term = page.getByRole('group', { name: 'Term component weights', exact: true });
  await term.getByRole('button', { name: 'Remove sba', exact: true }).click();
  await page.getByRole('button', { name: 'Save draft', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Curriculum draft saved' })).toBeVisible();
  for (const group of [annual, term]) {
    await group.getByLabel('New component name (sba, exam, oral)', { exact: true }).fill('sba');
    await group.getByRole('button', { name: 'Add component', exact: true }).click();
    await group.getByLabel('SBA (%)', { exact: true }).fill('100');
  }
  await page.getByRole('button', { name: 'Save draft', exact: true }).click();
  await expect(annual.getByLabel('SBA (%)', { exact: true })).toHaveValue('100');
  await expect(term.getByLabel('SBA (%)', { exact: true })).toHaveValue('100');
});
