import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(file = 'family-visits-fixture.php', env: Record<string, string> = {}): Record<string, string | number> {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', `echo json_encode(require base_path('e2e/support/${file}'));`], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, ...env },
  }).trim());
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`private booking, school approval and exact early-collection retry at ${width}px`, async ({ page, browser, baseURL }) => {
    const setup = fixture();
    const meetingTitle = `Synthetic browser visit ${width} ${crypto.randomUUID().slice(0, 8)}`;
    const errors = collectErrors(page, baseURL!);
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, accounts.admin);
    await page.goto('/family-visits');
    await page.getByLabel('Meeting host').selectOption(String(setup.host));
    await page.getByLabel('Meeting title').fill(meetingTitle);
    await page.getByLabel('Meeting venue').fill('Synthetic private room <script>neverExecute()</script>');
    await page.getByLabel('Meeting starts').fill(String(setup.starts));
    await page.getByLabel('Meeting ends').fill(String(setup.ends));
    await page.getByLabel('Booking deadline').fill(String(setup.deadline));
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.getByRole('button', { name: 'Publish meeting slot', exact: true }).click();
    await expect(page.getByRole('heading', { name: meetingTitle, exact: true })).toBeVisible();
    const family = await browser.newContext({ baseURL });
    const headers = { Authorization: `Bearer ${setup.token}`, 'X-School-Id': '1', 'X-Account-Id': String(setup.guardian_id), Accept: 'application/json' };
    const slots = await family.request.get(`/api/v2/family-visits/meetings?learner_id=${setup.learner}`, { headers });
    expect(slots.status()).toBe(200);
    const slot = (await slots.json()).data.items.find((row: {title: string}) => row.title === meetingTitle);
    expect(slot).toBeTruthy();
    const booked = await family.request.post(`/api/v2/family-visits/meetings/${slot.id}/book`, { headers, data: { operation_id: crypto.randomUUID(), revision: slot.revision, learner_id: setup.learner } });
    expect(booked.status()).toBe(200);
    await page.reload();
    const appointment = page.locator('article').filter({ has: page.getByRole('heading', { name: meetingTitle, exact: true }) });
    await expect(appointment.getByText(String(setup.name), { exact: false })).toBeVisible();
    const pass = page.locator(`[data-gate-pass="${setup.pass}"]`);
    await pass.getByRole('button', { name: 'Approve this collection request', exact: true }).click();
    await expect(page.locator(`[data-gate-pass="${setup.pass}"]`)).toContainText('Approved');
    await page.getByLabel('Maximum online scan age in seconds').fill('90');
    await page.getByRole('button', { name: 'Save collection rules', exact: true }).click();
    await expect(page.getByLabel('Maximum online scan age in seconds')).toHaveValue('90');
    await appointment.getByRole('button', { name: 'Cancel meeting slot', exact: true }).click();
    await expect(page.getByRole('heading', { name: meetingTitle, exact: true }).locator('..')).toContainText('Cancelled');
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);

    await page.goto('/card-reader');
    await page.getByLabel('Private reader credential').fill(String(setup.reader));
    await page.getByRole('button', { name: 'Start reader', exact: true }).click();
    const proof = fixture('cards-fixture.php', { CARD_FIXTURE_ACTION: 'proof', CARD_FIXTURE_ID: String(setup.card) });
    await page.getByLabel('Secure app code or personalized tap').fill(String(proof.credential));
    await page.getByRole('button', { name: 'Verify card', exact: true }).click();
    await expect(page.getByText('Named collector: Synthetic browser designated collector', { exact: true })).toBeVisible();
    const button = page.getByRole('button', { name: 'Record collection', exact: true });
    await expect(button).toBeDisabled();
    await page.getByLabel('I checked the named collector in person.').check();
    const operations: string[] = [];
    await page.route('**/api/v2/card-device/gate-passes/*/collect', async route => {
      const request = route.request(), ownHeaders = await request.allHeaders();
      expect(ownHeaders.cookie).toBeUndefined();
      expect(ownHeaders.referer).toBeUndefined();
      expect(ownHeaders.authorization).toBe(`Bearer ${setup.reader}`);
      operations.push(request.postDataJSON().operation_id);
      if (operations.length === 1) { expect((await route.fetch()).status()).toBe(200); await route.abort('failed'); }
      else await route.continue();
    });
    await button.click();
    await expect(page.getByRole('status').first()).toContainText('Collection outcome is uncertain.');
    await page.getByRole('button', { name: 'Retry same collection record', exact: true }).click();
    await expect(page.getByRole('status').first()).toHaveText('Collection recorded.');
    expect(operations).toHaveLength(2);
    expect(operations[0]).toBe(operations[1]);
    await expect(page.getByText('Named collector: Synthetic browser designated collector', { exact: true })).toHaveCount(0);
    const result = await family.request.get(`/api/v2/family-visits/passes?learner_id=${setup.learner}`, { headers });
    expect((await result.json()).data.items.find((row: {id: string}) => row.id === setup.pass).revision).toBe(3);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
    await family.close();
  });
}
