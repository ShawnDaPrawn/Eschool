<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\SchoolJoinInvitationMail;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolJoinInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use App\Services\People\SchoolImports;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Identity\SchoolJoinInvitations;
use App\Services\Platform\Identity\SharedAccounts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * A school gives a role to someone who already has an account elsewhere: an
 * invitation they accept while signed in as that account (Action Plan 1.3).
 * engineering/specs/platform/existing-account-invitations.md.
 */
class ExistingAccountInvitationsTest extends TestCase
{
    use UsesTwoSchools;

    private const PASSWORD = 'Synthetic-own-password-4417!';

    private const B = self::SCHOOL_B_ID;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    // ---- helpers ----

    /** School A's Tara, a teacher there, with a password only she knows. */
    private function tara(): User
    {
        $tara = User::acrossSchools()->findOrFail($this->schoolA->ids['tara_user']);
        $tara->forceFill(['password' => Hash::make(self::PASSWORD)])->save();

        return $tara->fresh();
    }

    /** School B's Super Admin. */
    private function adminB(): User
    {
        return app(CurrentSchool::class)->run(self::B, function () {
            $admin = User::acrossSchools()->firstOrCreate(['email' => 'head@baobab.example'], ['first_name' => 'Baobab', 'last_name' => 'Head', 'mobile' => '', 'password' => Hash::make(self::PASSWORD), 'status' => 1]);
            $admin->assignRole('Super Admin');
            app(MembershipService::class)->forget($admin);

            return $admin->fresh();
        });
    }

    /** @return array<string, string> the teacher form */
    private function teacherForm(string $email, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Typed', 'last_name' => 'Name', 'gender' => 'Female', 'email' => $email, 'mobile' => '0821234567',
            'dob' => '1990-04-12', 'qualification' => 'PGCE', 'current_address' => '1 Road', 'permanent_address' => '1 Road',
        ], $overrides);
    }

    private function addTeacherAtB(string $email, array $overrides = []): array
    {
        $response = $this->actingAs($this->adminB(), 'web')->post(route('teachers.store'), $this->teacherForm($email, $overrides))->assertOk();
        auth()->forgetGuards();

        return $response->json();
    }

    private function invitationAtB(User $person): SchoolJoinInvitation
    {
        return SchoolJoinInvitation::acrossSchools()->where('school_id', self::B)->where('user_id', $person->id)->latest('id')->firstOrFail();
    }

    /** The emailed link's path. Tests never commit, so the after-commit send is replayed here. */
    private function emailedPath(User $person): string
    {
        $this->assertTrue(app(SchoolJoinInvitations::class)->deliver($this->invitationAtB($person)), 'the invitation can be delivered');
        $job = Queue::pushed(SendMail::class)->last(fn ($job) => $job->mailable instanceof SchoolJoinInvitationMail && $job->mailable->to[0]['address'] === $person->email);
        $this->assertNotNull($job, 'no invitation was queued for '.$person->email);
        $this->assertSame('Baobab High', $job->mailable->schoolName);

        return (string) parse_url($job->mailable->invitationUrl, PHP_URL_PATH);
    }

    private function pathOf(SchoolJoinInvitation $invitation): string
    {
        return (string) parse_url(app(SchoolJoinInvitations::class)->url((string) $invitation->encrypted_token), PHP_URL_PATH);
    }

    /** Tara invited to School B with $role and accepted. */
    private function joinB(User $tara, ?Role $role = null): void
    {
        if ($role === null) {
            $this->addTeacherAtB($tara->email);
        } else {
            $form = ['role_id' => $role->id, 'first_name' => 'Typed', 'last_name' => 'Name', 'email' => $tara->email, 'gender' => 'Female', 'mobile' => '0821234567', 'dob' => '1990-04-12'];
            $this->actingAs($this->adminB(), 'web')->post(route('staff.store'), $form)->assertOk()->assertJsonPath('error', false);
            auth()->forgetGuards();
        }
        $path = $this->emailedPath($tara);
        $this->actingAs($tara, 'web')->post($path.'/accept')->assertRedirect(route('home'));
        auth()->forgetGuards();
        app(MembershipService::class)->forget($tara);
    }

    /** @return array<string, mixed> the users row as School A relies on it */
    private function accountOf(User $person): array
    {
        return (array) DB::table('users')->where('id', $person->id)->first(['email', 'first_name', 'last_name', 'dob', 'gender', 'mobile', 'status', 'password', 'deleted_at', 'image']);
    }

    /** Still a teacher at School A, signed in everywhere as before. */
    private function assertStillAtSchoolA(User $person, array $account, int $tokens): void
    {
        $this->assertSame($account, $this->accountOf($person), 'the account is unchanged');
        $this->assertSame($tokens, DB::table('personal_access_tokens')->where('tokenable_id', $person->id)->count(), 'no sign-in ended');
        app(MembershipService::class)->forget($person);
        $this->assertTrue(app(MembershipService::class)->isActiveMember(User::acrossSchools()->findOrFail($person->id), 1), 'School A access kept');
        $this->assertTrue(app(CurrentSchool::class)->run(1, fn () => User::acrossSchools()->findOrFail($person->id)->hasRole('Teacher')));
    }

    /** Nothing about the person changed at School B, and their password is the same. */
    private function assertNothingAttached(User $person, string $hash, int $school = self::B): void
    {
        $this->assertSame(0, SchoolMembership::query()->where('school_id', $school)->where('user_id', $person->id)->count(), 'no membership');
        $this->assertSame(0, DB::table('model_has_roles')->where('school_id', $school)->where('model_id', $person->id)->count(), 'no role');
        $this->assertSame($hash, DB::table('users')->where('id', $person->id)->value('password'), 'password unchanged');
    }

    // ---- the happy path ----

    public function test_a_teacher_at_another_school_is_invited_signs_in_accepts_and_keeps_her_password(): void
    {
        $tara = $this->tara();
        $hash = $tara->password;
        $tara->createToken('phone');
        $tokens = $tara->tokens()->count();
        $this->adminB();
        $users = User::acrossSchools()->count();

        $this->assertSame(['error' => false, 'message' => trans('data_store_successfully')], $this->addTeacherAtB($tara->email));
        $this->assertSame($users, User::acrossSchools()->count(), 'no second account');
        $this->assertNothingAttached($tara, $hash);
        $path = $this->emailedPath($tara);

        // A guest is sent to sign in with their existing password, then comes back to the link.
        $this->get($path)->assertRedirect(route('login'));
        $this->post('/login', ['email' => $tara->email, 'password' => self::PASSWORD])->assertRedirect(url($path));
        $this->get($path)->assertOk()->assertSee('Join Baobab High')->assertSee('Teacher');
        $this->post($path.'/accept')->assertRedirect(route('home'))->assertSessionHas((string) config('tenancy.session_key'), self::B);

        $tara->refresh();
        $this->assertSame($hash, $tara->password, 'the password is never reset');
        $this->assertSame($tokens, $tara->tokens()->count(), 'no sign-in ends');
        $this->assertTrue(SchoolMembership::query()->where(['school_id' => self::B, 'user_id' => $tara->id, 'role' => 'Teacher', 'status' => 'active'])->exists());
        $this->assertTrue(app(CurrentSchool::class)->run(self::B, fn () => $tara->fresh()->hasRole('Teacher')));
        $this->assertSame('PGCE', Teacher::acrossSchools()->where('school_id', self::B)->where('user_id', $tara->id)->value('qualification'));
        $this->assertTrue(AuditLog::query()->where('action', 'school_join_invitation_accepted')->where('school_id', self::B)->exists(), 'audited at the inviting school');
        $this->get($path)->assertNotFound();
        $this->post($path.'/accept')->assertNotFound();

        // Both schools are in the switcher.
        $this->get('/home')->assertOk()->assertSee('js-school-switcher', false)->assertSee('Baobab High');
        $schools = collect(app(MembershipService::class)->schools($tara))->pluck('school.id')->all();
        $this->assertSame([1, self::B], $schools);
    }

    public function test_the_invitee_can_decline_and_nothing_is_attached(): void
    {
        $tara = $this->tara();
        $this->addTeacherAtB($tara->email);
        $path = $this->emailedPath($tara);
        $this->actingAs($tara, 'web')->post($path.'/decline')->assertRedirect(route('home'));

        $this->assertNothingAttached($tara, $tara->password);
        $this->assertNotNull($this->invitationAtB($tara)->declined_at);
        $this->assertTrue(AuditLog::query()->where('action', 'school_join_invitation_declined')->where('school_id', self::B)->exists());
        $this->post($path.'/accept')->assertNotFound();
        $this->assertNothingAttached($tara, $tara->password);
    }

    // ---- privacy ----

    public function test_the_school_is_answered_and_audited_the_same_whatever_the_address(): void
    {
        $tara = $this->tara();
        $learner = User::acrossSchools()->findOrFail($this->schoolA->ids['asha_user']);
        DB::table('users')->where('id', $learner->id)->update(['email' => 'asha.learner@example.com']);
        $operator = User::query()->create(['first_name' => 'Hq', 'last_name' => 'Staff', 'email' => 'operator@navigate.example', 'mobile' => '', 'password' => Hash::make(self::PASSWORD), 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $operator->id, 'active' => true]);

        $answers = [];
        foreach (['brand.new@example.com', $tara->email, 'asha.learner@example.com', $operator->email] as $email) {
            $answers[] = $this->addTeacherAtB($email);
        }
        $this->assertCount(1, array_unique(array_map('json_encode', $answers)), 'one answer for a new address, an account elsewhere, a learner and platform staff');
        $added = AuditLog::query()->where('action', 'staff_added')->where('school_id', self::B)->get();
        $this->assertCount(4, $added);
        $this->assertCount(1, $added->map(fn (AuditLog $row) => json_encode([$row->subject_type, $row->subject_id, $row->after]))->unique());

        // The learner and the platform staff member are never emailed, and their rows read like any waiting invitation.
        foreach ([$learner, $operator] as $person) {
            $this->assertFalse(app(SchoolJoinInvitations::class)->deliver($this->invitationAtB($person)));
            $this->assertNothingAttached($person, (string) DB::table('users')->where('id', $person->id)->value('password'));
        }
        $page = $this->actingAs($this->adminB(), 'web')->get(route('join-invitations.index'))->assertOk();
        foreach ([$tara->email, 'asha.learner@example.com', $operator->email] as $email) {
            $page->assertSee($email);
        }
        // Only what the school typed: never the account's own name or another school.
        $page->assertSee('Typed Name')->assertDontSee('Tara Demo')->assertDontSee('Jacaranda');
        $this->assertStringNotContainsString('withheld', strtolower($page->getContent()));

        // Adding the same address again is refused as a duplicate, as for the new person now on the staff.
        $again = [];
        foreach (['brand.new@example.com', $tara->email, 'asha.learner@example.com'] as $email) {
            $again[] = $this->addTeacherAtB($email);
        }
        $this->assertCount(1, array_unique(array_map('json_encode', $again)));
        $this->assertTrue($again[0]['error']);
    }

    public function test_the_invitation_secret_is_never_in_the_audit_log_or_the_models_array(): void
    {
        $tara = $this->tara();
        $this->addTeacherAtB($tara->email);
        $token = basename($this->emailedPath($tara));

        $this->assertStringNotContainsString($token, json_encode(DB::table('audit_log')->get()));
        $this->assertStringNotContainsString($token, json_encode(SchoolJoinInvitation::acrossSchools()->get()->toArray()));
        $this->assertSame(hash('sha256', $token), $this->invitationAtB($tara)->token_hash);
    }

    // ---- refusals ----

    public function test_accepting_as_another_account_is_refused(): void
    {
        $tara = $this->tara();
        $this->addTeacherAtB($tara->email);
        $path = $this->emailedPath($tara);
        $theo = User::acrossSchools()->findOrFail($this->schoolA->ids['theo_user']);

        $this->actingAs($theo, 'web')->get($path)->assertForbidden()->assertSee('This invitation is for another account');
        $this->post($path.'/accept')->assertForbidden();
        $this->post($path.'/decline')->assertForbidden();
        $this->assertNothingAttached($tara, $tara->password);
        $this->assertNothingAttached($theo, $theo->password);
        $this->assertNull($this->invitationAtB($tara)->used_at, 'the right person can still answer');
    }

    public function test_an_expired_used_superseded_withdrawn_or_readdressed_link_or_a_suspended_school_opens_nothing(): void
    {
        $tara = $this->tara();
        $hash = $tara->password;
        $joins = app(SchoolJoinInvitations::class);
        $email = $tara->email;
        foreach (['expired', 'superseded', 'withdrawn', 'school_suspended', 'email_changed'] as $case) {
            SchoolJoinInvitation::acrossSchools()->where('user_id', $tara->id)->update(['used_at' => now()]);
            $this->addTeacherAtB($email);
            $invitation = $this->invitationAtB($tara);
            $path = $this->pathOf($invitation);
            match ($case) {
                'expired' => $invitation->forceFill(['expires_at' => now()->subMinute()])->save(),
                'superseded' => app(CurrentSchool::class)->run(self::B, fn () => $joins->inviteFromSchool($this->adminB(), $tara, Role::query()->where('name', 'Teacher')->firstOrFail())),
                'withdrawn' => $this->actingAs($this->adminB(), 'web')->post(route('join-invitations.withdraw', $invitation->id))->assertRedirect(route('join-invitations.index')),
                'school_suspended' => School::query()->whereKey(self::B)->update(['status' => School::SUSPENDED]),
                'email_changed' => DB::table('users')->where('id', $tara->id)->update(['email' => 'tara.moved@example.com']),
            };
            auth()->forgetGuards();
            Mail::fake();
            $this->assertFalse($joins->canDeliver($invitation), $case.': the queued email is not sent');
            $this->actingAs($tara->fresh(), 'web')->get($path)->assertNotFound();
            $this->post($path.'/accept')->assertNotFound();
            $this->assertNothingAttached($tara, $hash);
            auth()->forgetGuards();
            School::query()->whereKey(self::B)->update(['status' => School::ACTIVE]);
            DB::table('users')->where('id', $tara->id)->update(['email' => $email]);
        }
    }

    public function test_learner_and_platform_staff_accounts_never_join_even_with_a_link(): void
    {
        $tara = $this->tara();
        $teacher = Role::acrossSchoolsQuery()->where('school_id', self::B)->where('name', 'Teacher')->firstOrFail();
        // Withheld at once: never sent, and its secret opens nothing.
        $learner = User::acrossSchools()->findOrFail($this->schoolA->ids['asha_user']);
        DB::table('users')->where('id', $learner->id)->update(['email' => 'asha.learner@example.com']);
        $this->addTeacherAtB('asha.learner@example.com');
        $withheld = $this->invitationAtB($learner);
        $this->assertNotNull($withheld->withheld_at);
        $this->actingAs($learner->fresh(), 'web')->post($this->pathOf($withheld).'/accept')->assertNotFound();
        $this->assertNothingAttached($learner, $learner->password);
        auth()->forgetGuards();

        // Became a learner or platform staff after the invitation was sent: refused at acceptance.
        foreach (['learner', 'platform_staff'] as $case) {
            SchoolJoinInvitation::acrossSchools()->where('user_id', $tara->id)->update(['used_at' => now()]);
            app(CurrentSchool::class)->run(self::B, fn () => app(SchoolJoinInvitations::class)->inviteFromSchool($this->adminB(), $tara, $teacher));
            $path = $this->pathOf($this->invitationAtB($tara));
            $case === 'learner'
                ? SchoolMembership::query()->create(['user_id' => $tara->id, 'school_id' => 1, 'role' => 'Student', 'status' => 'ended'])
                : PlatformStaffIdentity::query()->create(['user_id' => $tara->id, 'active' => true]);
            $this->actingAs($tara->fresh(), 'web')->post($path.'/accept')->assertNotFound();
            $this->assertNothingAttached($tara, $tara->password);
            SchoolMembership::query()->where('user_id', $tara->id)->where('role', 'Student')->delete();
            PlatformStaffIdentity::query()->where('user_id', $tara->id)->delete();
            auth()->forgetGuards();
        }
    }

    // ---- who may invite or withdraw, and which school ----

    public function test_school_b_cannot_see_or_withdraw_school_a_invitations_and_a_teacher_cannot_manage_them(): void
    {
        // School A invites School B's Tara (an account elsewhere for School A).
        $taraB = User::acrossSchools()->findOrFail($this->schoolB->ids['tara_user']);
        $response = $this->actingAs($this->superAdmin(), 'web')->post(route('teachers.store'), $this->teacherForm($taraB->email))->assertOk();
        $this->assertFalse($response->json('error'));
        $atA = SchoolJoinInvitation::acrossSchools()->where('school_id', 1)->where('user_id', $taraB->id)->sole();
        $this->get(route('join-invitations.index'))->assertOk()->assertSee($taraB->email);
        auth()->forgetGuards();

        $this->actingAs($this->adminB(), 'web')->get(route('join-invitations.index'))->assertOk()->assertDontSee($taraB->email);
        $this->post(route('join-invitations.withdraw', $atA->id))->assertNotFound();
        $this->assertNull($atA->fresh()->used_at);
        auth()->forgetGuards();

        // A teacher at School A has neither staff-create nor teacher-create.
        $theo = User::acrossSchools()->findOrFail($this->schoolA->ids['theo_user']);
        $this->actingAs($theo, 'web')->get(route('join-invitations.index'))->assertForbidden();
        $this->post(route('join-invitations.withdraw', $atA->id))->assertForbidden();
        $before = SchoolJoinInvitation::acrossSchools()->count();
        $this->post(route('teachers.store'), $this->teacherForm('someone.else@example.com'))->assertOk()->assertJsonMissing(['message' => trans('data_store_successfully')]);
        $this->assertSame($before, SchoolJoinInvitation::acrossSchools()->count());
        $this->assertNull($atA->fresh()->used_at);
    }

    public function test_staff_import_invites_an_account_from_another_school_instead_of_giving_it_the_role(): void
    {
        $tara = $this->tara();
        $admin = $this->adminB();
        $csv = "First Name,Surname,Email,Role\nNew,Person,new.person@example.com,Teacher\nTyped,Name,{$tara->email},Teacher\n";
        app(CurrentSchool::class)->run(self::B, function () use ($admin, $csv) {
            $imports = app(SchoolImports::class);
            $check = $imports->check($admin, 'staff', $csv, null, ['invite' => true]);
            $this->assertSame(['create', 'create'], array_column($check['rows'], 'status'));
            $this->assertSame($check['rows'][0]['notes'], $check['rows'][1]['notes'], 'the rows read the same');
            $this->assertSame(['created' => 2, 'invited' => 2], $imports->commit($admin, 'staff', $csv, $check['mapping'], $check['options'], $check['fingerprint']));
            $this->assertSame(['exists', 'exists'], array_column($imports->check($admin, 'staff', $csv, null, [])['rows'], 'status'));
        });
        $this->assertNothingAttached($tara, $tara->password);
        $this->assertNull($this->invitationAtB($tara)->withheld_at);
        $this->assertSame(0, StaffInvitation::acrossSchools()->where('user_id', $tara->id)->count(), 'no set-password link');
    }

    public function test_a_set_password_link_is_never_sent_to_someone_who_belongs_to_another_school(): void
    {
        $tara = $this->tara();
        $this->addTeacherAtB($tara->email);
        $path = $this->emailedPath($tara);
        $this->actingAs($tara, 'web')->post($path.'/accept')->assertRedirect();
        auth()->forgetGuards();

        $this->actingAs($this->adminB(), 'web')->post(route('people-profiles.invitation', $tara->id))->assertRedirect();
        $this->assertSame(0, StaffInvitation::acrossSchools()->where('user_id', $tara->id)->count());
        $this->assertSame($tara->password, DB::table('users')->where('id', $tara->id)->value('password'));
        $this->assertTrue(AuditLog::query()->where('action', 'staff_invitation_withheld')->where('school_id', self::B)->exists());
    }

    // ---- tenancy ----

    public function test_the_new_membership_opens_nothing_at_the_first_school_and_the_first_opens_nothing_at_the_new_one(): void
    {
        $tara = $this->tara();
        $bursar = app(CurrentSchool::class)->run(self::B, function () {
            $role = Role::query()->create(['name' => 'Baobab Bursar', 'guard_name' => 'web', 'custom_role' => 1]);
            $role->givePermissionTo('fees-paid');

            return $role;
        });
        $permissionsAt = fn (int $school) => app(CurrentSchool::class)->run($school, fn () => User::acrossSchools()->findOrFail($tara->id)->getAllPermissions()->pluck('name')->sort()->values()->all());
        $rolesAt = fn (int $school) => app(CurrentSchool::class)->run($school, fn () => User::acrossSchools()->findOrFail($tara->id)->getRoleNames()->all());
        $atA = $permissionsAt(1);
        $this->assertNotContains('fees-paid', $atA);

        $form = ['role_id' => $bursar->id, 'first_name' => 'Typed', 'last_name' => 'Name', 'email' => $tara->email, 'gender' => 'Female', 'mobile' => '0821234567', 'dob' => '1990-04-12'];
        $this->actingAs($this->adminB(), 'web')->post(route('staff.store'), $form)->assertOk()->assertJsonPath('error', false);
        auth()->forgetGuards();
        $path = $this->emailedPath($tara);
        $this->actingAs($tara, 'web')->post($path.'/accept')->assertRedirect();

        $this->assertSame($atA, $permissionsAt(1), 'nothing new at School A');
        $this->assertSame(['Teacher'], $rolesAt(1));
        $this->assertSame(['Baobab Bursar'], $rolesAt(self::B));
        $this->assertSame(['fees-paid'], $permissionsAt(self::B), 'only the role School B gave');
        // Each school's records stay its own.
        app(CurrentSchool::class)->run(self::B, function () use ($tara) {
            $this->assertFalse(Teacher::query()->where('user_id', $tara->id)->exists(), 'School A\'s teacher record is not School B\'s');
            $this->assertTrue(Staff::query()->where('user_id', $tara->id)->exists());
        });
        app(CurrentSchool::class)->run(1, function () use ($tara) {
            $this->assertSame(1, Staff::query()->where('user_id', $tara->id)->count(), 'School A keeps only its own staff row');
        });
        // Working in School B, School A's teacher permissions open nothing.
        $this->withSession([(string) config('tenancy.session_key') => self::B])->getJson('/medium_list?limit=50&offset=0')->assertOk()->assertJsonPath('error', true);
    }

    // ---- HQ: the first administrator ----

    public function test_hq_sets_up_a_school_whose_first_administrator_already_has_an_account(): void
    {
        $tara = $this->tara();
        $hash = $tara->password;
        $operator = User::query()->create(['first_name' => 'Hq', 'last_name' => 'Staff', 'email' => 'operator@navigate.example', 'mobile' => '', 'password' => Hash::make(self::PASSWORD), 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $operator->id, 'active' => true]);
        PlatformAdminGrant::query()->create(['user_id' => $operator->id, 'active' => true, 'expires_at' => now()->addHour()]);
        app(CurrentSchool::class)->forget();
        $users = User::acrossSchools()->count();
        $form = ['name' => 'Marula Primary', 'slug' => '', 'timezone' => 'Africa/Johannesburg', 'package_key' => 'core', 'admin_first_name' => 'Tara', 'admin_last_name' => 'Moving', 'admin_email' => strtoupper($tara->email), 'admin_mobile' => ''];

        // Accounts that may not be a school's administrator are refused, and nothing is created.
        $schools = School::query()->count();
        $learner = User::acrossSchools()->findOrFail($this->schoolA->ids['asha_user']);
        DB::table('users')->where('id', $learner->id)->update(['email' => 'asha.learner@example.com']);
        foreach (['asha.learner@example.com', $operator->email, User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user'])->email] as $refused) {
            $this->actingAs($operator)->post('/saas/schools', ['admin_email' => $refused] + $form)->assertSessionHasErrors('admin_email');
        }
        $this->assertSame($schools, School::query()->count());

        $response = $this->actingAs($operator)->post('/saas/schools', $form)->assertOk()->assertSee('already has a NavigateEd account');
        $this->assertTrue($response->viewData('existingAccount'));
        $this->assertSame($users, User::acrossSchools()->count(), 'no second account');
        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();
        $this->assertNothingAttached($tara, $hash, (int) $school->id);
        $first = (string) parse_url($response->viewData('invitationUrl'), PHP_URL_PATH);
        $this->get('/saas/schools/'.$school->id)->assertOk()->assertSee('Send the invitation again')->assertDontSee($tara->email);
        $this->post('/saas/schools/'.$school->id.'/administrator-invitation')->assertRedirect('/saas/schools/'.$school->id);
        auth()->forgetGuards();
        app(CurrentSchool::class)->forget();

        $this->actingAs($tara, 'web')->get($first)->assertNotFound();
        $latest = SchoolJoinInvitation::acrossSchools()->where('school_id', $school->id)->latest('id')->firstOrFail();
        $this->get($this->pathOf($latest))->assertOk()->assertSee('Be the first administrator of Marula Primary');
        $this->post($this->pathOf($latest).'/accept')->assertRedirect(route('home'));
        $this->assertSame($hash, DB::table('users')->where('id', $tara->id)->value('password'));
        $this->assertTrue(app(CurrentSchool::class)->run((int) $school->id, fn () => $tara->fresh()->hasRole('Super Admin')));
        $this->assertTrue(app(CurrentSchool::class)->run(1, fn () => $tara->fresh()->hasRole('Teacher')), 'still a teacher at her first school');
        auth()->forgetGuards();

        $this->actingAs($operator)->get('/saas/schools/'.$school->id)->assertOk()->assertDontSee('Send the invitation again');
        $this->assertFalse(app(SchoolJoinInvitations::class)->resendAdministrator((int) $school->id, $operator));
    }

    // ---- a shared account belongs to its owner, not to one school (review HIGH-1) ----

    public function test_school_b_cannot_change_lock_out_or_delete_a_teacher_it_shares_with_school_a(): void
    {
        $tara = $this->tara();
        DB::table('users')->where('id', $tara->id)->update(['permanent_address' => '2 School Lane']);
        $tara = $tara->fresh();
        $this->joinB($tara);
        $account = $this->accountOf($tara);
        $tokens = $tara->tokens()->count();
        $teacherB = (int) Teacher::acrossSchools()->where('school_id', self::B)->where('user_id', $tara->id)->value('id');
        $form = fn (array $changes = []) => array_merge($this->teacherForm($tara->email, ['first_name' => $tara->first_name, 'last_name' => $tara->last_name, 'gender' => $tara->gender,
            'mobile' => $tara->mobile, 'dob' => '1990-04-12', 'current_address' => $tara->current_address, 'permanent_address' => '2 School Lane']), ['id' => $teacherB, 'user_id' => $tara->id], $changes);
        $this->actingAs($this->adminB(), 'web');

        // Changing the account's email (then "forgot password") or name is refused.
        foreach ([['email' => 'takeover@baobab.example'], ['first_name' => 'Someone'], ['dob' => '1970-01-01']] as $change) {
            $this->put('/teachers/'.$teacherB, $form($change))->assertOk()->assertJsonPath('error', true);
            $this->assertStillAtSchoolA($tara, $account, $tokens);
        }
        // School B's own record still changes.
        $this->put('/teachers/'.$teacherB, $form(['qualification' => 'MEd']))->assertOk()->assertJsonPath('error', false);
        $this->assertSame('MEd', Teacher::acrossSchools()->whereKey($teacherB)->value('qualification'));
        $this->assertStillAtSchoolA($tara, $account, $tokens);

        // Deactivating ends School B's role only; reactivating restores it.
        $this->post('/teachers/'.$tara->id.'/status', ['status' => 0])->assertOk()->assertJsonPath('error', false);
        $this->assertStillAtSchoolA($tara, $account, $tokens);
        $this->assertFalse(app(CurrentSchool::class)->run(self::B, fn () => app(SharedAccounts::class)->activeHere($tara->id, 'Teacher')));
        $this->post('/teachers/'.$tara->id.'/status', ['status' => 1])->assertOk()->assertJsonPath('error', false);
        $this->assertTrue(app(CurrentSchool::class)->run(self::B, fn () => app(SharedAccounts::class)->activeHere($tara->id, 'Teacher')));

        // Deleting removes her from School B only.
        $this->delete('/teachers/'.$tara->id)->assertOk()->assertJsonPath('error', false);
        $this->assertStillAtSchoolA($tara, $account, $tokens);
        $this->assertFalse(Teacher::acrossSchools()->whereKey($teacherB)->exists());
        $this->assertFalse(SchoolMembership::query()->where(['school_id' => self::B, 'user_id' => $tara->id, 'status' => 'active'])->exists());
    }

    public function test_school_b_cannot_change_or_delete_a_staff_member_it_shares_with_school_a(): void
    {
        $tara = $this->tara();
        $bursar = app(CurrentSchool::class)->run(self::B, fn () => Role::query()->create(['name' => 'Baobab Bursar', 'guard_name' => 'web', 'custom_role' => 1]));
        $this->joinB($tara, $bursar);
        $account = $this->accountOf($tara);
        $tokens = $tara->tokens()->count();
        $form = fn (array $changes = []) => array_merge(['role_id' => $bursar->id, 'user_id' => $tara->id, 'first_name' => $tara->first_name, 'last_name' => $tara->last_name,
            'email' => $tara->email, 'gender' => $tara->gender, 'mobile' => $tara->mobile, 'dob' => '1990-04-12', 'address' => $tara->current_address], $changes);
        $this->actingAs($this->adminB(), 'web');

        $this->put('/staff/'.$tara->id, $form(['email' => 'takeover@baobab.example']))->assertJsonPath('error', true);
        $this->put('/staff/'.$tara->id, $form(['last_name' => 'Other']))->assertJsonPath('error', true);
        $this->assertStillAtSchoolA($tara, $account, $tokens);
        $this->put('/staff/'.$tara->id, $form())->assertJsonPath('error', false);
        $this->assertStillAtSchoolA($tara, $account, $tokens);

        $this->delete('/staff/'.$tara->id)->assertJsonPath('error', false);
        $this->assertStillAtSchoolA($tara, $account, $tokens);
        $this->assertFalse(SchoolMembership::query()->where(['school_id' => self::B, 'user_id' => $tara->id, 'status' => 'active'])->exists(), 'only School B\'s role ends');
        $this->assertFalse(Staff::acrossSchools()->where('school_id', self::B)->where('user_id', $tara->id)->exists());
    }

    public function test_an_account_linked_to_school_b_only_as_a_guardian_is_not_school_bs_to_change_or_delete(): void
    {
        $tara = $this->tara();
        $account = $this->accountOf($tara);
        $tokens = $tara->tokens()->count();
        $this->actingAs($this->adminB(), 'web');
        $this->post(route('learner-guardians.store', $this->schoolB->ids['ben']), ['email' => $tara->email, 'first_name' => 'Tara', 'last_name' => 'Aunt',
            'relationship' => 'aunt_or_uncle', 'can_approve' => 1, 'can_see_fees' => 0])->assertRedirect();
        $parentRow = (int) DB::table('parents')->where('school_id', self::B)->where('user_id', $tara->id)->value('id');
        $this->assertGreaterThan(0, $parentRow);

        // The teacher form with her user id and one of School B's own teachers.
        $this->put('/teachers/'.$this->schoolB->ids['tara_teacher'], $this->teacherForm('takeover@baobab.example') + ['id' => $this->schoolB->ids['tara_teacher'], 'user_id' => $tara->id])
            ->assertJsonPath('error', true);
        $this->assertSame($account, $this->accountOf($tara));
        // The parent form and the profile form.
        $this->put('/parents/'.$parentRow, ['edit_id' => $parentRow, 'first_name' => 'Tara', 'last_name' => 'Aunt', 'gender' => 'female', 'email' => 'takeover@baobab.co.za', 'mobile' => '0123456789', 'dob' => '1990-04-12'])
            ->assertJsonPath('error', true);
        $this->assertSame($account, $this->accountOf($tara));
        $this->put('/people-profiles/'.$tara->id, ['profile_type' => 'parent', 'values' => ['email' => ['text' => 'takeover@baobab.example']]])->assertStatus(422);
        $this->assertSame($account, $this->accountOf($tara));

        // Deleting the only learner she is the legacy guardian of ends her Parent role at School B, never her account.
        DB::table('students')->where('id', $this->schoolB->ids['ben'])->update(['guardian_id' => $parentRow]);
        $this->delete('/students/'.$this->schoolB->ids['ben_user'])->assertOk()->assertJsonPath('error', false);
        $this->assertStillAtSchoolA($tara, $account, $tokens);
        $this->assertFalse(SchoolMembership::query()->where(['school_id' => self::B, 'user_id' => $tara->id, 'role' => 'Parent', 'status' => 'active'])->exists());
    }

    // ---- staff import consent (review MEDIUM-1) ----

    public function test_the_staff_import_never_gives_a_role_to_a_learner_or_a_known_non_staff_account_without_consent(): void
    {
        $asha = User::acrossSchools()->findOrFail($this->schoolA->ids['asha_user']);
        DB::table('users')->where('id', $asha->id)->update(['email' => 'asha.learner@example.com']);
        $benB = User::acrossSchools()->findOrFail($this->schoolB->ids['ben_user']);
        DB::table('users')->where('id', $benB->id)->update(['email' => 'ben.learner@baobab.example']);
        $farid = User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user']);
        $csv = "First Name,Surname,Email,Role\nAsha,Learner,asha.learner@example.com,Teacher\nBen,Elsewhere,ben.learner@baobab.example,Teacher\nFarid,Parent,{$farid->email},Teacher\n";
        $imports = app(SchoolImports::class);
        $check = $imports->check($this->superAdmin(), 'staff', $csv, null, ['invite' => true]);
        $this->assertSame(['error', 'create', 'create'], array_column($check['rows'], 'status'), 'a learner here is refused; others read like new people');

        $good = "First Name,Surname,Email,Role\nBen,Elsewhere,ben.learner@baobab.example,Teacher\nFarid,Parent,{$farid->email},Teacher\n";
        $check = $imports->check($this->superAdmin(), 'staff', $good, null, ['invite' => true]);
        $imports->commit($this->superAdmin(), 'staff', $good, $check['mapping'], $check['options'], $check['fingerprint']);
        foreach ([$benB, $farid] as $person) {
            $this->assertFalse(SchoolMembership::query()->where(['school_id' => 1, 'user_id' => $person->id, 'role' => 'Teacher'])->exists(), 'no role without consent');
            $this->assertSame(0, StaffInvitation::acrossSchools()->where('user_id', $person->id)->count(), 'no set-password link');
            $this->assertTrue(SchoolJoinInvitation::acrossSchools()->where('school_id', 1)->where('user_id', $person->id)->exists(), 'an invitation instead');
        }
        $this->assertNotNull(SchoolJoinInvitation::acrossSchools()->where('school_id', 1)->where('user_id', $benB->id)->value('withheld_at'), 'a learner is never asked');
    }

    // ---- enumeration and lookups (review MEDIUM-2, LOW) ----

    public function test_adding_someone_again_after_they_declined_reads_as_a_duplicate_but_after_a_withdrawal_invites_again(): void
    {
        $tara = $this->tara();
        $this->addTeacherAtB($tara->email);
        $path = $this->emailedPath($tara);
        $this->actingAs($tara, 'web')->post($path.'/decline')->assertRedirect();
        auth()->forgetGuards();
        $this->assertTrue($this->addTeacherAtB($tara->email)['error'], 'declined: refused as a duplicate');

        SchoolJoinInvitation::acrossSchools()->where('user_id', $tara->id)->update(['declined_at' => now()->subDays(200)]);
        $this->assertFalse($this->addTeacherAtB($tara->email)['error'], 'long ago: invited again');
        $waiting = $this->invitationAtB($tara);
        $this->actingAs($this->adminB(), 'web')->post(route('join-invitations.withdraw', $waiting->id))->assertRedirect();
        auth()->forgetGuards();
        $this->assertFalse($this->addTeacherAtB($tara->email)['error'], 'withdrawn by the school: invited again');
    }

    public function test_only_the_exact_address_is_invited_and_the_school_keeps_what_it_typed(): void
    {
        $jose = User::query()->create(['first_name' => 'Jose', 'last_name' => 'Demo', 'email' => 'jose@example.com', 'mobile' => '', 'password' => Hash::make(self::PASSWORD), 'status' => 1]);
        app(CurrentSchool::class)->run(1, fn () => $jose->assignRole('Teacher'));
        $answer = $this->addTeacherAtB('josé@example.com');
        $this->assertFalse($answer['error']);
        $invitation = $this->invitationAtB($jose);
        $this->assertNotNull($invitation->withheld_at, 'an address that only looks the same is never emailed');
        $this->assertSame('josé@example.com', $invitation->encrypted_email, 'the school sees what it typed');

        $tara = $this->tara();
        $this->addTeacherAtB(strtoupper($tara->email));
        $this->assertNull($this->invitationAtB($tara)->withheld_at, 'letter case does not matter');
        $this->assertSame(strtoupper($tara->email), $this->invitationAtB($tara)->encrypted_email);
    }

    public function test_hq_is_not_offered_a_resend_after_the_first_administrator_declines(): void
    {
        $tara = $this->tara();
        $operator = User::query()->create(['first_name' => 'Hq', 'last_name' => 'Staff', 'email' => 'operator@navigate.example', 'mobile' => '', 'password' => Hash::make(self::PASSWORD), 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $operator->id, 'active' => true]);
        PlatformAdminGrant::query()->create(['user_id' => $operator->id, 'active' => true, 'expires_at' => now()->addHour()]);
        app(CurrentSchool::class)->forget();
        $form = ['name' => 'Marula Primary', 'slug' => '', 'timezone' => 'Africa/Johannesburg', 'package_key' => 'core', 'admin_first_name' => 'Tara', 'admin_last_name' => 'Moving', 'admin_email' => $tara->email, 'admin_mobile' => ''];
        $path = (string) parse_url($this->actingAs($operator)->post('/saas/schools', $form)->assertOk()->viewData('invitationUrl'), PHP_URL_PATH);
        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();
        auth()->forgetGuards();
        $this->actingAs($tara, 'web')->post($path.'/decline')->assertRedirect();
        auth()->forgetGuards();

        $this->actingAs($operator)->get('/saas/schools/'.$school->id)->assertOk()->assertDontSee('Send the invitation again');
        $this->assertFalse(app(SchoolJoinInvitations::class)->resendAdministrator((int) $school->id, $operator));
    }
}
