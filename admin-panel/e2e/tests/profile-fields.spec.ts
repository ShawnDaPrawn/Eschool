import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });

for (const width of [1024, 1280]) {
  test(`profile editor, partial save and permitted export at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 800 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.adminB);
    const key = `transport_${width}_${Date.now()}`;
    const categoryLabel = `Transport ${key}`;
    const fieldLabel = `Transport route ${key}`;
    await page.goto('/profile-configuration?profile_type=learner');
    await expect(page.getByRole('heading', { name: 'Profile fields', exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.getByRole('textbox', { name: 'Category label', exact: true }).fill(categoryLabel);
    await page.getByRole('textbox', { name: 'Category key', exact: true }).fill(key);
    await page.getByRole('button', { name: 'Save category', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Profile category saved.' })).toBeVisible();
    await page.getByRole('combobox', { name: 'Field category', exact: true }).selectOption({ label: categoryLabel });
    await page.getByRole('textbox', { name: 'Field label', exact: true }).fill(fieldLabel);
    await page.getByRole('textbox', { name: 'Field key', exact: true }).fill(key);
    await page.getByRole('button', { name: 'Save field', exact: true }).click();
    await expect(page.getByRole('link', { name: `Edit ${fieldLabel}`, exact: true })).toBeVisible();
    // The existing student list supplies the authorised subject URL.
    await page.goto('/students');
    const profileLink = page.getByRole('link', { name: 'Person profile', exact: true }).first();
    await expect(profileLink).toBeVisible();
    const uri = await profileLink.getAttribute('href');
    await page.goto(uri!);
    await expect(page.getByRole('textbox', { name: fieldLabel, exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const input = page.getByRole('textbox', { name: fieldLabel, exact: true });
    await input.fill('Blue bus');
    const sent = page.waitForRequest((request) => request.method() === 'POST' && request.url().includes('/people-profiles/'));
    await page.getByRole('button', { name: 'Save profile', exact: true }).click();
    const body = (await sent).postData()!;
    expect(body).toContain(`values[${key}][text]`);
    expect(body).not.toContain('values[first_name]');
    await expect(page.getByRole('status').filter({ hasText: /successfully/i })).toBeVisible();
    expect(await page.evaluate(() => Object.values(sessionStorage).join(' '))).not.toContain('Blue bus');
    await page.reload();
    await expect(input).toHaveValue('Blue bus');
    const download = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Export permitted fields', exact: true }).click();
    const file = await download;
    const stream = await file.createReadStream();
    let csv = '';
    for await (const chunk of stream!) csv += chunk.toString();
    expect(csv).toContain('Blue bus');
    expect(errors).toEqual([]);
  });
}

test('teacher can read own profile but cannot configure school fields', async ({ page }) => {
  await signIn(page, accounts.teacher);
  const refusal = await page.goto('/profile-configuration');
  expect(refusal?.status()).toBe(403);
  await page.goto('/people-profiles');
  await expect(page.getByRole('heading', { name: 'Person profile', exact: true })).toBeVisible();
});

for (const width of [1024, 1280]) {
  test(`private profile file preserves unsaved edits at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 800 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.adminB);
    const key = `files_${width}_${Date.now()}`;
    const categoryLabel = `Private documents ${key}`;
    await page.goto('/profile-configuration?profile_type=learner');
    await page.getByRole('textbox', { name: 'Category label', exact: true }).fill(categoryLabel);
    await page.getByRole('textbox', { name: 'Category key', exact: true }).fill(key);
    await page.getByRole('button', { name: 'Save category', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Profile category saved.' })).toBeVisible();
    for (const [suffix, type] of [['route', 'text'], ['letter', 'file']]) {
      await page.getByRole('combobox', { name: 'Field category', exact: true }).selectOption({ label: categoryLabel });
      await page.getByRole('textbox', { name: 'Field label', exact: true }).fill(`${suffix} ${key}`);
      await page.getByRole('textbox', { name: 'Field key', exact: true }).fill(`${key}_${suffix}`);
      await page.getByRole('combobox', { name: 'Field type', exact: true }).selectOption(type);
      await page.getByRole('button', { name: 'Save field', exact: true }).click();
      await expect(page.getByRole('link', { name: `Edit ${suffix} ${key}`, exact: true })).toBeVisible();
    }
    await page.goto('/students');
    const uri = await page.getByRole('link', { name: 'Person profile', exact: true }).first().getAttribute('href');
    await page.goto(uri!);
    const input = page.getByRole('textbox', { name: `route ${key}`, exact: true });
    await input.fill('Private unsaved route');
    const upload = page.locator(`[data-profile-file-upload="${key}_letter"]`);
    const pdf = Buffer.from('%PDF-1.4\nPRIVATE DOCUMENT BODY\n%%EOF');
    await upload.locator('input[type=file]').setInputFiles({ name: 'Private-letter.pdf', mimeType: 'application/pdf', buffer: pdf });
    await upload.getByRole('button', { name: 'Upload or replace file' }).click();
    await expect(upload.getByRole('status')).toHaveText('File saved. Other profile edits are still unsaved.');
    await expect(input).toHaveValue('Private unsaved route');
    const current = page.locator(`[data-profile-file="${key}_letter"]`);
    const oldUrl = await current.getByRole('link', { name: 'Private-letter.pdf' }).getAttribute('href');
    expect(oldUrl).toContain('/protected/profile-files/people/');
    expect(oldUrl).not.toContain('Private-letter');
    const response = await page.request.get(oldUrl!, { headers: { Origin: baseURL!, 'X-School-Id': '2' } });
    expect(response.status()).toBe(200);
    expect(response.headers()['cache-control']).toContain('no-store');
    expect(await response.body()).toEqual(pdf);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await page.evaluate(() => Object.values(sessionStorage).join(' '))).not.toContain('Private unsaved route');
    await current.getByRole('checkbox', { name: 'Remove file when saving' }).check();
    await current.getByRole('checkbox', { name: 'Remove file when saving' }).uncheck();
    const write = page.waitForRequest((request) => request.method() === 'POST' && request.url().includes('/people-profiles/'));
    await page.getByRole('button', { name: 'Save profile', exact: true }).click();
    const body = (await write).postData()!;
    expect(body).toContain(`values[${key}_route][text]`);
    expect(body).not.toContain(`values[${key}_letter]`);
    await expect(page.getByRole('status').filter({ hasText: /successfully/i })).toBeVisible();
    await page.reload();
    await expect(input).toHaveValue('Private unsaved route');
    await expect(current.getByRole('link', { name: 'Private-letter.pdf' })).toBeVisible();
    await current.getByRole('checkbox', { name: 'Remove file when saving' }).check();
    await page.getByRole('button', { name: 'Save profile', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: /successfully/i })).toBeVisible();
    const revoked = await page.request.get(oldUrl!, { headers: { Origin: baseURL!, 'X-School-Id': '2' } });
    expect(revoked.status()).toBe(404);
    expect(errors).toEqual([]);
  });
}
