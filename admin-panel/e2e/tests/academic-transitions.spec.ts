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
const runId = Date.now().toString(36);
test.beforeAll(() => { php(`$transitionRun = '${runId}'; require base_path('e2e/support/transition-fixture.php');`); });
for (const width of [1024, 1280]) {
  test(`dated join, subject change, leave and signed export at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-transitions');
    await page.getByRole('textbox', { name: 'Learner name', exact: true }).fill(`Transition${width}${runId}`);
    await page.getByRole('button', { name: 'Find learner', exact: true }).click();
    await page.getByRole('link', { name: new RegExp(`Transition${width}${runId} Rehearsal`) }).click();
    let form = page.getByRole('form', { name: 'Preview learner change', exact: true });
    await form.getByRole('combobox', { name: 'New homeroom', exact: true }).selectOption({ label: 'Grade 5 A' });
    await form.getByRole('checkbox', { name: 'Transition group A · Transition rehearsal subject', exact: true }).check();
    await form.getByRole('textbox', { name: 'Reason for this change', exact: true }).fill('Synthetic browser join with reviewed date');
    await form.getByRole('button', { name: 'Preview learner change', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Review dated change', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Apply reviewed change', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Learner change recorded' })).toBeVisible();
    const learner = new URL(page.url()).searchParams.get('learner_id');
    await page.getByRole('combobox', { name: 'Change type', exact: true }).selectOption('subject_change');
    await page.getByRole('button', { name: 'Open change form', exact: true }).click();
    form = page.getByRole('form', { name: 'Preview learner change', exact: true });
    await form.getByRole('combobox', { name: 'New teaching group', exact: true }).selectOption({ label: 'Transition group B · Transition rehearsal subject' });
    await form.getByRole('combobox', { name: 'Earlier task mark rule', exact: true }).selectOption('carried');
    await form.getByRole('textbox', { name: 'Reason for this change', exact: true }).fill('Synthetic browser subject move with carried canonical marks');
    await form.getByRole('button', { name: 'Preview learner change', exact: true }).click();
    await page.getByRole('button', { name: 'Apply reviewed change', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Learner change recorded' })).toBeVisible();
    await page.getByRole('combobox', { name: 'Change type', exact: true }).selectOption('leave');
    await page.getByRole('button', { name: 'Open change form', exact: true }).click();
    form = page.getByRole('form', { name: 'Preview learner change', exact: true });
    await form.getByRole('textbox', { name: 'Reason for this change', exact: true }).fill('Synthetic browser departure preserving all history');
    await form.getByRole('button', { name: 'Preview learner change', exact: true }).click();
    await page.getByRole('button', { name: 'Apply reviewed change', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Learner change recorded' })).toBeVisible();
    await page.getByRole('button', { name: 'Preview exit package', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Review exit evidence', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Prepare reviewed exit package', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Exit package queued' })).toBeVisible();
    php(`app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1, function () { $p = \\App\\Models\\Academics\\AcademicExitPackage::where('learner_id', ${Number(learner)})->latest('id')->firstOrFail(); app(\\App\\Services\\Academics\\Transitions\\ExitArtifacts::class)->generate($p->id, $p->generation_id); });`);
    await page.getByRole('link', { name: 'Refresh export status', exact: true }).click();
    const downloadLink = page.getByRole('link', { name: 'Download signed exit package', exact: true }).first();
    const response = await page.request.get((await downloadLink.getAttribute('href'))!);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/zip');
    expect((await response.body()).subarray(0, 4).equals(Buffer.from([80, 75, 3, 4]))).toBe(true);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
test('ordinary teachers cannot open office learner transitions', async ({ page }) => {
  await signIn(page, accounts.teacher);
  const response = await page.goto('/academic-transitions');
  expect(response?.status()).toBe(403);
});
