import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Fixture = { subjects: number[]; today: string; expiry: string; year: number };
function fixture(): Fixture {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/pathways-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
  }).trim());
}
async function source(page: Page, data: Fixture, title: string) {
  await page.getByLabel('Source entry title').fill(title);
  await page.getByLabel('Source document title').fill('Synthetic reviewed prospectus');
  await page.getByLabel('Public HTTPS source address').fill('https://institution.example.test/prospectus');
  await page.getByLabel('Source year').fill(String(data.year));
  await page.getByLabel('Source reviewed on').fill(data.today);
  await page.getByLabel('Review expires on').fill(data.expiry);
  await page.getByLabel('Guidance and source limitations').fill('Synthetic browser source only. Verify all institution conditions.');
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`source programme, review, withdrawal and Grade 9 guide at ${width}px`, async ({ page, baseURL }) => {
    const data = fixture();
    const title = `Synthetic browser programme ${width}-${Date.now()}`;
    const guide = `Synthetic browser guide ${width}-${Date.now()}`;
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.admin);
    const response = await page.goto('/pathways');
    expect(response!.status()).toBe(200);
    expect(response!.headers()['cache-control']).toContain('no-store');
    expect(response!.headers()['referrer-policy']).toBe('no-referrer');
    let releaseEditor!: () => void;
    const editorReady = new Promise<void>(resolve => { releaseEditor = resolve; });
    await page.route('**/assets/js/pathways.js', async route => { await editorReady; await route.continue(); }, { times: 1 });
    await page.getByRole('link', { name: 'Add a programme source', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Add subject rule', exact: true })).toBeDisabled();
    releaseEditor();
    await expect(page.getByRole('button', { name: 'Add subject rule', exact: true })).toBeEnabled();
    await source(page, data, title);
    await page.getByLabel('Institution').fill('Synthetic institution');
    await page.locator('#pathway-subject-0-id').selectOption(String(data.subjects[0]));
    await page.locator('#pathway-subject-0-minimum').fill('50.25');
    await page.getByRole('button', { name: 'Add subject rule', exact: true }).click();
    await page.locator('#pathway-subject-1-id').selectOption(String(data.subjects[1]));
    await page.locator('#pathway-subject-1-counts').uncheck();
    await page.locator('#pathway-band-0-points').fill('0');
    await page.getByRole('button', { name: 'Add score band', exact: true }).click();
    await page.locator('#pathway-band-1-minimum').fill('50');
    await page.locator('#pathway-band-1-points').fill('4');
    await page.getByRole('button', { name: 'Add score band', exact: true }).click();
    await page.locator('[data-pathway-row="band"]').last().getByRole('button', { name: 'Remove score band', exact: true }).click();
    await page.getByLabel('Number of best subjects to count').fill('1');
    await page.getByLabel('Minimum points stated by the source').fill('4');
    await check(page);
    await page.getByRole('button', { name: 'Publish reviewed source', exact: true }).click();
    await page.getByRole('link', { name: title, exact: true }).click();
    await expect(page.locator('#pathway-subject-0-minimum')).toHaveValue('50.25');
    await expect(page.locator('#pathway-reviewed')).toHaveValue(data.today);
    await page.getByLabel('Source publication status').selectOption('withdrawn');
    await page.getByRole('button', { name: 'Save reviewed source revision', exact: true }).click();
    const article = page.locator('article').filter({ has: page.getByRole('link', { name: title, exact: true }) });
    await expect(article).toContainText('Revision 2');
    await expect(article).toContainText('Withdrawn');
    await page.getByRole('link', { name: 'Add a Grade 9 choice guide', exact: true }).click();
    await source(page, data, guide);
    await page.locator('#pathway-compulsory').selectOption([String(data.subjects[0])]);
    await page.locator('#pathway-elective-0-title').fill('Synthetic choice');
    await page.locator('#pathway-elective-0-subjects').selectOption([String(data.subjects[1]), String(data.subjects[2])]);
    await check(page);
    await page.getByRole('button', { name: 'Publish reviewed source', exact: true }).click();
    await expect(page.getByRole('link', { name: guide, exact: true })).toBeVisible();
    await check(page);
    expect(errors).toEqual([]);
  });
}
