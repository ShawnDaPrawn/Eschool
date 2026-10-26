import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(): Record<string, string | number> {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/family-forms-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
  }).trim());
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`exact private consent publish, sign and withdraw at ${width}px`, async ({ page, browser, baseURL }) => {
    const setup = fixture();
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.admin);
    await page.goto(`/family-forms/new?activity_group_id=${setup.group}`);
    await page.getByLabel('Request title', { exact: true }).fill(`Synthetic consent ${width}`);
    const wording = 'Synthetic complete trip wording <script>neverExecute()</script>';
    await page.getByLabel('Exact consent wording', { exact: true }).fill(wording);
    await page.locator('#consent-event_starts_at_local').fill(String(setup.starts));
    await page.locator('#consent-event_ends_at_local').fill(String(setup.ends));
    await page.locator('#consent-respond_by_local').fill(String(setup.deadline));
    await page.getByRole('button', { name: 'Find eligible participants', exact: true }).click();
    await page.getByLabel(String(setup.name), { exact: true }).check();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.getByRole('button', { name: 'Publish exact consent request', exact: true }).click();
    await expect(page).toHaveURL(/\/family-forms\/consents\/([a-f0-9-]{36})\/managed$/);
    const managedUrl = page.url();
    const id = managedUrl.match(/consents\/([a-f0-9-]{36})/)![1];
    await expect(page.getByRole('heading', { name: `Synthetic consent ${width}`, exact: true })).toBeVisible();
    await expect(page.getByText(wording, { exact: true })).toBeVisible();
    await expect(page.getByText('Permission is not current', { exact: true })).toBeVisible();
    // Parents keep their existing app-only sign-in policy; exercise the real
    // family API here and qualify the native signing screen in its own target.
    const family = await browser.newContext({ baseURL });
    const headers = { Authorization: `Bearer ${setup.token}`, 'X-School-Id': '1', 'X-Account-Id': String(setup.guardian_id), Accept: 'application/json' };
    const read = await family.request.get(`/api/v2/family-forms/consents/${id}?learner_id=${setup.learner}`, { headers });
    expect(read.status()).toBe(200);
    expect(read.headers()['cache-control']).toContain('no-store');
    const record = (await read.json()).data;
    expect(record.wording).toBe(wording);
    const responsePath = `/api/v2/family-forms/consents/${id}/learners/${setup.learner}/response`;
    const signed = await family.request.post(responsePath, { headers, data: { operation_id: crypto.randomUUID(), revision: record.revision, fingerprint: record.fingerprint, decision: 'yes', acknowledged: true, signed_name: setup.guardian } });
    expect(signed.status()).toBe(200);
    await page.goto(managedUrl);
    await expect(page.getByText('Current signed permission', { exact: true })).toBeVisible();
    const withdrawn = await family.request.post(responsePath, { headers, data: { operation_id: crypto.randomUUID(), revision: (await signed.json()).data.revision, fingerprint: record.fingerprint, decision: 'no', acknowledged: true, signed_name: setup.guardian } });
    expect(withdrawn.status()).toBe(200);
    await page.goto(managedUrl);
    await expect(page.getByText('Permission is not current', { exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
    await family.close();
  });
}
