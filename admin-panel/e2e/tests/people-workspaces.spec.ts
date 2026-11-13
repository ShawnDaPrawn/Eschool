import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect as playwrightExpect, test } from '@playwright/test';
const expect = playwrightExpect.configure({ timeout: 30_000 });
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
// These flows include file conversion/export and axe under the shared test runtime.
test.setTimeout(180_000);
let memberPath: string;

test.beforeAll(() => {
  execFileSync('php', ['artisan', 'tinker', '--execute', "app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1, function () { $admin = \\App\\Models\\User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail(); app(\\App\\Services\\Platform\\Modules\\ModuleGate::class)->change('people_pool', true, $admin); });"], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});

test('school manager invites a volunteer without granting member consent at 390px', async ({ page, baseURL }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await signIn(page, accounts.admin);
  const errors = collectErrors(page, baseURL!);
  await page.goto('/people/alumni/invite');
  await page.getByRole('combobox', { name: 'Member type', exact: true }).selectOption('volunteer');
  await page.getByRole('textbox', { name: 'First name', exact: true }).fill('Nomsa');
  await page.getByRole('textbox', { name: 'Last name', exact: true }).fill('Browser Reviewer');
  await page.getByRole('textbox', { name: 'Email address', exact: true }).fill(`pool-${Date.now()}@example.test`);
  await page.getByLabel('Initial password').fill('TemporaryReview2026!x');
  await page.getByRole('button', { name: 'Create invitation', exact: true }).click();
  await expect(page.getByRole('alert').filter({ hasText: 'Choose a career talk, a job shadow or both.' })).toBeVisible();
  await expect(page).toHaveURL(/\/people\/alumni\/invite/);
  await expect(page.getByRole('textbox', { name: 'First name', exact: true })).toHaveValue('Nomsa');
  await expect(page.getByLabel('Initial password')).toHaveValue('');
  await page.getByRole('combobox', { name: 'Offers career talks', exact: true }).selectOption('1');
  await page.getByLabel('Initial password').fill('TemporaryReview2026!x');
  await page.getByRole('button', { name: 'Create invitation', exact: true }).click();
  await expect(page).toHaveURL(/\/people\/alumni\/members\/\d+/);
  memberPath = new URL(page.url()).pathname;
  await expect(page.getByRole('heading', { name: 'Nomsa Browser Reviewer', exact: true })).toBeVisible();
  await expect(page.getByText('Invited', { exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: /accept consent/i })).toHaveCount(0);
  expect(await page.content()).not.toContain('TemporaryReview2026!x');
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(errors).toEqual([]);
});

for (const width of [320, 390, 1024, 1280]) {
  test(`people workspace layouts at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await signIn(page, accounts.admin);
    for (const url of ['/people/facilitators', '/people/alumni', '/people/alumni/bookings']) {
      const response = await page.goto(url);
      expect(response?.status()).toBe(200);
      await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1);
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      expect(await seriousViolations(page)).toEqual([]);
    }
  });
}

test('another school cannot open the invited member and a teacher cannot manage the pool', async ({ page }) => {
  await signIn(page, accounts.adminB);
  expect((await page.goto(memberPath))?.status()).toBe(404);
  await page.context().clearCookies();
  await signIn(page, accounts.teacher);
  expect((await page.goto('/people/alumni'))?.status()).toBe(403);
  expect((await page.goto('/people/facilitators'))?.status()).toBe(403);
});

test('school manager creates a facilitator with the supported staff invitation form at 390px', async ({ page }) => {
  test.setTimeout(300_000);
  await page.setViewportSize({ width: 390, height: 844 });
  await signIn(page, accounts.admin);
  await page.goto('/people/facilitators');
  await page.getByRole('link', { name: 'Add facilitator', exact: true }).click();
  const form = page.locator('#staff-form');
  await expect(form.getByRole('combobox', { name: 'Role', exact: true })).toHaveValue(/\d+/);
  const email = `facilitator-${Date.now()}@example.test`;
  await form.getByRole('textbox', { name: 'First name', exact: true }).fill('Synthetic');
  await form.getByRole('textbox', { name: 'Last name', exact: true }).fill('Facilitator');
  await form.getByRole('textbox', { name: 'Email address', exact: true }).fill(email);
  await form.getByRole('textbox', { name: 'Mobile number', exact: true }).fill('0821234567');
  await form.getByRole('radio', { name: 'Female', exact: true }).check();
  await form.getByLabel(/Date of birth/).fill('1990-01-01');
  await form.getByRole('textbox', { name: 'Address', exact: true }).fill('Synthetic school rehearsal address');
  await form.locator('input[type=file]').setInputFiles({ name: 'profile.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAALUlEQVR4nGO0iapgoCVgoqnpoxaMWjBqwagFoxaMWjBqwagFoxaMWjBqARUBAFhaAU4lavslAAAAAElFTkSuQmCC', 'base64') });
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await seriousViolations(page)).toEqual([]);
  const saved = page.waitForResponse(response => response.request().method() === 'POST' && new URL(response.url()).pathname === '/staff');
  const reloaded = page.waitForResponse(response => response.request().isNavigationRequest() && new URL(response.url()).pathname === '/staff');
  await form.getByRole('button', { name: 'Create staff invitation', exact: true }).click();
  expect((await (await saved).json()).error).toBe(false);
  await reloaded;
  await page.waitForLoadState('load');
  await page.goto('/people/facilitators?search=Synthetic');
  await expect(page.getByText('Synthetic Facilitator', { exact: true }).first()).toBeVisible();
});
