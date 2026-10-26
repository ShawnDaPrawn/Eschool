import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Fixture = { group: number; tutee: number; topic: number; prepared: string; starts: string; ends: string };
function fixture(): Fixture {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/peer-tutoring-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`allocated staff matches, schedules, confirms and corrects service at ${width}px`, async ({ page, baseURL }) => {
    const data = fixture();
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.teacher);
    const response = await page.goto('/peer-tutoring');
    expect(response!.status()).toBe(200);
    expect(response!.headers()['cache-control']).toContain('no-store');
    expect(response!.headers()['referrer-policy']).toBe('no-referrer');
    await page.getByLabel('Teaching group', { exact: true }).selectOption(String(data.group));
    await page.getByRole('button', { name: 'Load current learners', exact: true }).click();
    await page.getByLabel('Learner needing support', { exact: true }).selectOption(String(data.tutee));
    await page.getByRole('button', { name: 'Find current tutor matches', exact: true }).click();
    const match = page.locator('details').filter({ has: page.locator(`input[name="topic_id"][value="${data.topic}"]`) }).first();
    await match.locator('summary').click();
    await match.getByLabel('Starts at', { exact: true }).fill(data.starts);
    await match.getByLabel('Ends at', { exact: true }).fill(data.ends);
    const venue = `Synthetic browser peer supervision ${width}-${Date.now()}`;
    await match.getByLabel('Venue', { exact: true }).fill(venue);
    await check(page);
    await match.getByRole('button', { name: 'Schedule peer session', exact: true }).click();
    const scheduled = page.locator('article').filter({ hasText: venue });
    await expect(scheduled).toContainText('Scheduled');
    await scheduled.locator('summary').filter({ hasText: 'Cancel peer session' }).click();
    await scheduled.getByLabel('Reason', { exact: true }).fill('Synthetic supervision no longer available');
    await scheduled.getByRole('button', { name: 'Cancel peer session', exact: true }).click();
    await expect(page.locator('article').filter({ hasText: venue })).toContainText('Cancelled');
    let prepared = page.locator(`[data-peer-session="${data.prepared}"]`);
    await prepared.locator('summary').filter({ hasText: 'Confirm supervised service' }).click();
    await prepared.getByLabel('Verified service minutes', { exact: true }).fill('35');
    await check(page);
    const form = prepared.locator('form').filter({ has: page.getByRole('button', { name: 'Confirm session change', exact: true }) });
    const original = await form.evaluate(element => Object.fromEntries(new FormData(element as HTMLFormElement).entries()) as Record<string, string>);
    const action = await form.getAttribute('action');
    await prepared.getByRole('button', { name: 'Confirm session change', exact: true }).click();
    await expect(prepared).toContainText('Verified service minutes: 35');
    const retry = await page.request.post(action!, { form: original, maxRedirects: 0 });
    expect(retry.status()).toBe(302);
    await page.reload();
    await expect(prepared).toContainText('Verified service minutes: 35');
    await prepared.locator('summary').filter({ hasText: 'Correct completed service' }).click();
    await prepared.getByLabel('Reason', { exact: true }).fill('Synthetic correction of attendance verification');
    await prepared.getByRole('button', { name: 'Correct completed service', exact: true }).click();
    await expect(prepared).toContainText('Reversed');
    await expect(prepared).toContainText('Verified service minutes: 0');
    await check(page);
    expect(errors).toEqual([]);
  });
}
