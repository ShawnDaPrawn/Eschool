import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type BrowserContext } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

const coverDate = new Date(Date.now() + 86_400_000).toISOString().slice(0, 10);
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
type SessionState = Awaited<ReturnType<BrowserContext['storageState']>>;
let adminState: SessionState, teacherState: SessionState;
test.beforeAll(async ({browser, baseURL}) => {
  const fixture = String.raw`app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1, function () {
    $user=\App\Models\User::where('email','admin.a@demo.navigateed.test')->firstOrFail();
    app(\App\Services\Platform\Modules\ModuleGate::class)->change('relief_cover',true,$user);
    $year=\App\Models\SessionYear::where('name','2026')->firstOrFail();
    $teacher=\App\Models\Teacher::whereHas('user',fn($q)=>$q->where('email','teacher.a@demo.navigateed.test'))->firstOrFail();
    $grade=\App\Models\Academics\GradeLevel::where('code','5')->firstOrFail();
    $subject=\App\Models\Subject::where('name','Mathematics')->firstOrFail();
    \App\Services\Platform\Tenancy\SchoolTable::query('timetables')->delete();
    $group=\App\Models\Academics\TeachingGroup::where('name','Browser cover group')->first() ?? app(\App\Services\Academics\TeachingGroups::class)->save($user,['name'=>'Browser cover group','subject_id'=>$subject->id,'grade_level_id'=>$grade->id,'session_year_id'=>$year->id,'class_section_id'=>null,'active'=>true]);
    if (!\App\Models\Academics\TeachingAllocation::where('teaching_group_id',$group->id)->where('teacher_id',$teacher->id)->exists()) app(\App\Services\Academics\TeachingGroups::class)->dated($user,'teachers',['teacher_id'=>$teacher->id,'starts_on'=>$year->start_date,'ends_on'=>$year->end_date,'reason'=>'Synthetic browser cover'], $group->id);
    $room=\App\Models\Academics\Room::where('name','Browser cover room')->first() ?? app(\App\Services\Academics\TimetableConfiguration::class)->room($user,['name'=>'Browser cover room','capacity'=>35,'type'=>'classroom','active'=>true]);
    $period=\App\Models\Academics\BellSchedule::where('name','Browser cover bells')->first()?->periods->sole() ?? app(\App\Services\Academics\TimetableConfiguration::class)->bells($user,['name'=>'Browser cover bells','section'=>'Cover','session_year_id'=>$year->id,'periods'=>[['name'=>'C1','starts_at'=>'08:00','ends_at'=>'08:45','is_break'=>false]]])->periods->sole();
    \App\Models\Academics\TimetableLesson::firstOrCreate(['teaching_group_id'=>$group->id,'occurs_on'=>'__COVER_DATE__'],['session_year_id'=>$year->id,'term_id'=>null,'teaching_group_id'=>$group->id,'class_section_id'=>null,'teacher_id'=>$teacher->id,'room_id'=>$room->id,'bell_period_id'=>$period->id,'starts_on'=>'__COVER_DATE__','ends_on'=>'__COVER_DATE__','occurs_on'=>'__COVER_DATE__','weekday'=>__COVER_DAY__,'week_pattern'=>'all','active'=>true,'revision'=>1]);
    foreach (\App\Models\Pastoral\CoverAbsence::where('teacher_id',$teacher->id)->where('state','requested')->get() as $absence) { app(\App\Services\Pastoral\ReliefCover::class)->change($user,(int)$absence->id,$absence->revision,'cancel','Reset synthetic browser cover',(string)\Illuminate\Support\Str::uuid()); }
  });`.replaceAll('__COVER_DATE__', coverDate).replaceAll('__COVER_DAY__', String(new Date(coverDate).getUTCDay() || 7));
  execFileSync('php', ['artisan', 'tinker', '--execute', fixture], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
  for (const email of [accounts.admin, accounts.teacher2]) {
    const context = await browser.newContext({baseURL});
    await signIn(await context.newPage(), email);
    if (email === accounts.admin) adminState = await context.storageState();
    else teacherState = await context.storageState();
    await context.close();
  }
});
for (const width of [1024, 1280]) {
  test(`named absence assignment and private substitute view at ${width}`, async ({ browser, baseURL }) => {
    const adminContext = await browser.newContext({baseURL, storageState:adminState});
    const teacherContext = await browser.newContext({baseURL, storageState:teacherState});
    const page = await adminContext.newPage();
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 1000 });
    const errors = collectErrors(page, baseURL!);
    await page.goto(`/relief-cover?from=${coverDate}&to=${coverDate}`);
    const request = page.getByRole('form', { name: 'Request cover', exact: true });
    await request.getByRole('combobox', { name: 'Absent teacher', exact: true }).selectOption({ label: 'Thandi Mokoena' });
    await request.getByRole('textbox', { name: 'First absence date', exact: true }).fill(coverDate);
    await request.getByRole('textbox', { name: 'Last absence date', exact: true }).fill(coverDate);
    await request.getByLabel('Private reason (optional)', { exact: true }).fill(`Private browser cover ${width}`);
    await request.getByRole('button', { name: 'Request cover', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Relief cover saved' })).toBeVisible();
    const requestUrl = page.url();
    await page.getByRole('link', { name: /Choose substitute/ }).click();
    await page.getByRole('combobox', { name: 'Available substitute', exact: true }).selectOption({ label: 'Pieter van Wyk' });
    await page.getByRole('button', { name: 'Assign substitute', exact: true }).click();
    await expect(page.getByText('Pieter van Wyk', { exact: false }).first()).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id));
    expect(new Set(ids).size).toBe(ids.length);
    const substitutePage = await teacherContext.newPage();
    await substitutePage.goto(`/relief-cover?from=${coverDate}&to=${coverDate}`);
    await expect(substitutePage.getByText('Browser cover group', { exact: true })).toBeVisible();
    await expect(substitutePage.getByText(`Private browser cover ${width}`, { exact: false })).toHaveCount(0);
    expect(await seriousViolations(substitutePage)).toEqual([]);
    await page.goto(requestUrl);
    const cancel = page.getByRole('form', { name: 'Cancel cover request', exact: true });
    await cancel.getByRole('textbox', { name: 'Reason for this change', exact: true }).fill('Browser cover no longer needed');
    await cancel.getByRole('button', { name: 'Cancel cover request', exact: true }).click();
    await expect(page.getByText('Status: cancelled', { exact: false }).first()).toBeVisible();
    expect(errors).toEqual([]);
    await teacherContext.close(); await adminContext.close();
  });
}
