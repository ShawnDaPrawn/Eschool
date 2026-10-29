import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let leadershipFixture: { year: number; finance: string; teacher: string };
test.beforeAll(() => {
  const fixture = String.raw`abort_unless(in_array(config('database.connections.mysql.database'), ['eschool_e2e','eschool_pastoral_test'], true),403);
$data=require base_path('e2e/support/fee-payments-fixture.php');
$data=app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run($data['school'],function()use($data){
 $actor=\App\Models\User::where('email',$data['finance'])->firstOrFail();$actor->assignRole('Super Admin');$actor->unsetRelation('roles');
 app(\App\Services\Platform\Modules\ModuleGate::class)->change('leadership_dashboard',true,$actor);
 $grade=\App\Models\Academics\GradeLevel::where('code','5')->firstOrFail();
 $classIds=\App\Models\Students::whereIn('id',array_column($data['learners'],'id'))->pluck('class_id');
 \App\Models\ClassSchool::whereIn('id',$classIds)->update(['grade_level_id'=>$grade->id]);
 app(\App\Services\Money\FeePaymentSettings::class)->save($actor,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>0,'allocation_order'=>'oldest_first']);
 $payment=app(\App\Services\Money\FeePayments::class)->record($actor,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'fee_account_id'=>$data['account'],'holder_user_id'=>$data['parents'][0],'pinned_learner_id'=>null,'method'=>'cash','source_reference'=>'LEADERSHIP-'.\Illuminate\Support\Str::uuid(),'amount'=>'4.00','received_on'=>$data['date'],'confirmed'=>true]);
 $allocation=app(\App\Services\Money\FeeAllocations::class)->preview($actor,$payment->id);
 app(\App\Services\Money\FeeAllocations::class)->post($actor,$payment->id,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'fingerprint'=>$allocation['fingerprint'],'confirmed'=>true]);
 $learner=\App\Models\Students::findOrFail($data['learners'][0]['id']);
 foreach([0=>'present',1=>'first_half'] as $days=>$state){$on=\Carbon\CarbonImmutable::parse($data['date'])->subDays($days)->toDateString();$register=\App\Models\Academics\AttendanceRegister::create(['session_year_id'=>$data['year'],'on_date'=>$on,'scope_key'=>'homeroom:'.$learner->class_section_id,'class_section_id'=>$learner->class_section_id,'grade_level_id'=>$grade->id,'label'=>'Synthetic dashboard register','revision'=>1]);\App\Models\Academics\AttendanceEntry::create(['attendance_register_id'=>$register->id,'learner_id'=>$learner->id,'state'=>$state,'source'=>'manual','revision'=>1,'captured_by'=>$actor->id]);}
 $reader=app(\App\Services\Leadership\LeadershipFeeCollections::class);
 $snapshot=$reader->read($actor,$data['year'],$data['date'],$data['date']);
 $actual=$reader->amount($snapshot,(int)$grade->id,$data['date'],$data['date']);
 if($actual!==400){throw new \RuntimeException('Leadership projection diagnostic: '.json_encode(['actual'=>$actual,'expected'=>400,'school'=>$data['school'],'year'=>$data['year'],'date'=>$data['date'],'grade'=>$grade->id,'allocated'=>$allocation['amount_cents'],'snapshot'=>$snapshot]));}
 return $data;
});echo json_encode($data);`;
  try {
    leadershipFixture = JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', fixture], {
      cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
      env: { ...process.env, BILLING_FIXTURE_ACTION: 'prepare', BILLING_FIXTURE_SCHOOL: '0' },
    }).trim());
  } catch (error) {
    const failure = error as { stdout?: unknown; stderr?: unknown };
    throw new Error(String(error) + '\n' + String(failure.stdout ?? '') + String(failure.stderr ?? ''));
  }
});
for (const width of [390, 1024, 1280]) {
  test(`school leadership grade trends fit ${width}px`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width, height: 1000 });
    await signIn(page, leadershipFixture.finance);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/leadership-dashboard?session_year_id=' + leadershipFixture.year);
    await expect(page.getByRole('heading', { name: 'Leadership dashboard', exact: true })).toBeVisible();
    await page.getByRole('spinbutton', { name: 'Weeks to show', exact: true }).fill('2');
    await page.getByRole('button', { name: 'View trends', exact: true }).click();
    await expect(page.getByRole('spinbutton', { name: 'Weeks to show', exact: true })).toHaveValue('2');
    await expect(page.getByRole('heading', { name: 'Grade 5', exact: true }).first()).toBeVisible();
    await expect(page.getByText('Unavailable', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Recorded attendance', { exact: true }).first()).toBeVisible();
    const collected = page.getByText('Allocated learner fees', { exact: true }).first().locator('xpath=following-sibling::dd[1]');
    console.info('Leadership rendered diagnostic:', page.url(), await collected.innerText());
    await expect(collected).toHaveText('ZAR 4.00');
    await expect(page.getByText('Allocated learner fees show current applied receipts by receipt date, after credit releases. Held credit and family charges are excluded; mixed currencies or unreconciled amounts are unavailable.', { exact: true })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id));
    expect(new Set(ids).size).toBe(ids.length);
    expect(errors).toEqual([]);
  });
}
test('ordinary teacher cannot open principal trends', async ({ page }) => {
  await signIn(page, leadershipFixture.teacher);
  const response = await page.goto('/leadership-dashboard');
  expect(response?.status()).toBe(403);
  await expect(page.getByText('Recorded attendance', { exact: true })).toHaveCount(0);
});
