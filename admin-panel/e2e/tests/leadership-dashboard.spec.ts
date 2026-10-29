import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let leadershipYear: string;
test.beforeAll(() => {
  const fixture = String.raw`abort_unless(in_array(config('database.connections.mysql.database'), ['eschool_e2e','eschool_pastoral_test'], true),403);
$yearId=app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1,function(){
 $admin=\App\Models\User::where('email','admin.a@demo.navigateed.test')->firstOrFail();
 app(\App\Services\Platform\Modules\ModuleGate::class)->change('leadership_dashboard',true,$admin);
 app(\App\Services\Platform\Modules\ModuleGate::class)->change('fees',true,$admin);
 $year=\App\Models\SessionYear::create(['name'=>'Leadership browser '.\Illuminate\Support\Str::uuid(),'start_date'=>now()->startOfYear()->toDateString(),'end_date'=>now()->endOfYear()->toDateString()]);
 app(\App\Services\Academics\TeachingGroups::class)->dated($admin,'leadership',['user_id'=>$admin->id,'session_year_id'=>$year->id,'kind'=>'principal','grade_level_id'=>null,'starts_on'=>$year->start_date,'ends_on'=>$year->end_date,'reason'=>'Synthetic dashboard browser principal']);
 $learner=\App\Models\Students::with('user')->whereNotNull('father_id')->orderBy('id')->firstOrFail();
 $room=\App\Models\ClassSection::with('class')->findOrFail($learner->class_section_id);
 $grade=\App\Models\Academics\GradeLevel::where('code','5')->firstOrFail();$room->class->forceFill(['grade_level_id'=>$grade->id])->save();
 \App\Models\People\Enrolment::create(['learner_id'=>$learner->id,'session_year_id'=>$year->id,'class_section_id'=>$room->id,'starts_on'=>$year->start_date,'ends_on'=>null,'legacy_dates'=>false]);
 $parent=\App\Models\Parents::findOrFail($learner->father_id);
 \App\Models\Platform\GuardianLink::updateOrCreate(['learner_id'=>$learner->id,'guardian_user_id'=>$parent->user_id],['status'=>'active','relationship'=>'father','can_see_fees'=>true]);
 $account=app(\App\Services\Money\FeeAccounts::class)->save($admin,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>0,'name'=>'Synthetic leadership family','currency'=>'ZAR','session_year_id'=>$year->id,'learner_ids'=>[$learner->id],'holders'=>[['user_id'=>$parent->user_id,'share_percent'=>'100']]]);
 app(\App\Services\Money\FeePlans::class)->save($admin,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>0,'session_year_id'=>$year->id,'name'=>'Synthetic leadership tuition','amount'=>'123.45','currency'=>'ZAR','scope'=>'class','target_id'=>$room->class_id,'state'=>'active']);
 $date=app(\App\Services\Academics\AcademicAuthority::class)->today();
 $run=app(\App\Services\Money\BillRuns::class)->prepare($admin,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'session_year_id'=>$year->id,'as_of'=>$date,'due_on'=>$date,'tax_treatment'=>'no_vat']);
 app(\App\Services\Money\BillRuns::class)->approve($admin,$run->id,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>1]);
 app(\App\Services\Money\BillRuns::class)->post($admin,$run->id,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>2,'confirmed'=>true]);
 $policy=\App\Models\Money\FeePaymentPolicy::first();
 app(\App\Services\Money\FeePaymentSettings::class)->save($admin,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>(int)($policy?->revision??0),'allocation_order'=>'oldest_first']);
 $payment=app(\App\Services\Money\FeePayments::class)->record($admin,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'fee_account_id'=>$account->id,'holder_user_id'=>$parent->user_id,'pinned_learner_id'=>$learner->id,'method'=>'cash','source_reference'=>'LEADERSHIP-'.\Illuminate\Support\Str::uuid(),'amount'=>'123.45','received_on'=>$date,'confirmed'=>true]);
 $allocation=app(\App\Services\Money\FeeAllocations::class)->preview($admin,$payment->id);
 app(\App\Services\Money\FeeAllocations::class)->post($admin,$payment->id,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'fingerprint'=>$allocation['fingerprint'],'confirmed'=>true]);
 foreach([0=>'present',1=>'first_half'] as $days=>$state){$on=now()->subDays($days)->toDateString();$register=\App\Models\Academics\AttendanceRegister::create(['session_year_id'=>$year->id,'on_date'=>$on,'scope_key'=>'homeroom:'.$room->id,'class_section_id'=>$room->id,'grade_level_id'=>$grade->id,'label'=>'Synthetic dashboard register','revision'=>1]);\App\Models\Academics\AttendanceEntry::create(['attendance_register_id'=>$register->id,'learner_id'=>$learner->id,'state'=>$state,'source'=>'manual','revision'=>1,'captured_by'=>$admin->id]);}
 return $year->id;
});echo 'LEADERSHIP_YEAR='.$yearId;`;
  const output = execFileSync('php', ['artisan', 'tinker', '--execute', fixture], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
  const match = /LEADERSHIP_YEAR=(\d+)/.exec(output);
  if (!match?.[1]) throw new Error('The canonical leadership fixture did not return its school year.');
  leadershipYear = match[1];
});
for (const width of [390, 1024, 1280]) {
  test(`principal grade trends fit ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/leadership-dashboard?session_year_id=' + leadershipYear);
    await expect(page.getByRole('heading', { name: 'Leadership dashboard', exact: true })).toBeVisible();
    await page.getByRole('spinbutton', { name: 'Weeks to show', exact: true }).fill('2');
    await page.getByRole('button', { name: 'View trends', exact: true }).click();
    await expect(page.getByRole('spinbutton', { name: 'Weeks to show', exact: true })).toHaveValue('2');
    await expect(page.getByRole('heading', { name: 'Grade 5', exact: true }).first()).toBeVisible();
    await expect(page.getByText('Unavailable', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Recorded attendance', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('ZAR 123.45', { exact: true })).toBeVisible();
    await expect(page.getByText('Allocated learner fees show current applied receipts by receipt date, after credit releases. Held credit and family charges are excluded; mixed currencies or unreconciled amounts are unavailable.', { exact: true })).toBeVisible();
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
