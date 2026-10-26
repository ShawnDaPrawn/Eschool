import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
test.beforeAll(() => {
  const fixture = String.raw`abort_unless(in_array(config('database.connections.mysql.database'), ['eschool_e2e','eschool_pastoral_test'], true),403);app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1,function(){
 $admin=\App\Models\User::where('email','admin.a@demo.navigateed.test')->firstOrFail();
 app(\App\Services\Platform\Modules\ModuleGate::class)->change('leadership_dashboard',true,$admin);
 $year=\App\Models\SessionYear::findOrFail((int)schoolSetting('session_year'));
 if(!\App\Models\Academics\AcademicLeadership::where('user_id',$admin->id)->where('kind','principal')->where('session_year_id',$year->id)->exists()) app(\App\Services\Academics\TeachingGroups::class)->dated($admin,'leadership',['user_id'=>$admin->id,'session_year_id'=>$year->id,'kind'=>'principal','grade_level_id'=>null,'starts_on'=>$year->start_date,'ends_on'=>$year->end_date,'reason'=>'Synthetic dashboard browser principal']);
 $learner=\App\Models\Students::with('user')->orderBy('id')->firstOrFail();
 $room=\App\Models\ClassSection::with('class')->findOrFail($learner->class_section_id);
 $grade=\App\Models\Academics\GradeLevel::where('code','5')->firstOrFail();$room->class->forceFill(['grade_level_id'=>$grade->id])->save();
 \App\Models\People\Enrolment::firstOrCreate(['learner_id'=>$learner->id,'session_year_id'=>$year->id],['class_section_id'=>$room->id,'starts_on'=>$year->start_date,'ends_on'=>null,'legacy_dates'=>false]);
 app(\App\Services\Platform\Modules\ModuleGate::class)->change('fees',true,$admin);
 \App\Models\PaymentTransaction::where('session_year_id',$year->id)->delete();\App\Models\FeesPaid::where('session_year_id',$year->id)->delete();
 $date=app(\App\Services\Academics\AcademicAuthority::class)->today();
 \App\Models\PaymentTransaction::forceCreate(['student_id'=>$learner->id,'class_id'=>$room->class_id,'parent_id'=>$learner->father_id ?: $learner->guardian_id,'session_year_id'=>$year->id,'mode'=>0,'type_of_fee'=>0,'payment_status'=>1,'date'=>$date,'total_amount_cents'=>12345]);
 \App\Models\FeesPaid::forceCreate(['student_id'=>$learner->id,'class_id'=>$room->class_id,'session_year_id'=>$year->id,'date'=>$date,'is_fully_paid'=>0,'total_amount_cents'=>12345]);
 foreach([0=>'present',1=>'first_half'] as $days=>$state){$date=now()->subDays($days)->toDateString();$register=\App\Models\Academics\AttendanceRegister::firstOrCreate(['session_year_id'=>$year->id,'on_date'=>$date,'scope_key'=>'homeroom:'.$room->id],['class_section_id'=>$room->id,'grade_level_id'=>$grade->id,'label'=>'Synthetic dashboard register','revision'=>1]);\App\Models\Academics\AttendanceEntry::updateOrCreate(['attendance_register_id'=>$register->id,'learner_id'=>$learner->id],['state'=>$state,'source'=>'manual','revision'=>1,'captured_by'=>$admin->id]);}
 });`;
  execFileSync('php', ['artisan', 'tinker', '--execute', fixture], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});
for (const width of [390, 1024, 1280]) {
  test(`principal grade trends fit ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/leadership-dashboard');
    await expect(page.getByRole('heading', { name: 'Leadership dashboard', exact: true })).toBeVisible();
    await page.getByRole('spinbutton', { name: 'Weeks to show', exact: true }).fill('2');
    await page.getByRole('button', { name: 'View trends', exact: true }).click();
    await expect(page.getByRole('spinbutton', { name: 'Weeks to show', exact: true })).toHaveValue('2');
    await expect(page.getByRole('heading', { name: 'Grade 5', exact: true }).first()).toBeVisible();
    await expect(page.getByText('Unavailable', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Recorded attendance', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('123.45', { exact: true })).toBeVisible();
    await expect(page.getByText('Fee collection shows current recorded paid receipts after corrections, by receipt date. Unreconciled receipts are unavailable.', { exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id));
    expect(new Set(ids).size).toBe(ids.length);
    expect(errors).toEqual([]);
  });
}
test('ordinary teacher cannot open principal trends', async ({ page }) => {
  await signIn(page, accounts.teacher);
  const response = await page.goto('/leadership-dashboard');
  expect(response?.status()).toBe(403);
  await expect(page.getByText('Recorded attendance', { exact: true })).toHaveCount(0);
});
