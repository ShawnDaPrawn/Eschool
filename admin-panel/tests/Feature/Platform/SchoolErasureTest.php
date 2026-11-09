<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\EraseOffboardedSchool;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolErasureCertificate;
use App\Models\Platform\SchoolOffboarding;
use App\Models\User;
use App\Services\Platform\Offboarding\OffboardingDeletionPlan;
use App\Services\Platform\Offboarding\OffboardingExport;
use App\Services\Platform\Offboarding\SchoolErasure;
use App\Services\Platform\Saas\PlatformOffboarding;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Erasing a leaving school's data (Action Plan SA.11), the last step of
 * offboarding: two approvals by different people, a cooling-off period, an
 * erasure in resumable batches that touches only the leaving school's rows,
 * and a certificate that holds no personal information.
 *
 * School B (school 2) leaves; school 1 is the install's default school and the
 * "other school", whose rows must never change.
 * engineering/specs/platform/offboarding.md.
 */
class SchoolErasureTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const LEAVING = self::SCHOOL_B_ID;

    private const OTHER = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        Storage::fake(OffboardingExport::DISK);
        Queue::fake([EraseOffboardedSchool::class]);
        $this->travelTo(CarbonImmutable::parse('2026-11-04 09:00', PlatformOffboarding::TIMEZONE));
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
    }

    public function test_schema_lookups_are_bounded_and_row_queries_keep_their_school(): void
    {
        $plan = new OffboardingDeletionPlan;
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $other = $plan->targets(self::OTHER);
            $leaving = $plan->targets(self::LEAVING);
            $plan->kept(self::OTHER);
            $plan->kept(self::LEAVING);
            $metadata = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains(strtolower($query['query']), 'information_schema.tables'));
            $this->assertCount(1, $metadata, 'Read table metadata once, not once per table per batch.');
            $this->assertSame([self::OTHER], $other['students']->getBindings());
            $this->assertSame([self::LEAVING], $leaving['students']->getBindings());
            $this->assertNotSame($other['students'], $leaving['students']);
        } finally {
            DB::disableQueryLog();
        }
    }

    private function staff(string $role, string $name = ''): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($name ?: $role), 'last_name' => 'Operator', 'email' => ($name ?: $role).'@platform.example', 'mobile' => '', 'password' => 'Synthetic-erasure-password-731!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addDays(400)]);

        return $user;
    }

    private function headOfB(): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Head', 'last_name' => 'Baobab Admin', 'email' => 'head@baobab.example', 'mobile' => '', 'password' => 'Synthetic-erasure-password-731!', 'status' => 1]));
        app(CurrentSchool::class)->run(self::LEAVING, fn () => $user->assignRole('Super Admin'));
        DB::table('school_memberships')->where('user_id', $user->id)->update(['starts_on' => '2026-01-01']);

        return $user->fresh();
    }

    private function offboarding(): SchoolOffboarding
    {
        return SchoolOffboarding::acrossSchools()->where('school_id', self::LEAVING)->latest('id')->firstOrFail();
    }

    private function url(string $path = ''): string
    {
        return '/saas/schools/'.self::LEAVING.'/offboarding'.$path;
    }

    /** Started, exported, collected, archived, asked for by Operations and approved by an Owner. Returns the offboarding. */
    private function approved(User $operations, User $owner): SchoolOffboarding
    {
        $admin = $this->headOfB();
        $this->actingAsHq($operations)->post($this->url(), ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20',
            'delete_on' => '2026-12-31', 'retention_terms' => 'Clause 12: data deleted within 60 days of termination.'])->assertRedirect()->assertSessionHasNoErrors();
        $row = $this->offboarding();
        app(CurrentSchool::class)->run(self::LEAVING, fn () => app(OffboardingExport::class)->generate((int) $row->id, (string) $row->generation_id));
        $url = $this->newBrowserSession()->actingAs($admin)->get('/school-exit')->assertOk()->viewData('offboarding')['download_url'];
        $this->get($url)->assertOk();
        $this->post('/school-exit/'.$row->id.'/receipt', ['confirm' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAsHq($operations)->post($this->url('/archive'))->assertRedirect()->assertSessionHasNoErrors();
        $this->post($this->url('/deletion'))->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAsHq($owner)->post($this->url('/deletion/approval'))->assertRedirect()->assertSessionHasNoErrors();

        return $row->fresh();
    }

    /** Runs the queued erasure in the school (queued jobs are faked; the test transaction never commits). */
    private function erase(SchoolOffboarding $row, ?int $batches = null): bool
    {
        return app(CurrentSchool::class)->run(self::LEAVING, fn (): bool => app(SchoolErasure::class)->run((int) $row->id, $batches, 900));
    }

    /**
     * Everything of one school: every row of every table with a school id
     * (hashed), its memberships, its status, and its members' accounts.
     *
     * @return array<string, string|int>
     */
    private function snapshot(int $schoolId): array
    {
        $hash = fn ($rows): string => md5(implode("\n", $rows->map(fn (object $row): string => (string) json_encode($row))->sort()->values()->all()));
        $state = [];
        foreach (SchoolOwnedTables::withSchoolId() as $table) {
            if (Schema::hasTable($table)) {
                $state[$table] = $hash(DB::table($table)->where('school_id', $schoolId)->get());
            }
        }
        $state['role_has_permissions'] = DB::table('role_has_permissions')->whereIn('role_id', DB::table('roles')->select('id')->where('school_id', $schoolId))->count();
        $state['school_memberships'] = $hash(DB::table('school_memberships')->where('school_id', $schoolId)->get());
        $state['users'] = $hash(DB::table('users')->whereIn('id', DB::table('school_memberships')->select('user_id')->where('school_id', $schoolId))->get());
        $state['status'] = (string) DB::table('schools')->where('id', $schoolId)->value('status');

        return $state;
    }

    public function test_after_two_approvals_and_the_cooling_off_period_the_school_is_erased_with_a_certificate(): void
    {
        $operations = $this->staff('operations');
        $owner = $this->staff('owner');
        $learner = User::query()->findOrFail($this->schoolB->ids['asha_user']);
        $learnerEmail = $learner->email;
        // A bursary decision record: history the database keeps immutable (a trigger refuses deletes). It refers to a learner and a school year.
        $bursary = DB::table('bursary_applications')->insertGetId(['school_id' => self::LEAVING, 'public_id' => (string) Str::uuid(), 'learner_id' => $this->schoolB->ids['ben'],
            'guardian_user_id' => $this->schoolB->ids['gina_user'] ?? $this->schoolB->ids['ben_user'], 'session_year_id' => $this->schoolB->ids['session_year'], 'state' => 'draft',
            'household' => '{"note":"synthetic"}', 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $row = $this->approved($operations, $owner);
        $this->assertSame(1, $row->dry_run_report['protected']['bursary_applications'] ?? null);
        $auditBefore = DB::table('audit_log')->where('school_id', self::LEAVING)->count();
        $contracts = DB::table('school_contracts')->where('school_id', self::LEAVING)->count();
        $plan = app(OffboardingDeletionPlan::class);
        $inScope = array_map(fn ($query): int => $query->count(), $plan->targets(self::LEAVING));

        // The cooling-off period: 72 hours after the second approval, and not before the deletion date (31 December).
        $this->assertSame('2026-12-31 00:00', $row->erasure_not_before->timezone(PlatformOffboarding::TIMEZONE)->format('Y-m-d H:i'));
        $this->actingAsHq($operations)->get($this->url())->assertOk()->assertSee('Cooling-off: the erasure can start from 2026-12-31 00:00.');
        $this->post($this->url('/erasure'), ['confirm' => 'OFF-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT)])->assertSessionHasErrors('confirm');
        $this->assertSame(SchoolOffboarding::ARCHIVED, $row->fresh()->state);

        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));
        // The reference must be typed again.
        $this->actingAsHq($operations)->post($this->url('/erasure'), ['confirm' => 'OFF-9999'])->assertSessionHasErrors('confirm');
        Queue::assertNothingPushed();
        $this->post($this->url('/erasure'), ['confirm' => PlatformOffboarding::reference((int) $row->id)])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(SchoolOffboarding::ERASING, $row->fresh()->state);
        Queue::assertPushed(EraseOffboardedSchool::class, fn (EraseOffboardedSchool $job): bool => $job->schoolId === self::LEAVING && $job->offboardingId === (int) $row->id);
        // Once started it cannot be stopped: no hold, no cancellation.
        $this->post($this->url('/legal-hold'), ['reason' => 'A late records request from the department.'])->assertStatus(409);
        $this->post($this->url('/cancel'), ['reason' => 'Changed our minds after it started.'])->assertStatus(409);

        $this->assertTrue($this->erase($row));
        $row = $row->fresh();
        $this->assertSame(SchoolOffboarding::ERASED, $row->state);

        // The school's own rows are gone, except immutable history and what it refers to.
        $certificate = SchoolErasureCertificate::acrossSchools()->where('offboarding_id', $row->id)->sole();
        $held = $certificate->certificate['held'];
        foreach ($plan->targets(self::LEAVING) as $table => $query) {
            $this->assertSame($held[$table]['rows'] ?? 0, $query->count(), $table);
            $this->assertSame($inScope[$table] - ($held[$table]['rows'] ?? 0), $certificate->certificate['erased']['rows'][$table] ?? 0, $table);
        }
        $this->assertSame(0, DB::table('school_memberships')->where('school_id', self::LEAVING)->count());
        // The immutable record stays, with the learner and school year it refers to; nothing else of theirs is held.
        $this->assertTrue(DB::table('bursary_applications')->where('id', $bursary)->exists());
        $this->assertSame(['rows' => 1, 'reason' => 'history the database keeps immutable (money, ledger and similar records)'], $held['bursary_applications']);
        $this->assertSame(1, $held['students']['rows']);
        $this->assertSame(1, $held['session_years']['rows']);
        $this->assertTrue(DB::table('students')->where('id', $this->schoolB->ids['ben'])->exists());
        $this->assertFalse(DB::table('students')->where('id', $this->schoolB->ids['asha'])->exists());
        $this->assertSame(0, DB::table('roles')->where('school_id', self::LEAVING)->count());

        // An account only this school knew is erased in place; the id stays for the kept audit log.
        $erased = DB::table('users')->where('id', $learner->id)->first();
        $this->assertSame(['Erased', 'account', 'erased-'.$learner->id.'@erased.invalid', null, 0], [$erased->first_name, $erased->last_name, $erased->email, $erased->mobile, (int) $erased->status]);
        $this->assertNotNull($erased->deleted_at);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $learner->id)->count());
        $this->assertGreaterThan(0, $certificate->certificate['erased']['accounts']);

        // Kept: the audit log (with every earlier entry), Navigate Ed's records, the archived school; the export copy is gone.
        $this->assertGreaterThan($auditBefore, DB::table('audit_log')->where('school_id', self::LEAVING)->count());
        $this->assertSame($contracts, DB::table('school_contracts')->where('school_id', self::LEAVING)->count());
        $this->assertSame('archived', DB::table('schools')->where('id', self::LEAVING)->value('status'));
        $this->assertSame([], Storage::disk(OffboardingExport::DISK)->allFiles('schools/2/offboarding'));
        $this->assertSame('discarded', $certificate->certificate['export']['copy']);

        // The certificate: counts, dates and audit entry ids; no person's name, email or id.
        $json = $certificate->body;
        foreach ([$learnerEmail, $learner->first_name.' '.$learner->last_name, $operations->email, $owner->email, 'Operator', 'another provider'] as $personal) {
            $this->assertStringNotContainsString($personal, $json);
        }
        $this->assertTrue($certificate->certificate['approvals']['different_people']);
        $this->assertSame((int) DB::table('audit_log')->where('action', 'platform_school_offboarding_deletion_approved')->value('id'), $certificate->certificate['approvals']['audit_entries']['approved']);
        $download = $this->get($this->url('/certificate'))->assertOk()->assertHeader('X-Certificate-SHA256', $certificate->certificate_sha256);
        $this->assertSame($certificate->certificate_sha256, hash('sha256', (string) $download->getContent()));
        // The download is the stored bytes exactly, kept as text, never a JSON column a database could reorder.
        $this->assertSame((string) DB::table('school_erasure_certificates')->where('id', $certificate->id)->value('body'), $download->getContent());
        $this->assertNotSame('json', Schema::getColumnType('school_erasure_certificates', 'body'));
        $this->assertSame($certificate->certificate_sha256, hash('sha256', $certificate->body));
        $this->get($this->url())->assertOk()->assertSee('Certificate '.$certificate->reference)->assertDontSee($learnerEmail);

        $actions = DB::table('audit_log')->where('school_id', self::LEAVING)->where('action', 'like', 'platform_school_offboarding_eras%')->orderBy('id')->pluck('action')->all();
        $this->assertSame(['platform_school_offboarding_erasure_started', 'platform_school_offboarding_erased'], $actions);
        // Erased is final: nothing else can happen to it, and the school cannot be offboarded again.
        $this->post($this->url('/erasure'), ['confirm' => PlatformOffboarding::reference((int) $row->id)])->assertStatus(409);
        $this->post($this->url(), ['reason' => 'A second offboarding after erasure.', 'receipt_deadline' => '2027-02-20'])->assertSessionHasErrors('reason');
        $this->put('/saas/schools/'.self::LEAVING.'/status', ['status' => 'active', 'reason' => 'Bringing the erased school back'])->assertSessionHasErrors('status');
        $this->assertSame('archived', DB::table('schools')->where('id', self::LEAVING)->value('status'));
    }

    public function test_erasure_refuses_the_wrong_role_and_changes_nothing(): void
    {
        $row = $this->approved($this->staff('operations'), $this->staff('owner'));
        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));
        $before = $this->snapshot(self::LEAVING);
        $confirm = ['confirm' => PlatformOffboarding::reference((int) $row->id)];
        foreach (['support', 'finance', 'content', 'sales'] as $role) {
            $this->actingAsHq($this->staff($role));
            $this->post($this->url('/erasure'), $confirm)->assertForbidden();
            $this->post($this->url('/erasure/resume'))->assertForbidden();
            $this->get($this->url('/certificate'))->assertForbidden();
        }
        // The leaving school's own Super Admin never reaches HQ.
        $this->newBrowserSession()->actingAs(User::query()->where('email', 'head@baobab.example')->firstOrFail());
        $this->post($this->url('/erasure'), $confirm)->assertForbidden();
        $this->get($this->url('/certificate'))->assertForbidden();

        $this->assertSame(SchoolOffboarding::ARCHIVED, $row->fresh()->state);
        $this->assertSame($before, $this->snapshot(self::LEAVING));
        Queue::assertNothingPushed();
        $this->assertSame(0, DB::table('audit_log')->where('action', 'like', 'platform_school_offboarding_eras%')->count());
    }

    public function test_erasing_one_school_changes_nothing_of_another_school(): void
    {
        $operations = $this->staff('operations');
        // Someone who belongs to both schools keeps their account and loses only the leaving school's membership.
        $both = User::query()->create(['first_name' => 'Shared', 'last_name' => 'Parent', 'email' => 'shared.parent@example.test', 'mobile' => '0820000000', 'password' => 'Synthetic-erasure-password-731!', 'status' => 1]);
        app(CurrentSchool::class)->run(self::OTHER, fn () => $both->assignRole(SchoolRoles::PARENT));
        app(CurrentSchool::class)->run(self::LEAVING, fn () => $both->assignRole(SchoolRoles::PARENT));
        DB::table('school_memberships')->where('user_id', $both->id)->update(['starts_on' => '2026-01-01']);
        $row = $this->approved($operations, $this->staff('owner'));
        $other = $this->snapshot(self::OTHER);
        $account = (array) DB::table('users')->where('id', $both->id)->first();

        // The other school has nothing to erase, and its certificate is not found.
        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));
        $this->actingAsHq($operations)->post('/saas/schools/'.self::OTHER.'/offboarding/erasure', ['confirm' => PlatformOffboarding::reference((int) $row->id)])->assertNotFound();
        $this->get('/saas/schools/'.self::OTHER.'/offboarding/certificate')->assertNotFound();

        $this->post($this->url('/erasure'), ['confirm' => PlatformOffboarding::reference((int) $row->id)])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($this->erase($row));

        $this->assertSame($other, $this->snapshot(self::OTHER));
        $this->assertSame($account, (array) DB::table('users')->where('id', $both->id)->first());
        $this->assertTrue(DB::table('school_memberships')->where('user_id', $both->id)->where('school_id', self::OTHER)->exists());
        $this->assertFalse(DB::table('school_memberships')->where('user_id', $both->id)->where('school_id', self::LEAVING)->exists());
        // The other school's people still sign in and see their school.
        $this->newBrowserSession()->actingAs($this->superAdmin())->get('/school-exit')->assertOk()->assertSee('Your school is not leaving NavigateEd');
    }

    public function test_a_reference_from_another_schools_row_stops_the_erasure_before_anything_is_deleted(): void
    {
        $operations = $this->staff('operations');
        $row = $this->approved($operations, $this->staff('owner'));
        // A school A row that (wrongly) points at one of school B's school years.
        $yearB = (int) DB::table('session_years')->where('school_id', self::LEAVING)->value('id');
        $planted = null;
        foreach (['holidays', 'terms', 'events', 'leave_masters', 'class_subjects'] as $table) {
            $id = DB::table($table)->where('school_id', self::OTHER)->whereNotNull('session_year_id')->value('id');
            if ($id !== null) {
                DB::table($table)->where('id', $id)->update(['session_year_id' => $yearB]);
                $planted = $table;
                break;
            }
        }
        $this->assertNotNull($planted, 'the fixture has a school A row with a school year');
        $before = $this->snapshot(self::LEAVING);
        $other = $this->snapshot(self::OTHER);

        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));
        $this->actingAsHq($operations)->post($this->url('/erasure'), ['confirm' => PlatformOffboarding::reference((int) $row->id)])->assertSessionHasErrors('confirm');
        $this->assertStringContainsString($planted.'.session_year_id→session_years', (string) session('errors')->first('confirm'));
        $this->assertSame(SchoolOffboarding::ARCHIVED, $row->fresh()->state);

        // Even an erasure already running stops before a batch when such a reference appears.
        DB::table('school_offboardings')->where('id', $row->id)->update(['state' => SchoolOffboarding::ERASING, 'erasure_started_at' => now()]);
        $this->assertFalse($this->erase($row->fresh()));
        $this->assertSame('blocked:'.$planted.'.session_year_id→session_years', $row->fresh()->erasure_failure_code);
        // Only the offboarding's own record and its audit entry changed.
        $unchanged = fn (array $state): array => array_diff_key($state, ['school_offboardings' => 1, 'audit_log' => 1]);
        $this->assertSame($unchanged($before), $unchanged($this->snapshot(self::LEAVING)));
        $this->assertTrue(DB::table('audit_log')->where('action', 'platform_school_offboarding_erasure_blocked')->exists());
        $this->assertSame($other, $this->snapshot(self::OTHER));
        Queue::assertNothingPushed();
    }

    public function test_another_schools_row_pointing_into_the_same_table_stops_the_erasure_and_the_schools_own_chain_erases_cleanly(): void
    {
        $operations = $this->staff('operations');
        $row = $this->approved($operations, $this->staff('owner'));
        // The leaving school's own chain of groups (a team, its squad, the squad's B side), each pointing at its parent.
        $group = fn (int $school, ?int $parent, string $name): int => DB::table('activity_groups')->insertGetId(['school_id' => $school, 'parent_id' => $parent,
            'session_year_id' => (int) DB::table('session_years')->where('school_id', $school)->value('id'), 'name' => $name, 'discipline' => 'netball', 'season' => 'winter',
            'starts_on' => '2026-04-01', 'ends_on' => '2026-08-31', 'created_at' => now(), 'updated_at' => now()]);
        $team = $group(self::LEAVING, null, 'Baobab netball');
        $squad = $group(self::LEAVING, $team, 'Baobab netball U14');
        $group(self::LEAVING, $squad, 'Baobab netball U14 B');
        // Another school's group that (wrongly) names the leaving school's team as its parent.
        $stray = $group(self::OTHER, $team, 'Jacaranda netball');
        $before = $this->snapshot(self::LEAVING);
        $other = $this->snapshot(self::OTHER);

        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));
        $confirm = ['confirm' => PlatformOffboarding::reference((int) $row->id)];
        $this->actingAsHq($operations)->post($this->url('/erasure'), $confirm)->assertSessionHasErrors('confirm');
        $this->assertStringContainsString('activity_groups.parent_id→activity_groups', (string) session('errors')->first('confirm'));
        $this->assertSame(SchoolOffboarding::ARCHIVED, $row->fresh()->state);
        $this->assertSame($before, $this->snapshot(self::LEAVING));
        $this->assertSame($other, $this->snapshot(self::OTHER));
        $this->assertSame($team, (int) DB::table('activity_groups')->where('id', $stray)->value('parent_id'));
        Queue::assertNothingPushed();

        // Once the other school's row no longer points there, the leaving school's chain erases cleanly and the other school's group is untouched.
        DB::table('activity_groups')->where('id', $stray)->update(['parent_id' => null]);
        $other = $this->snapshot(self::OTHER);
        $this->post($this->url('/erasure'), $confirm)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($this->erase($row));
        $this->assertSame(0, DB::table('activity_groups')->where('school_id', self::LEAVING)->count());
        $this->assertSame(3, SchoolErasureCertificate::acrossSchools()->where('offboarding_id', $row->id)->sole()->certificate['erased']['rows']['activity_groups']);
        $this->assertTrue(DB::table('activity_groups')->where('id', $stray)->exists());
        $this->assertSame($other, $this->snapshot(self::OTHER));
    }

    public function test_erasure_needs_two_different_people_who_still_may_approve_and_a_hold_in_the_cooling_off_period_voids_it(): void
    {
        $operations = $this->staff('operations');
        $owner = $this->staff('owner');
        $row = $this->approved($operations, $owner);
        $confirm = ['confirm' => PlatformOffboarding::reference((int) $row->id)];
        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));

        // The approvals recorded as one person's (as if tampered): refused.
        DB::table('school_offboardings')->where('id', $row->id)->update(['deletion_approved_by' => $operations->id]);
        $this->actingAsHq($operations)->post($this->url('/erasure'), $confirm)->assertStatus(409);
        DB::table('school_offboardings')->where('id', $row->id)->update(['deletion_approved_by' => $owner->id]);

        // An approver who no longer holds the role: refused.
        PlatformAdminGrant::query()->where('user_id', $owner->id)->update(['active' => false]);
        $this->post($this->url('/erasure'), $confirm)->assertStatus(409);
        PlatformAdminGrant::query()->where('user_id', $owner->id)->update(['active' => true]);

        // A legal hold during the cooling-off period voids both approvals; erasure is refused until both are given again.
        $this->post($this->url('/legal-hold'), ['reason' => 'Litigation hold from the school governing body.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($row->fresh()->erasure_not_before);
        $this->actingAsHq($this->staff('owner', 'owner2'))->delete($this->url('/legal-hold'))->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAsHq($operations)->post($this->url('/erasure'), $confirm)->assertStatus(409);
        $this->post($this->url('/deletion'))->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAsHq($owner)->post($this->url('/deletion/approval'))->assertRedirect()->assertSessionHasNoErrors();
        // A fresh cooling-off period from the new approval.
        $this->assertSame('2027-01-07 10:00', $row->fresh()->erasure_not_before->timezone(PlatformOffboarding::TIMEZONE)->format('Y-m-d H:i'));
        $this->actingAsHq($operations)->post($this->url('/erasure'), $confirm)->assertSessionHasErrors('confirm');
        $this->travelTo(CarbonImmutable::parse('2027-01-07 10:01', PlatformOffboarding::TIMEZONE));
        $this->actingAsHq($operations)->post($this->url('/erasure'), $confirm)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(SchoolOffboarding::ERASING, $row->fresh()->state);
        // Nothing is deleted until the erasure runs.
        $this->assertGreaterThan(0, DB::table('students')->where('school_id', self::LEAVING)->count());
    }

    public function test_an_interrupted_erasure_resumes_where_it_stopped_and_counts_every_row_once(): void
    {
        config(['offboarding.erasure_batch_size' => 7]);
        $operations = $this->staff('operations');
        $row = $this->approved($operations, $this->staff('owner'));
        $other = $this->snapshot(self::OTHER);
        $plan = app(OffboardingDeletionPlan::class);
        $inScope = array_map(fn ($query): int => $query->count(), $plan->targets(self::LEAVING));
        $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00', PlatformOffboarding::TIMEZONE));
        $this->actingAsHq($operations)->post($this->url('/erasure'), ['confirm' => PlatformOffboarding::reference((int) $row->id)])->assertRedirect()->assertSessionHasNoErrors();

        // The worker stops after a few batches: part erased, progress saved.
        $this->assertFalse($this->erase($row, 60));
        $row = $row->fresh();
        $this->assertSame(SchoolOffboarding::ERASING, $row->state);
        $done = array_sum($row->erasure_progress['rows']);
        $steps = count($row->erasure_progress['completed']);
        $this->assertGreaterThan(0, $done);
        $this->get($this->url())->assertOk()->assertSee('Rows erased so far: '.$done.'.');

        // Nothing happens again for a while: the scheduler resumes it from its progress.
        $this->travel(31)->minutes();
        $this->artisan('offboarding:resume-erasures')->assertSuccessful()->expectsOutput('Erasures resumed: 1');
        Queue::assertPushed(EraseOffboardedSchool::class, 2);
        $this->assertSame(2, $row->fresh()->erasure_runs);
        // HQ can resume it by hand too; a second worker at once is harmless.
        $this->post($this->url('/erasure/resume'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($this->erase($row, 60));
        $progress = $row->fresh()->erasure_progress;
        $this->assertGreaterThan($steps, count($progress['completed']));
        $this->assertGreaterThanOrEqual($done, array_sum($progress['rows']));

        $this->assertTrue($this->erase($row));
        $certificate = SchoolErasureCertificate::acrossSchools()->where('offboarding_id', $row->id)->sole()->certificate;
        // Each row counted once, whatever the interruptions.
        foreach ($inScope as $table => $count) {
            $this->assertSame($count - ($certificate['held'][$table]['rows'] ?? 0), $certificate['erased']['rows'][$table] ?? 0, $table);
        }
        $this->assertSame(3, $certificate['runs']);
        $this->assertSame($other, $this->snapshot(self::OTHER));
        $this->assertSame(1, DB::table('audit_log')->where('action', 'platform_school_offboarding_erased')->count());
        $this->assertSame(2, DB::table('audit_log')->where('action', 'platform_school_offboarding_erasure_resumed')->count());
        // Running it again after it finished does nothing.
        $this->assertTrue($this->erase($row));
        $this->assertSame(1, SchoolErasureCertificate::acrossSchools()->count());
    }

    public function test_the_retention_period_defaults_to_the_configured_days_when_neither_the_contract_nor_hq_gives_one(): void
    {
        config(['offboarding.default_retention_days' => 45]);
        $this->headOfB();
        $this->actingAsHq($this->staff('operations'));
        $this->get($this->url())->assertOk()->assertSee('45 days after the school');
        // A date without terms (or terms without a date) is still refused.
        $this->post($this->url(), ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20', 'delete_on' => '2026-12-31'])->assertSessionHasErrors('retention_terms');
        $this->post($this->url(), ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20'])->assertRedirect()->assertSessionHasNoErrors();
        $row = $this->offboarding();
        $this->assertSame(['default', '2027-01-04'], [$row->retention_source, $row->delete_on->toDateString()]);
        $this->assertStringContainsString('45 days', $row->retention_terms);
    }
}
