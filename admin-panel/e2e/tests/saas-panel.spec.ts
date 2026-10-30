import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { PASSWORD } from '../support/accounts';
import { horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'initial'): { email: string } {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/saas-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, SAAS_FIXTURE_ACTION: action },
  }).trim());
}

test.describe.configure({ mode: 'serial' });
for (const width of [390, 820, 1280]) {
  test(`role-free operator, private school setup and revocation at ${width}px`, async ({ page, browser }) => {
    const operator = fixture('pending');
    await page.setViewportSize({ width, height: 1000 });
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 500) errors.push(`HTTP ${response.status()}: ${new URL(response.url()).pathname}`); });
    await page.goto('/login');
    await page.locator('[name=email]').fill(operator.email);
    await page.locator('[name=password]').fill(PASSWORD);
    await page.locator('form button[type=submit], form input[type=submit]').first().click();
    await expect(page).toHaveURL(/\/saas\/password$/);
    await expect(page.getByRole('heading', { name: 'Change your platform password', exact: true })).toBeVisible();
    const csrfDenied = await page.request.post(new URL('/saas/password', page.url()).toString(), {
      data: { old_password: PASSWORD, new_password: 'Synthetic-platform-rotated-password-674!', confirm_password: 'Synthetic-platform-rotated-password-674!' },
    });
    expect(csrfDenied.status()).toBe(419);
    await page.getByLabel('Current password', { exact: true }).fill(PASSWORD);
    await page.getByLabel('New password', { exact: true }).fill('Synthetic-platform-rotated-password-674!');
    await page.getByLabel('Confirm new password', { exact: true }).fill('Synthetic-platform-rotated-password-674!');
    expect(await seriousViolations(page)).toEqual([]);
    await page.getByRole('button', { name: 'Change password', exact: true }).click();
    await expect(page).toHaveURL(/\/saas$/);
    await expect(page.getByRole('heading', { name: 'Schools', exact: true })).toBeVisible();
    expect(await page.locator('main').innerText()).not.toContain('@');
    await page.getByRole('link', { name: 'Set up a school', exact: true }).first().click();
    const slug = `saas-browser-${width}-${Date.now()}`;
    const email = `${slug}@school.example`;
    await page.getByLabel('School name', { exact: true }).fill(`Synthetic platform school ${width}`);
    await page.getByLabel('Short name for addresses', { exact: true }).fill(slug);
    await page.getByLabel('First name', { exact: true }).fill('Synthetic');
    await page.getByLabel('Last name', { exact: true }).fill('Administrator');
    await page.getByLabel('Email (their sign-in)', { exact: true }).fill(email);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const responsePromise = page.waitForResponse(response => response.request().method() === 'POST' && new URL(response.url()).pathname === '/saas/schools');
    await page.getByRole('button', { name: 'Create school', exact: true }).click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(response.headers()['cache-control']).toBe('no-store, private');
    const invitation = (await page.locator('.saas-secret').innerText()).trim();
    expect(new URL(invitation).pathname).toMatch(/^\/invite\/staff\/[A-Za-z0-9]{64}$/);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.getByRole('link', { name: 'Open the school', exact: true }).click();
    await expect(page.getByRole('heading', { name: `Synthetic platform school ${width}`, exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Send the invitation again', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);

    // The HQ audit log shows the set-up, filtered to this school.
    await page.getByRole('link', { name: 'Audit log for this school', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Audit log', exact: true })).toBeVisible();
    await expect(page.locator('.saas-audit tbody tr').filter({ hasText: 'School set up' })).toHaveCount(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.goBack();

    // An Owner manages platform staff and their roles.
    await page.getByRole('link', { name: 'Platform staff', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Platform staff', exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'Invite a staff member', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.goBack();

    // A separate cookie jar proves the emailed link: the administrator chooses
    // their own password, then signs in. Nobody else ever knew a password.
    const context = await browser.newContext({ viewport: { width, height: 1000 } });
    const admin = await context.newPage();
    admin.on('pageerror', error => errors.push(error.message));
    admin.on('response', response => { if (response.status() >= 500) errors.push(`HTTP ${response.status()}: ${new URL(response.url()).pathname}`); });
    await admin.goto(new URL(new URL(invitation).pathname, page.url()).toString());
    await expect(admin.getByRole('heading', { name: `Welcome to Synthetic platform school ${width}`, exact: true })).toBeVisible();
    expect(await seriousViolations(admin)).toEqual([]);
    expect(await horizontalOverflow(admin)).toBeLessThanOrEqual(1);
    await admin.getByLabel('New password', { exact: true }).fill(PASSWORD);
    await admin.getByLabel('Confirm new password', { exact: true }).fill(PASSWORD);
    await admin.getByRole('button', { name: 'Set password', exact: true }).click();
    await expect(admin).toHaveURL(/\/login$/);
    await expect(admin.getByRole('status').filter({ hasText: 'Your password is set.' })).toBeVisible();
    await admin.locator('[name=email]').fill(email);
    await admin.locator('[name=password]').fill(PASSWORD);
    await admin.locator('form button[type=submit], form input[type=submit]').first().click();
    await expect(admin).not.toHaveURL(/\/resetpassword$/);
    const home = await admin.goto(new URL('/home', page.url()).toString());
    expect(home?.status()).toBe(200);
    const denied = await admin.goto(new URL('/saas', page.url()).toString());
    expect(denied?.status()).toBe(403);
    await context.close();

    // HQ's own record of the school: profile, contacts, contracts and timeline.
    await page.getByRole('link', { name: 'School record, contacts and contracts', exact: true }).click();
    await expect(page.getByRole('heading', { name: `Synthetic platform school ${width}`, exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'School profile and stage', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);

    fixture('revoke');
    const retired = await page.reload();
    expect(retired?.status()).toBe(403);
    expect(await page.locator('body').innerText()).not.toContain(`Synthetic platform school ${width}`);
    expect(errors).toEqual([]);
  });
}
