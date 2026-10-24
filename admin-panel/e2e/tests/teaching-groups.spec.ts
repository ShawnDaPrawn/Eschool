import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let groupUrl: string;
let groupName: string;

test.beforeAll(() => {
  execFileSync('php', ['artisan', 'people:backfill-identities'], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});

test('admin maps a class, creates a group and records dated teacher and learner membership', async ({ page, baseURL }) => {
  await signIn(page, accounts.admin);
  const errors = collectErrors(page, baseURL!);
  await page.goto('/teaching-groups');
  const mapping = page.getByLabel('Grade for class Grade 5', { exact: true });
  await mapping.selectOption({ label: 'Grade 5' });
  await mapping.locator('xpath=ancestor::form').getByRole('button', { name: 'Save grade mapping' }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Class grade mapping saved' })).toBeVisible();
  await page.getByRole('link', { name: 'New teaching group', exact: true }).click();
  groupName = `Browser teaching group ${Date.now()}`;
  await page.getByRole('textbox', { name: 'Group name', exact: true }).fill(groupName);
  await page.getByRole('combobox', { name: 'Subject', exact: true }).selectOption({ label: 'Mathematics' });
  await page.getByRole('combobox', { name: 'Grade', exact: true }).selectOption({ label: 'Grade 5' });
  await page.getByRole('main').getByRole('combobox', { name: 'School year', exact: true }).selectOption({ label: '2026' });
  await page.getByRole('combobox', { name: 'Homeroom (optional)', exact: true }).selectOption({ label: 'Grade 5 A' });
  await page.getByRole('button', { name: 'Save teaching group', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Teaching group saved' })).toBeVisible();
  groupUrl = page.url();
  const teacher = page.getByRole('form', { name: 'Allocate a teacher', exact: true });
  await teacher.getByRole('combobox', { name: 'Teacher', exact: true }).selectOption({ index: 0 });
  await teacher.getByRole('textbox', { name: 'Reason', exact: true }).fill('Browser teaching allocation');
  await teacher.getByRole('button', { name: 'Save teacher allocation' }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Dated assignment saved' })).toBeVisible();
  const member = page.getByRole('form', { name: 'Add learner membership', exact: true });
  await member.getByRole('combobox', { name: 'Learner', exact: true }).selectOption({ index: 0 });
  await member.getByRole('textbox', { name: 'Reason', exact: true }).fill('Browser learner membership');
  await member.getByRole('button', { name: 'Save learner membership' }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Dated assignment saved' })).toBeVisible();
  await expect(page.getByText('Browser learner membership', { exact: true })).toBeVisible();
  for (const width of [1024, 1280]) {
    await page.setViewportSize({ width, height: 900 });
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  }
  expect(errors).toEqual([]);
});

test('allocated teacher reads the roster and cannot change structures or appointments', async ({ page }) => {
  await signIn(page, accounts.teacher);
  await page.goto(groupUrl);
  await expect(page.getByRole('heading', { level: 1, name: groupName })).toBeVisible();
  await expect(page.getByText('Browser learner membership', { exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Save learner membership' })).toHaveCount(0);
  await expect(page.getByRole('link', { name: 'Edit group', exact: true })).toHaveCount(0);
  expect((await page.goto('/academic-allocations'))?.status()).toBe(403);
});
