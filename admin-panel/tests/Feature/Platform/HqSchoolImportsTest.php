<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\ClassSchool;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformSchoolImport;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\People\SchoolImports;
use App\Services\Platform\Saas\PlatformSchoolImports;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Onboarding imports run by Navigate Ed Operations on a school's behalf
 * (Action Plan SA.11): preview, the school leader's approval, apply, and
 * everything that refuses. engineering/specs/platform/hq-imports.md.
 */
class HqSchoolImportsTest extends TestCase
{
    use UsesTwoSchools;

    private const CSV = "Grade,Letter\n8,A\n8,B";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-imports-password-614!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addDays(30)]);

        return $user;
    }

    /** A Super Admin of one school only. */
    private function leader(int $school, string $name): User
    {
        return app(CurrentSchool::class)->run($school, function () use ($name): User {
            $user = User::query()->create(['first_name' => $name, 'last_name' => 'Leader', 'email' => mb_strtolower($name).'@school.example', 'mobile' => '', 'password' => 'Synthetic-leader-password-614!', 'status' => 1]);
            $user->assignRole('Super Admin');

            return $user;
        });
    }

    /** Operations check the file for School A and send it to the leader; returns the import's id. */
    private function send(User $operator, string $csv = self::CSV, ?User $approver = null): int
    {
        $approver ??= $this->superAdmin();
        $this->actingAs($operator);
        $check = $this->post('/saas/schools/1/imports/preview', ['kind' => 'homerooms', 'approver_id' => $approver->id, 'csv' => $csv])->assertOk()->viewData('check');
        $this->post('/saas/schools/1/imports', ['kind' => 'homerooms', 'approver_id' => $approver->id, 'csv' => $csv, 'mapped' => 1,
            'mapping' => $check['mapping'], 'fingerprint' => $check['fingerprint']])->assertRedirect()->assertSessionHasNoErrors();

        return (int) PlatformSchoolImport::acrossSchools()->max('id');
    }

    private function import(int $id): PlatformSchoolImport
    {
        return PlatformSchoolImport::acrossSchools()->findOrFail($id);
    }

    private function grade8(int $school): int
    {
        return ClassSchool::query()->withoutGlobalScopes()->where('school_id', $school)->where('name', 'Grade 8')->count();
    }

    private function approve(User $leader, int $id): void
    {
        $this->actingAs($leader)->post('/hq-imports/'.$id.'/approve', ['reason' => 'Checked against the SA-SAMS class list.'])
            ->assertRedirect('/hq-imports/'.$id)->assertSessionHasNoErrors();
    }

    /** @return list<string> the audit steps recorded for an import */
    private function steps(int $id): array
    {
        return DB::table('audit_log')->where('subject_type', (new PlatformSchoolImport)->getMorphClass())->where('subject_id', $id)->orderBy('id')->pluck('action')->all();
    }

    public function test_operations_preview_send_and_apply_an_import_the_school_approved(): void
    {
        $operator = $this->staff('operations');
        $leader = $this->superAdmin();
        $classesB = ClassSchool::query()->withoutGlobalScopes()->where('school_id', self::SCHOOL_B_ID)->count();

        $this->actingAs($operator)->get('/saas/schools/1/imports')->assertOk()->assertSee('No imports yet.')->assertSee(trim($leader->first_name.' '.$leader->last_name));
        // The preview checks every row and writes nothing.
        $this->post('/saas/schools/1/imports/preview', ['kind' => 'homerooms', 'approver_id' => $leader->id, 'csv' => self::CSV])
            ->assertOk()->assertSee('Will be imported')->assertSee('Send 2 rows to');
        $this->assertSame(0, $this->grade8(1));
        $this->assertSame(0, PlatformSchoolImport::acrossSchools()->count());
        $preview = DB::table('audit_log')->where('action', 'platform_import_previewed')->sole();
        $this->assertSame(1, (int) $preview->school_id);
        $this->assertSame(hash('sha256', self::CSV), json_decode($preview->after, true)['file_sha256']);

        $id = $this->send($operator);
        $import = $this->import($id);
        $this->assertSame([1, PlatformSchoolImport::AWAITING, (int) $leader->id], [$import->school_id, $import->status, $import->approver_id]);
        // Stored encrypted: the raw column never holds the file.
        $raw = (string) DB::table('platform_school_imports')->where('id', $id)->value('contents');
        $this->assertStringNotContainsString('8,A', $raw);
        $this->assertSame(self::CSV, Crypt::decryptString($raw));
        $this->assertSame(1, DB::table('notifications')->where('type', PlatformSchoolImports::NOTICE)->where('school_id', 1)->count());

        // Nothing is applied before the school approves.
        $this->post('/saas/imports/'.$id.'/apply')->assertSessionHasErrors('status');
        $this->assertSame(0, $this->grade8(1));

        // The leader sees the rows and every step, and approves with a reason.
        $this->actingAs($leader)->get('/hq-imports')->assertOk()->assertSee('Homerooms')->assertSee('Waiting for the school&#039;s approval', false);
        $this->get('/hq-imports/'.$id)->assertOk()->assertSee('Grade 8 A')->assertSee('Your decision')->assertSee('Navigate Ed checked the file (nothing written)');
        $this->approve($leader, $id);
        $this->assertSame([PlatformSchoolImport::APPROVED, hash('sha256', self::CSV)], [$this->import($id)->status, $this->import($id)->approved_sha256]);

        $this->actingAs($operator)->post('/saas/imports/'.$id.'/apply')->assertRedirect('/saas/imports/'.$id)->assertSessionHasNoErrors();
        $this->assertSame(1, $this->grade8(1));
        $this->assertSame(2, DB::table('class_sections')->where('class_id', ClassSchool::query()->withoutGlobalScopes()->where('school_id', 1)->where('name', 'Grade 8')->value('id'))->count());
        $this->assertSame($classesB, ClassSchool::query()->withoutGlobalScopes()->where('school_id', self::SCHOOL_B_ID)->count());
        $import = $this->import($id);
        $this->assertSame([PlatformSchoolImport::APPLIED, null, null, ['created' => 2]], [$import->status, $import->contents, $import->preview, $import->result]);
        $this->assertNull(DB::table('platform_school_imports')->where('id', $id)->value('contents'));
        $this->assertSame(['platform_import_requested', 'platform_import_approved', 'platform_import_applied'], $this->steps($id));
        $this->assertSame([1], DB::table('audit_log')->where('action', 'like', 'platform\_import\_%')->distinct()->pluck('school_id')->map(fn ($s) => (int) $s)->all());
        $this->assertSame(1, (int) DB::table('audit_log')->where('action', 'people.onboarding_import_committed')->sole()->school_id);

        $this->get('/saas/audit?school=1')->assertOk()->assertSee('Import applied for the school')->assertSee('Import approved by the school')->assertSee('Checked against the SA-SAMS class list.');
        $this->get('/saas/imports/'.$id)->assertOk()->assertSee('Imported')->assertDontSee('Apply the import');
        $this->actingAs($leader)->get('/hq-imports/'.$id)->assertOk()->assertSee('Imported by Navigate Ed')->assertSee('Checked against the SA-SAMS class list.')->assertDontSee('Your decision');
    }

    public function test_other_platform_roles_cannot_preview_send_apply_or_cancel(): void
    {
        $id = $this->send($this->staff('operations'));
        $this->approve($this->superAdmin(), $id);
        $audit = DB::table('audit_log')->where('action', 'like', 'platform\_import\_%')->count();

        foreach (['support', 'finance', 'content', 'sales'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get('/saas/schools/1/imports')->assertForbidden();
            $this->post('/saas/schools/1/imports/preview', ['kind' => 'homerooms', 'approver_id' => $this->superAdmin()->id, 'csv' => self::CSV])->assertForbidden();
            $this->post('/saas/schools/1/imports', ['kind' => 'homerooms', 'approver_id' => $this->superAdmin()->id, 'csv' => self::CSV, 'mapped' => 1, 'fingerprint' => str_repeat('a', 64)])->assertForbidden();
            $this->get('/saas/imports/'.$id)->assertForbidden();
            $this->post('/saas/imports/'.$id.'/apply')->assertForbidden();
            $this->post('/saas/imports/'.$id.'/cancel', ['reason' => 'Should never be cancelled'])->assertForbidden();
        }

        $this->assertSame(PlatformSchoolImport::APPROVED, $this->import($id)->status);
        $this->assertSame(1, PlatformSchoolImport::acrossSchools()->count());
        $this->assertSame(0, $this->grade8(1));
        $this->assertSame($audit, DB::table('audit_log')->where('action', 'like', 'platform\_import\_%')->count());
    }

    public function test_school_staff_cannot_use_the_hq_import_routes(): void
    {
        $id = $this->send($this->staff('operations'));
        $this->approve($this->superAdmin(), $id);

        foreach ([$this->superAdmin(), $this->leader(self::SCHOOL_B_ID, 'Baobab'), User::query()->findOrFail($this->schoolA->ids['tara_user'])] as $user) {
            $this->actingAs($user);
            $this->get('/saas/schools/1/imports')->assertForbidden();
            $this->post('/saas/schools/1/imports/preview', ['kind' => 'homerooms', 'approver_id' => $user->id, 'csv' => self::CSV])->assertForbidden();
            $this->get('/saas/imports/'.$id)->assertForbidden();
            $this->post('/saas/imports/'.$id.'/apply')->assertForbidden();
            $this->post('/saas/imports/'.$id.'/cancel', ['reason' => 'Should never be cancelled'])->assertForbidden();
        }

        $this->assertSame(PlatformSchoolImport::APPROVED, $this->import($id)->status);
        $this->assertSame(0, $this->grade8(1));
    }

    public function test_only_the_named_leader_of_the_target_school_decides(): void
    {
        $operator = $this->staff('operations');
        $baobab = $this->leader(self::SCHOOL_B_ID, 'Baobab');
        // A leader of School B cannot be named to approve School A's import: nothing is stored.
        $this->actingAs($operator)->post('/saas/schools/1/imports/preview', ['kind' => 'homerooms', 'approver_id' => $baobab->id, 'csv' => self::CSV])
            ->assertStatus(422)->assertSee('Choose a leader at this school who may import homerooms.');
        $this->assertSame(0, PlatformSchoolImport::acrossSchools()->count());

        $id = $this->send($operator);
        $audit = DB::table('audit_log')->where('action', 'like', 'platform\_import\_%')->count();

        // School B's leader: School A's import is not found, and their own list is empty.
        $this->actingAs($baobab)->get('/hq-imports')->assertOk()->assertSee('Navigate Ed has not prepared any imports for the school.');
        $this->get('/hq-imports/'.$id)->assertNotFound();
        $this->post('/hq-imports/'.$id.'/approve', ['reason' => 'Approving another school\'s import'])->assertNotFound();
        $this->post('/hq-imports/'.$id.'/decline', ['reason' => 'Declining another school\'s import'])->assertNotFound();

        // Another leader at School A sees it but is not the one asked.
        $other = $this->leader(1, 'Other');
        $this->actingAs($other)->get('/hq-imports/'.$id)->assertOk()->assertDontSee('Your decision');
        $this->post('/hq-imports/'.$id.'/approve', ['reason' => 'Approving in someone else\'s place'])->assertForbidden();
        // A teacher may not import, so cannot open it at all.
        $this->actingAs(User::query()->findOrFail($this->schoolA->ids['tara_user']))->get('/hq-imports/'.$id)->assertForbidden();
        $this->post('/hq-imports/'.$id.'/approve', ['reason' => 'A teacher approving it'])->assertForbidden();

        $this->assertSame(PlatformSchoolImport::AWAITING, $this->import($id)->status);
        $this->assertNotNull($this->import($id)->contents);
        $this->assertSame($audit, DB::table('audit_log')->where('action', 'like', 'platform\_import\_%')->count());

        // The leader asked declines it: the file is deleted and it can never be applied.
        $this->actingAs($this->superAdmin())->post('/hq-imports/'.$id.'/decline', ['reason' => 'We will import homerooms ourselves.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([PlatformSchoolImport::DECLINED, null], [$this->import($id)->status, $this->import($id)->contents]);
        $this->actingAs($operator)->post('/saas/imports/'.$id.'/apply')->assertSessionHasErrors('status');
        $this->assertSame(0, $this->grade8(1));
    }

    public function test_a_file_changed_after_approval_is_never_applied(): void
    {
        $operator = $this->staff('operations');
        $id = $this->send($operator);
        $this->approve($this->superAdmin(), $id);
        // The stored file is replaced after the school approved it.
        DB::table('platform_school_imports')->where('id', $id)->update(['contents' => Crypt::encryptString("Grade,Letter\n9,A")]);

        $this->actingAs($operator)->post('/saas/imports/'.$id.'/apply')->assertSessionHasErrors('status');
        $this->assertSame(0, $this->grade8(1));
        $this->assertSame(0, ClassSchool::query()->withoutGlobalScopes()->where('name', 'Grade 9')->count());
        $import = $this->import($id);
        $this->assertSame([PlatformSchoolImport::VOID, null], [$import->status, $import->contents]);
        $stopped = DB::table('audit_log')->where('action', 'platform_import_stopped')->sole();
        $this->assertSame('file_changed', json_decode($stopped->after, true)['stopped']);
        $this->assertSame(1, (int) $stopped->school_id);
        $this->post('/saas/imports/'.$id.'/apply')->assertSessionHasErrors('status');
    }

    public function test_school_records_changed_after_approval_stop_the_import(): void
    {
        $operator = $this->staff('operations');
        $id = $this->send($operator);
        $this->approve($this->superAdmin(), $id);
        // The school adds Grade 8 A itself before Navigate Ed applies the file.
        app(CurrentSchool::class)->run(1, fn () => app(SchoolImports::class)->commit($this->superAdmin(), 'homerooms', "Grade,Letter\n8,A", ['grade' => 'Grade', 'letter' => 'Letter'], [],
            app(SchoolImports::class)->check($this->superAdmin(), 'homerooms', "Grade,Letter\n8,A", null, [])['fingerprint']));
        $sections = DB::table('class_sections')->count();

        $this->actingAs($operator)->post('/saas/imports/'.$id.'/apply')->assertSessionHasErrors('status');
        $this->assertSame($sections, DB::table('class_sections')->count());
        $this->assertSame(PlatformSchoolImport::VOID, $this->import($id)->status);
        $this->assertSame('records_changed', json_decode((string) DB::table('audit_log')->where('action', 'platform_import_stopped')->sole()->after, true)['stopped']);
    }

    public function test_an_import_not_applied_in_time_expires_and_its_file_is_deleted(): void
    {
        $operator = $this->staff('operations');
        $waiting = $this->send($operator);
        $approved = $this->send($operator, "Grade,Letter\n10,A");
        $this->approve($this->superAdmin(), $approved);
        $this->assertSame(now()->addDays(PlatformSchoolImports::EXPIRY_DAYS)->toDateString(), $this->import($waiting)->expires_at->toDateString());

        $this->travel(PlatformSchoolImports::EXPIRY_DAYS)->days();
        $this->travel(1)->hours();
        // Approving after the date is refused, and expires it at once.
        $this->actingAs($this->superAdmin())->post('/hq-imports/'.$waiting.'/approve', ['reason' => 'Approving too late'])->assertSessionHasErrors('status');
        $this->assertSame([PlatformSchoolImport::EXPIRED, null, null], [$this->import($waiting)->status, $this->import($waiting)->contents, $this->import($waiting)->preview]);

        // The hourly clean-up (no one signed in) expires the approved one that was never applied.
        Auth::forgetGuards();
        $this->artisan('platform:expire-school-imports')->assertSuccessful();
        $this->assertSame([PlatformSchoolImport::EXPIRED, null], [$this->import($approved)->status, $this->import($approved)->contents]);
        $this->assertNull(DB::table('audit_log')->where('action', 'platform_import_expired')->where('subject_id', $approved)->sole()->actor_id);

        $this->actingAs($operator)->post('/saas/imports/'.$approved.'/apply')->assertSessionHasErrors('status');
        $this->assertSame(0, ClassSchool::query()->withoutGlobalScopes()->whereIn('name', ['Grade 8', 'Grade 10'])->count());
        $this->get('/saas/schools/1/imports')->assertOk()->assertSee('Expired');
    }

    public function test_operations_cancel_an_open_import_with_a_reason(): void
    {
        $operator = $this->staff('operations');
        $id = $this->send($operator);

        $this->actingAs($operator)->post('/saas/imports/'.$id.'/cancel', ['reason' => 'x'])->assertSessionHasErrors('reason');
        $this->post('/saas/imports/'.$id.'/cancel', ['reason' => 'The school sent a corrected list.'])->assertRedirect('/saas/imports/'.$id)->assertSessionHasNoErrors();
        $this->assertSame([PlatformSchoolImport::CANCELLED, null, 'The school sent a corrected list.'], [$this->import($id)->status, $this->import($id)->contents, $this->import($id)->close_reason]);
        $this->actingAs($this->superAdmin())->get('/hq-imports/'.$id)->assertOk()->assertSee('Cancelled by Navigate Ed')->assertSee('The school sent a corrected list.');
        $this->post('/hq-imports/'.$id.'/approve', ['reason' => 'Approving a cancelled import'])->assertSessionHasErrors('status');
    }
}
