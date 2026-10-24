import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
function php(code: string) {
  return execFileSync('php', ['artisan', 'tinker', '--execute', code], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
}
test.beforeAll(() => { php("require base_path('e2e/support/report-fixture.php');"); });

for (const width of [1024, 1280]) {
  test(`prepare, review, sign, preview and release reports at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-reports');
    await page.getByRole('combobox', { name: 'Homeroom', exact: true }).selectOption({ label: 'Grade 5 A' });
    await page.getByRole('button', { name: 'View reports', exact: true }).click();
    const preparation = page.getByRole('form', { name: 'Preview selected reports', exact: true });
    await preparation.getByRole('combobox', { name: 'Report design', exact: true }).selectOption({ label: 'Browser report design' });
    await preparation.getByRole('combobox', { name: 'Report type', exact: true }).selectOption('running');
    await preparation.getByRole('checkbox', { name: 'Report Rehearsal Learner', exact: true }).check();
    await preparation.getByRole('button', { name: 'Preview selected reports', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Evidence preview', exact: true })).toBeVisible();
    await expect(page.getByText('Provisional running report', { exact: true }).last()).toBeVisible();
    await page.getByRole('button', { name: 'Prepare reviewed reports', exact: true }).click();
    const batchId = Number(new URL(page.url()).searchParams.get('batch_id'));
    expect(batchId).toBeGreaterThan(0);
    const comment = page.getByRole('form', { name: /Comment for .* Report rehearsal subject/ }).first();
    await comment.getByRole('textbox', { name: 'Comment text', exact: true }).fill(`Browser reviewed progress ${width}`);
    await comment.getByRole('textbox', { name: 'Correction reason', exact: true }).fill('Synthetic leadership review of the report comment');
    await comment.getByRole('button', { name: 'Save comment', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Report comment saved' })).toBeVisible();
    await page.getByRole('button', { name: 'Grade-head sign-off', exact: true }).click();
    await page.getByRole('button', { name: 'Principal sign-off', exact: true }).click();
    php(`app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1, function () { $batch = \\App\\Models\\Academics\\ReportBatch::findOrFail(${batchId}); foreach ($batch->cards as $card) { app(\\App\\Services\\Academics\\Reports\\ReportArtifacts::class)->generate($card->id, $batch->fingerprint); } });`);
    await page.getByRole('link', { name: 'Refresh batch', exact: true }).click();
    const pdf = page.getByRole('link', { name: /Preview PDF for/ }).first();
    const response = await page.request.get((await pdf.getAttribute('href'))!);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/pdf');
    expect((await response.body()).subarray(0, 5).toString()).toBe('%PDF-');
    await page.getByRole('button', { name: 'Preview release', exact: true }).click();
    await page.getByRole('button', { name: 'Release and notify families', exact: true }).click();
    await expect(page.getByRole('heading', { name: new RegExp(`Report batch ${batchId} · Released`) })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Comment text', exact: true })).toHaveCount(0);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}

test('allocated teacher retains a conflicting draft and explicitly reviews it before saving', async ({ page, browser, baseURL }) => {
  await signIn(page, accounts.admin);
  await page.goto('/academic-reports');
  const preparation = page.getByRole('form', { name: 'Preview selected reports', exact: true });
  await preparation.getByRole('combobox', { name: 'Report design', exact: true }).selectOption({ label: 'Browser report design' });
  await preparation.getByRole('combobox', { name: 'Report type', exact: true }).selectOption('running');
  await preparation.getByRole('checkbox', { name: 'Report Rehearsal Learner', exact: true }).check();
  await preparation.getByRole('button', { name: 'Preview selected reports', exact: true }).click();
  await page.getByRole('button', { name: 'Prepare reviewed reports', exact: true }).click();
  const teacherContext = await browser.newContext({ baseURL });
  try {
  const teacherPage = await teacherContext.newPage();
  await signIn(teacherPage, accounts.teacher);
  await teacherPage.goto('/academic-reports');
  const form = teacherPage.getByRole('form', { name: /Comment for .* Report rehearsal subject/ }).first();
  await expect(form).toBeVisible();
  const action = await form.getAttribute('action');
  const subjectId = await form.locator('input[name="subject_id"]').inputValue();
  const other = await teacherContext.newPage();
  await other.goto(teacherPage.url());
  await form.getByRole('textbox', { name: 'Comment text', exact: true }).fill('First teacher comment');
  await form.getByRole('button', { name: 'Save comment', exact: true }).click();
  const stale = other.locator(`form[action="${action}"]`).filter({ has: other.locator(`input[name="subject_id"][value="${subjectId}"]`) }).first();
  await stale.getByRole('textbox', { name: 'Comment text', exact: true }).fill('My preserved draft');
  await stale.getByRole('button', { name: 'Save comment', exact: true }).click();
  await expect(other.getByRole('heading', { name: 'Your unsaved comment', exact: true })).toBeVisible();
  await expect(other.locator('p.report-comment-text').filter({ hasText: 'My preserved draft' }).first()).toBeVisible();
  await other.getByRole('button', { name: 'Prepare reviewed comment draft', exact: true }).click();
  const reviewed = other.locator(`form[action="${action}"]`).filter({ has: other.locator(`input[name="subject_id"][value="${subjectId}"]`) }).first();
  await expect(reviewed.getByRole('textbox', { name: 'Comment text', exact: true })).toHaveValue('My preserved draft');
  await reviewed.getByRole('button', { name: 'Save comment', exact: true }).click();
  await expect(other.getByRole('status').filter({ hasText: 'Report comment saved' })).toBeVisible();
  expect(await seriousViolations(other)).toEqual([]);
  expect(await horizontalOverflow(other)).toBeLessThanOrEqual(1);
  await other.close();
  } finally { await teacherContext.close(); }
});
