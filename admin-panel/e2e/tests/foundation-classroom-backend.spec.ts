import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Setup = { group: number; group_name: string; learner_name: string; card: string; locator: string; tokens: number };
function fixture(action = 'initial', setup?: Setup): Record<string, unknown> {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/foundation-classroom-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, FOUNDATION_FIXTURE_ACTION: action,
      FOUNDATION_FIXTURE_GROUP: String(setup?.group ?? ''), FOUNDATION_FIXTURE_CARD: setup?.card ?? '' },
  }).trim());
}
async function groupArticle(page: Page, name: string) {
  await page.goto('/foundation-classrooms');
  for (let i = 0; i < 20; i++) {
    const article = page.locator('article').filter({ has: page.getByRole('heading', { name, exact: true }) });
    if (await article.count()) return article;
    await page.getByRole('link', { name: 'More classroom groups', exact: true }).click();
  }
  throw new Error('Synthetic Foundation group was not in the bounded current choices.');
}

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`real manager tablet registration, Teacher delegation and restricted practice at ${width}px`, async ({ page, browser, baseURL }) => {
    const setup = fixture() as unknown as Setup;
    const managerErrors = collectErrors(page, baseURL!);
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, accounts.admin);
    const group = await groupArticle(page, setup.group_name);
    await group.getByRole('combobox', { name: /^Classroom eligibility/ }).selectOption('1');
    await group.getByRole('textbox', { name: /^Reason for eligibility decision/ }).fill('Synthetic explicit Foundation eligibility');
    await group.getByRole('button', { name: 'Save classroom eligibility', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Foundation classrooms', exact: true })).toBeVisible();
    const tabletContext = await browser.newContext({ storageState: await page.context().storageState(), viewport: { width, height: 1000 } });
    const tablet = await tabletContext.newPage(), tabletErrors = collectErrors(tablet, baseURL!);
    await tablet.goto(`${baseURL}/foundation-classroom`); await tablet.bringToFront();
    await tablet.getByRole('button', { name: 'Generate tablet setup code', exact: true }).click();
    await expect(tablet.locator('[data-foundation-setup-code]')).toHaveText(/^NESET1\.[A-Za-z0-9_-]{43}$/);
    const setupCode = (await tablet.locator('[data-foundation-setup-code]').textContent())!;
    await page.bringToFront(); await page.goto('/foundation-classrooms');
    const tabletName = `Synthetic Foundation tablet ${width}`;
    await page.getByRole('textbox', { name: /^Tablet name/ }).fill(tabletName);
    await page.getByRole('textbox', { name: /^Tablet setup code/ }).fill(setupCode);
    await page.getByRole('button', { name: 'Register classroom tablet', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Tablet access code', exact: true })).toBeVisible();
    const device = (await page.locator('[data-foundation-secret]').textContent())!.trim(); expect(device).toMatch(/^NED1\./);
    const teacherContext = await browser.newContext({ viewport: { width, height: 1000 } }), teacher = await teacherContext.newPage();
    const teacherErrors = collectErrors(teacher, baseURL!);
    await signIn(teacher, accounts.teacher);
    const classroom = await groupArticle(teacher, setup.group_name);
    await classroom.getByRole('combobox', { name: /^Classroom tablet/ }).selectOption({ label: tabletName });
    await classroom.getByRole('button', { name: 'Authorize my classroom lesson', exact: true }).click();
    await expect(teacher.getByRole('heading', { name: 'Teacher classroom authorization', exact: true })).toBeVisible();
    const delegation = (await teacher.locator('[data-foundation-secret]').textContent())!.trim(); expect(delegation).toMatch(/^NECL1\./);
    expect(await seriousViolations(teacher)).toEqual([]); expect(await horizontalOverflow(teacher)).toBeLessThanOrEqual(1);
    let interrupted = false;
    const reviewBodies: string[] = [];
    await tablet.route('**/api/v2/foundation-**', async route => {
      const request = route.request(), headers = await request.allHeaders();
      expect(headers.cookie).toBeUndefined(); expect(headers.referer).toBeUndefined(); expect(new URL(request.url()).search).toBe('');
      expect(headers.authorization).toMatch(/^Bearer (?:NEF1\.[^~]+~)?NED1\./);
      if (/\/practice\/\d+\/entries\/\d+\/review$/.test(new URL(request.url()).pathname)) {
        reviewBodies.push(request.postData()!);
        if (!interrupted) { interrupted = true; const committed = await route.fetch(); expect(committed.status()).toBe(200); await route.abort('failed'); return; }
      }
      await route.continue();
    });
    // Card inventory becomes read-only after the existing short grant. Practice is independent.
    fixture('readonly_inventory', setup);
    await tablet.bringToFront();
    await tablet.getByLabel('Tablet access code', { exact: true }).fill(device);
    await tablet.getByLabel('Teacher classroom authorization', { exact: true }).fill(delegation);
    await tablet.getByRole('button', { name: 'Pair classroom tablet', exact: true }).click();
    await tablet.getByLabel('Printed classroom card code', { exact: true }).fill(setup.locator);
    await tablet.getByRole('button', { name: 'Choose this classroom card', exact: true }).click();
    await expect(tablet.getByRole('heading', { name: setup.learner_name, exact: true })).toBeVisible();
    await tablet.getByRole('button', { name: 'Start my practice', exact: true }).click();
    await expect(tablet.getByRole('heading', { name: 'Synthetic Foundation one half as a decimal?', exact: true })).toBeVisible();
    await tablet.getByLabel('Your number', { exact: true }).fill('0.5');
    await tablet.getByRole('button', { name: 'Save my answer', exact: true }).click();
    await expect(tablet.getByRole('button', { name: 'Retry same request', exact: true })).toBeVisible();
    expect(fixture('stats', setup).reviews).toBe(1);
    await tablet.getByRole('button', { name: 'Retry same request', exact: true }).click();
    await expect(tablet.getByRole('heading', { name: 'Well done!', exact: true })).toBeVisible();
    expect(reviewBodies).toHaveLength(2); expect(reviewBodies[0]).toBe(reviewBodies[1]);
    expect(await seriousViolations(tablet)).toEqual([]); expect(await horizontalOverflow(tablet)).toBeLessThanOrEqual(1);
    expect(tabletErrors).toEqual([]); expect(managerErrors).toEqual([]); expect(teacherErrors).toEqual([]);
    fixture('withdraw', setup);
    await tablet.getByRole('button', { name: 'Read saved practice', exact: true }).click();
    await expect(tablet.getByLabel('Tablet access code', { exact: true })).toBeVisible();
    await expect(tablet.getByRole('heading', { name: setup.learner_name, exact: true })).toHaveCount(0);
    await expect(tablet.locator('[data-foundation-questions]')).toBeEmpty();
    const stats = fixture('stats', setup);
    expect(stats.reviews).toBe(1); expect(stats.gate_scans).toBe(0); expect(stats.tokens).toBe(setup.tokens);
    expect(tabletErrors).toEqual(['HTTP 403: /api/v2/foundation-learner/context']);
    await teacherContext.close(); await tabletContext.close();
  });
}
