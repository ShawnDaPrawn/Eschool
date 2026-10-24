import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });

test.beforeAll(() => {
  const fixture = String.raw`app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1, function () {
    $admin = \App\Models\User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $year = \App\Models\SessionYear::where('name', '2026')->firstOrFail();
    $grade = \App\Models\Academics\GradeLevel::where('code', '5')->firstOrFail();
    $class = \App\Models\ClassSchool::where('name', 'Grade 5')->firstOrFail();
    \App\Models\ClassSchool::whereKey($class->id)->update(['grade_level_id' => $grade->id]);
    $room = \App\Models\ClassSection::where('class_id', $class->id)->firstOrFail();
    $teacher = \App\Models\Teacher::whereHas('user', fn($q) => $q->where('email', 'teacher.a@demo.navigateed.test'))->firstOrFail();
    if (!\App\Models\Academics\HomeroomAllocation::where('teacher_id', $teacher->id)->where('class_section_id', $room->id)->where('session_year_id', $year->id)->exists()) {
      app(\App\Services\Academics\TeachingGroups::class)->dated($admin, 'homerooms', ['teacher_id' => $teacher->id, 'class_section_id' => $room->id, 'session_year_id' => $year->id, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic register browser rehearsal']);
    }
    foreach (\App\Models\Students::where('class_section_id', $room->id)->get() as $learner) {
      \App\Models\People\Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id], ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic register browser rehearsal']);
    }
  });`;
  execFileSync('php', ['artisan', 'tinker', '--execute', fixture], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});

for (const width of [1024, 1280]) {
  test(`capture, correction, QR and explicit conflict review at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-registers');
    await page.getByRole('button', { name: /Open register: Grade 5/ }).first().click();
    const form = page.getByRole('form', { name: 'Capture register', exact: true });
    await expect(form.locator('[data-register-row]').first()).toBeVisible();
    await form.getByRole('button', { name: 'Mark remaining present', exact: true }).click();
    await form.getByRole('textbox', { name: 'Reason for correction', exact: true }).fill('Synthetic browser roll call verified');
    const qr = await form.locator('[data-register-row]').first().getAttribute('data-qr-code');
    await form.getByRole('textbox', { name: 'Scan or paste learner QR', exact: true }).fill(qr!);
    await form.getByRole('textbox', { name: 'Scan or paste learner QR', exact: true }).press('Enter');
    await expect(form.getByRole('status')).toContainText('Learner marked present');
    // Hold another tab on the old revision while this tab captures the register.
    const other = await page.context().newPage();
    await other.goto(page.url());
    await form.getByRole('button', { name: 'Save register', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Register saved' })).toBeVisible();
    await other.locator('[data-register-state]').first().selectOption('absent');
    await other.getByRole('textbox', { name: 'Reason for correction', exact: true }).fill('Synthetic verified correction');
    await other.getByRole('button', { name: 'Save register', exact: true }).click();
    await expect(other.getByText('Review changes before saving', { exact: true })).toBeVisible();
    await expect(other.getByRole('button', { name: 'Save register', exact: true })).toBeDisabled();
    await other.getByRole('button', { name: 'Prepare reviewed draft', exact: true }).click();
    await expect(other.getByText('Reviewed entries are a draft. Check them and save to apply the correction.', { exact: true })).toBeVisible();
    await other.getByRole('button', { name: 'Save register', exact: true }).click();
    await expect(other.getByRole('status').filter({ hasText: 'Register saved' })).toBeVisible();
    expect(await seriousViolations(other)).toEqual([]);
    expect(await horizontalOverflow(other)).toBeLessThanOrEqual(1);
    await other.close();
    await page.reload();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}

test('homeroom teacher can read earlier day but only the grade head may correct it', async ({ page }) => {
  await signIn(page, accounts.teacher);
  await page.goto('/academic-registers?on_date=2026-02-10');
  await page.getByRole('button', { name: /Open register: Grade 5/ }).first().click();
  await expect(page.getByRole('status').filter({ hasText: 'Read only.' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Save register', exact: true })).toBeDisabled();
});
