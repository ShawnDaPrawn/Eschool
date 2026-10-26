import { expect, test, type Page } from '@playwright/test';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

// Synthetic transport proves the real public client. Native Foundation tests
// separately prove device/Teacher/learner authority against actual MySQL rows.
const deviceId = '15151515-1515-4515-8515-151515151515';
const leaseId = '25252525-2525-4525-8525-252525252525';
const sessionId = '35353535-3535-4535-8535-353535353535';
const cardId = '45454545-4545-4545-8545-454545454545';
const device = `NED1.1.${deviceId}.${'a'.repeat(64)}`;
const authorization = `NECL1.1.${leaseId}.${'b'.repeat(64)}`;
const learner = `NEF1.1.${sessionId}.${'c'.repeat(64)}`;
const locator = `NELOC1.1.${cardId}`;
async function pair(page: Page): Promise<void> {
  await page.getByLabel('Tablet access code', { exact: true }).fill(device);
  await page.getByLabel('Teacher classroom authorization', { exact: true }).fill(authorization);
  await page.getByRole('button', { name: 'Pair classroom tablet', exact: true }).click();
  await page.getByLabel('Printed classroom card code', { exact: true }).fill(locator);
  await page.getByRole('button', { name: 'Choose this classroom card', exact: true }).click();
}

test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`restricted Foundation practice preserves exact uncertain operations and erases at ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await page.context().addCookies([{ name: 'ordinary_session', value: 'synthetic-do-not-send', url: baseURL!, httpOnly: true }]);
    const expires = new Date(Date.now() + 10 * 60_000).toISOString();
    const context = { school_id: 1, learner_id: 11, name: 'PRIVATE classroom learner', teaching_group_id: 41, expires_at: expires, restricted: true, capabilities: ['daily_practice'] };
    const review = { id: 91, entry_id: 81, correct: true, reviewed_on: '2026-10-26', schedule: { version: 'synthetic-browser', rating: 3, difficulty: 5, stability_days: 1, retrievability_before: null, interval_days: 1 } };
    let saved = false, attempts = 0, effects = 0;
    const reviews: string[] = [], outgoing: string[] = [];
    const set = () => ({ id: 71, school_id: 1, reader_id: 111, learner_id: 11, group_id: 41, practice_on: '2026-10-26', server_time: new Date().toISOString(), offline_until: expires, estimated_seconds: 60, completed: saved, streak_days: 0,
      items: [{ id: 81, item_id: 61, topic_id: 51, source_version_id: 31, estimated_seconds: 60, definition: { kind: 'choice', prompt: 'PRIVATE choose one half as a decimal', explanation: 'PRIVATE <script> must remain ordinary explanation text.', options: [{ id: 'a', text: '0.5' }, { id: 'b', text: '2' }], answers: ['a'] }, review: saved ? review : null }] });
    await page.route('**/api/v2/foundation-**', async route => {
      const request = route.request(), url = new URL(request.url()), headers = await request.allHeaders();
      outgoing.push(url.pathname);
      expect(url.origin).toBe(new URL(baseURL!).origin); expect(url.search).toBe('');
      expect(headers['cookie']).toBeUndefined(); expect(headers['referer']).toBeUndefined();
      const answer = (data: object) => route.fulfill({ status: 200, contentType: 'application/json', json: { error: false, message: 'Synthetic result', code: 200, data } });
      if (url.pathname === '/api/v2/foundation-device/select') {
        expect(headers['authorization']).toBe(`Bearer ${device}`);
        const body = request.postDataJSON();
        expect(Object.keys(body).sort()).toEqual(['classroom_authorization', 'locator', 'operation_id']);
        expect(body.classroom_authorization).toBe(authorization); expect(body.locator).toBe(locator);
        return answer({ ...context, classroom_session: learner });
      }
      expect(headers['authorization']).toBe(`Bearer ${learner}~${device}`);
      if (url.pathname === '/api/v2/foundation-learner/context') return answer(context);
      if (url.pathname === '/api/v2/foundation-learner/practice') { expect(Object.keys(request.postDataJSON())).toEqual(['operation_id']); return answer(set()); }
      if (url.pathname === '/api/v2/foundation-learner/practice/71') return answer(set());
      if (url.pathname === '/api/v2/foundation-learner/practice/71/entries/81/review') {
        reviews.push(request.postData()!); const body = request.postDataJSON();
        expect(Object.keys(body).sort()).toEqual(['answer', 'operation_id', 'rating']);
        expect(body.answer).toBe('a'); expect(body.rating).toBe(3);
        attempts++; if (!saved) { saved = true; effects++; }
        if (attempts === 1) return route.fulfill({ status: 200, contentType: 'application/json', json: { error: false, code: 200, data: { unverified: true } } });
        return answer(review);
      }
      if (url.pathname === '/api/v2/foundation-learner/leave') return answer({ left: true });
      throw new Error(`Unexpected restricted path ${url.pathname}`);
    });
    await page.goto('/foundation-classroom');
    await page.getByRole('button', { name: 'Generate tablet setup code', exact: true }).click();
    await expect(page.locator('[data-foundation-setup-code]')).toHaveText(/^NESET1\.[A-Za-z0-9_-]{43}$/);
    await pair(page);
    await expect(page.getByRole('heading', { name: context.name, exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Start my practice', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'PRIVATE choose one half as a decimal', exact: true })).toBeVisible();
    await expect(page.locator('[data-foundation-questions] form')).toHaveAttribute('method', 'POST');
    expect(await seriousViolations(page)).toEqual([]); expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.getByLabel('0.5', { exact: true }).check();
    await page.getByRole('button', { name: 'Save my answer', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Retry same request', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Save my answer', exact: true })).toBeDisabled();
    await page.getByRole('button', { name: 'Retry same request', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Well done!', exact: true })).toBeVisible();
    expect(reviews).toHaveLength(2); expect(reviews[0]).toBe(reviews[1]); expect(effects).toBe(1);
    await expect(page.getByText('PRIVATE <script> must remain ordinary explanation text.', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Next question', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Great practice today!', exact: true })).toBeVisible();
    await page.evaluate(() => { Object.defineProperty(document, 'hidden', { configurable: true, get: () => true }); document.dispatchEvent(new Event('visibilitychange')); });
    await expect(page.getByRole('heading', { name: context.name, exact: true })).toHaveCount(0);
    await expect(page.locator('[data-foundation-questions]')).toBeEmpty();
    await expect(page.locator('[data-foundation-setup-code]')).toBeEmpty();
    await expect(page.getByLabel('Tablet access code', { exact: true })).toHaveValue('');
    await expect(page.getByLabel('Teacher classroom authorization', { exact: true })).toHaveValue('');
    expect(await page.evaluate(() => [localStorage.length, sessionStorage.length])).toEqual([0, 0]);
    expect(outgoing.every(path => path.startsWith('/api/v2/foundation-'))).toBe(true);
    expect(errors).toEqual([]);
  });
}


test('disabled script cannot submit classroom secrets or answers through a GET fallback', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 900 } });
  const page = await context.newPage();
  await page.goto(`${baseURL}/foundation-classroom`);
  await expect(page.getByText('This classroom needs a supported browser. Ask your teacher for help.', { exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Pair classroom tablet', exact: true })).toBeDisabled();
  for (const name of ['Tablet access code', 'Teacher classroom authorization', 'Printed classroom card code']) {
    const control = page.getByLabel(name, { exact: true });
    await expect(control).toBeDisabled(); expect(await control.getAttribute('name')).toBeNull();
  }
  await expect(page.locator('[data-foundation-pair-form]')).toHaveAttribute('method', 'POST');
  await expect(page.locator('[data-foundation-select-form]')).toHaveAttribute('method', 'POST');
  const response = await page.request.post(`${baseURL}/api/v2/foundation-device/select`, { form: { accidental_private_field: 'synthetic-no-credential' } });
  expect(response.status()).toBe(401); expect(response.headers()['cache-control']).toContain('no-store');
  expect(await response.text()).not.toContain('synthetic-no-credential');
  await context.close();
});
