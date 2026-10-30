import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });

test.beforeAll(() => {
  const fixture = String.raw`app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1, function () {
    $user = \App\Models\User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $year = \App\Models\SessionYear::where('name', '2026')->firstOrFail();
    $grade = \App\Models\Academics\GradeLevel::where('code', '5')->firstOrFail();
    \App\Models\ClassSchool::where('name', 'Grade 5')->update(['grade_level_id' => $grade->id]);
    $group = \App\Models\Academics\TeachingGroup::where('name', 'Browser timetable shared')->first();
    if ($group) {
      $rows = \App\Models\Academics\TimetableLesson::where('teaching_group_id', $group->id)->where('active', true)->get()->map(fn($lesson) => array_replace($lesson->getAttributes(), ['active' => false]))->all();
      if ($rows) {
        $engine = app(\App\Services\Academics\TimetableEngine::class);
        $preview = $engine->preview($user, $rows);
        $engine->save($user, $preview['rows'], $preview['fingerprint']);
      }
      return;
    }
    $subject = \App\Models\Subject::where('name', 'Mathematics')->firstOrFail();
    $teacher = \App\Models\Teacher::whereHas('user', fn($q) => $q->where('email', 'teacher.a@demo.navigateed.test'))->firstOrFail();
    $service = app(\App\Services\Academics\TeachingGroups::class);
    $group = $service->save($user, ['name' => 'Browser timetable shared', 'subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'session_year_id' => $year->id, 'class_section_id' => null, 'active' => true]);
    $service->dated($user, 'teachers', ['teacher_id' => $teacher->id, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic browser rehearsal'], $group->id);
  });`;
  execFileSync('php', ['artisan', 'tinker', '--execute', fixture], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});

for (const width of [1024, 1280]) {
  test(`rooms, bells, clash preview and mapped import at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-timetable?week_on=2026-11-02');
    const suffix = `${width}-${Date.now()}`;
    const roomName = `Browser room ${suffix}`;
    const start = width === 1024 ? '06:00' : '07:00';
    const end = width === 1024 ? '06:45' : '07:45';
    await page.locator('#rooms > summary').click();
    await page.getByRole('textbox', { name: 'Room name', exact: true }).fill(roomName);
    await page.getByRole('textbox', { name: 'Building (optional)', exact: true }).fill('Main');
    await page.getByRole('spinbutton', { name: 'Capacity (optional)', exact: true }).fill('35');
    await page.getByRole('button', { name: 'Save room', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Room saved' })).toBeVisible();
    await page.locator('#bells > summary').click();
    await page.getByRole('textbox', { name: 'Bell schedule name', exact: true }).fill(`Browser bells ${suffix}`);
    await page.getByRole('textbox', { name: 'School section', exact: true }).fill('Browser');
    await page.getByRole('textbox', { name: 'Period name', exact: true }).fill(`Period ${suffix}`);
    await page.getByLabel('Starts at', { exact: true }).fill(start);
    await page.getByLabel('Ends at', { exact: true }).fill(end);
    await page.getByRole('button', { name: 'Add period', exact: true }).click();
    // A blank added row is intentionally harmless; labels must remain distinct.
    await expect(page.getByRole('textbox', { name: 'Period name', exact: true })).toHaveCount(2);
    await page.getByRole('button', { name: 'Save bell schedule', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Bell schedule saved' })).toBeVisible();
    const placement = page.getByRole('form', { name: 'Preview a lesson', exact: true });
    await placement.getByRole('combobox', { name: 'Teaching group', exact: true }).selectOption({ label: 'Browser timetable shared · Mathematics · Grade 5' });
    await placement.getByRole('combobox', { name: 'Teacher', exact: true }).selectOption({ label: 'Thandi Mokoena' });
    await placement.getByRole('combobox', { name: 'Room', exact: true }).selectOption({ label: roomName });
    await placement.getByRole('combobox', { name: 'Period', exact: true }).selectOption({ label: `Browser · Period ${suffix} · ${start}–${end}` });
    await placement.getByRole('combobox', { name: 'Day', exact: true }).selectOption({ label: 'Monday' });
    await placement.getByRole('button', { name: 'Preview placement', exact: true }).click();
    await expect(page.getByText('No clashes. Ready to save.', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Save previewed timetable', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Timetable saved' })).toBeVisible();
    await expect(page.locator('#week').getByText(roomName, { exact: false })).toBeVisible();
    await page.goBack(); // Repeat the unchanged preview to prove the existing placement is detected.
    await page.getByRole('form', { name: 'Preview a lesson', exact: true }).getByRole('button', { name: 'Preview placement', exact: true }).click();
    await expect(page.getByText('Resolve these clashes before saving.', { exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Save previewed timetable', exact: true })).toBeDisabled();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.goto('/academic-timetable?week_on=2026-11-02');
    await page.locator('#import > summary').click();
    await page.getByRole('textbox', { name: 'Timetable sheet', exact: true }).fill('Class,Teacher,Room,Day,Hour\n7A,Tara,R1,Tuesday,P1\n');
    await page.getByRole('button', { name: 'Read sheet columns', exact: true }).click();
    const columns = page.getByRole('form', { name: 'Match timetable columns', exact: true });
    for (const [field, column] of [['Teaching group', 'Class'], ['Teacher', 'Teacher'], ['Room', 'Room'], ['Day', 'Day'], ['Period', 'Hour']]) {
      await columns.getByRole('combobox', { name: field, exact: true }).selectOption({ label: column });
    }
    await columns.getByRole('button', { name: 'Match school values', exact: true }).click();
    const values = page.getByRole('form', { name: 'Preview timetable import', exact: true });
    for (const [source, label] of [['7A', 'Browser timetable shared · Mathematics · Grade 5'], ['Tara', 'Thandi Mokoena'], ['R1', roomName], ['Tuesday', 'Tuesday'], ['P1', `Browser · Period ${suffix} · ${start}–${end}`]]) {
      await values.getByRole('combobox', { name: source, exact: true }).selectOption({ label });
    }
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await values.getByRole('button', { name: 'Preview import clashes', exact: true }).click();
    await expect(page.getByText('No clashes. Ready to save.', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Save previewed timetable', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Timetable saved' })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}

test('exam periods are added, kept accessible and removed', async ({ page, baseURL }) => {
  await signIn(page, accounts.admin);
  const errors = collectErrors(page, baseURL!);
  await page.goto('/exam-periods');
  await expect(page.getByRole('heading', { name: 'Exam periods', exact: true })).toBeVisible();
  const form = page.getByRole('form', { name: 'Add an exam period', exact: true });
  const name = `Browser exams ${Date.now()}`;
  await form.getByRole('textbox', { name: 'Name', exact: true }).fill(name);
  await form.getByRole('combobox', { name: 'Grade', exact: true }).selectOption({ label: 'Grade 5' });
  await form.getByLabel('First exam day', { exact: true }).fill('2026-11-23');
  await form.getByLabel('Last exam day', { exact: true }).fill('2026-11-27');
  await form.getByRole('button', { name: 'Add exam period', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Exam period saved' })).toBeVisible();
  await expect(page.getByRole('heading', { name: `${name} · Grade 5`, exact: true })).toBeVisible();
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  await page.getByRole('form', { name: `Remove ${name} · Grade 5`, exact: true }).getByRole('button', { name: 'Remove exam period', exact: true }).click();
  await expect(page.getByRole('status').filter({ hasText: 'Exam period removed' })).toBeVisible();
  expect(errors).toEqual([]);
});

test('teachers cannot open the manager workbench', async ({ page }) => {
  await signIn(page, accounts.teacher);
  expect((await page.goto('/academic-timetable'))?.status()).toBe(403);
});
