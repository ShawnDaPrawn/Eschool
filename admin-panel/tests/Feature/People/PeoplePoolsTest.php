<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivitySession;
use App\Models\People\Enrolment;
use App\Models\People\Pools\PoolBooking;
use App\Models\People\Pools\PoolMember;
use App\Models\People\Pools\PoolOperation;
use App\Models\People\Pools\PoolVetting;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\People\Pools\PoolAccess;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * The alumni and volunteer pool (Action Plan 5.6): consent, vetting, group
 * bookings only, and a pool sign-in that never reaches learner records.
 */
class PeoplePoolsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private const PASSWORD = 'StartingPool2026!';

    private const CHOSEN = 'ChosenPool2026!x';

    private int $year;

    private int $group;

    private int $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(9, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $admin = $this->superAdmin();
        app(ModuleGate::class)->change('groups', true, $admin);
        app(ModuleGate::class)->change('people_pool', true, $admin);
        foreach (['asha', 'ben'] as $learner) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$learner], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic dated group enrolment']);
        }
        [$this->group, $this->session] = $this->groupSession(['asha', 'ben']);
    }

    /** @return array{0: int, 1: int} a staffed group with these learners and a session on 12 February */
    private function groupSession(array $learners, bool $staffed = true, string $name = 'Careers club'): array
    {
        $groups = app(ActivityGroups::class);
        $admin = $this->superAdmin();
        $group = $groups->save($admin, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'parent_id' => null, 'name' => $name, 'discipline' => 'Careers', 'season' => '2026',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null, 'minimum_age' => null, 'maximum_age' => null, 'active' => true])['id'];
        $dates = fn (array $extra) => $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic allocation'];
        if ($staffed) {
            $groups->allocation($admin, $group, null, true, $dates(['user_id' => $this->schoolA->ids['tara_user'], 'role' => 'facilitator', 'cascade' => false]));
        }
        foreach ($learners as $learner) {
            $groups->allocation($admin, $group, null, false, $dates(['learner_id' => $this->schoolA->ids[$learner], 'role' => 'member']));
        }
        $session = $groups->session($admin, $group, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-12', 'name' => 'Careers morning', 'venue' => 'Hall', 'status' => 'scheduled', 'starts_at' => '10:00', 'ends_at' => '11:00']);

        return [$group, $session['id']];
    }

    private function api(?User $actor = null, ?string $token = null): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($token ?? $actor->createToken('pool-test')->plainTextToken);
    }

    private function admin(): self
    {
        return $this->api($this->superAdmin());
    }

    private function inviteData(array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'kind' => 'volunteer', 'first_name' => 'Vusi', 'last_name' => 'Volunteer', 'email' => 'vusi.volunteer@example.test',
            'initial_password' => self::PASSWORD, 'occupation' => 'Civil engineer', 'organisation' => 'Synthetic Works', 'offers_career_talk' => true, 'offers_job_shadow' => false];
    }

    private function invite(array $extra = []): PoolMember
    {
        $id = $this->admin()->postJson('/api/v2/people-pool/members', $this->inviteData($extra))->assertOk()->assertJsonPath('data.consent_status', 'invited')->json('data.id');

        return PoolMember::findOrFail($id);
    }

    private function signIn(string $email, string $school = '', string $password = self::PASSWORD): TestResponse
    {
        $slug = $school !== '' ? $school : School::findOrFail(1)->slug;
        $this->app['auth']->forgetGuards();
        $input = ['school' => $slug, 'email' => $email, 'password' => $password];
        $setup = $this->flushHeaders()->postJson('/api/v2/pool/sign-in', $input);
        if ($setup->status() !== 409) {
            return $setup;
        }
        $setup->assertJsonPath('code', 'POOL_SETUP_REQUIRED')->assertJsonPath('data.token', null);
        $acceptances = array_column($setup->json('data.documents'), 'version', 'document');
        $this->assertArrayHasKey('pool_consent', $acceptances);

        return $this->postJson('/api/v2/pool/sign-in', $input + ['new_password' => self::CHOSEN, 'new_password_confirmation' => self::CHOSEN, 'acceptances' => $acceptances]);
    }

    /** @return array{0: PoolMember, 1: string} a consented pool member and their pool token */
    private function consented(array $extra = []): array
    {
        $member = $this->invite($extra);
        $this->assertSame(0, PoolMember::whereKey($member->id)->where('consent_status', 'given')->count(), 'The school cannot give consent for someone');
        $token = $this->signIn($this->inviteData($extra)['email'])->assertOk()->assertJsonPath('data.authenticated', true)->json('data.token');
        $member->refresh();
        $this->assertSame('given', $member->consent_status);

        return [$member, $token];
    }

    private function vet(PoolMember $member, array $extra = []): TestResponse
    {
        return $this->admin()->postJson('/api/v2/people-pool/members/'.$member->id.'/vetting', $extra + ['operation_id' => (string) Str::uuid(), 'revision' => $member->fresh()->revision, 'outcome' => 'cleared',
            'checks' => ['identity', 'child_protection_register', 'sexual_offences_register'], 'checked_on' => '2026-02-01', 'expires_on' => '2027-01-31', 'note' => 'Synthetic register checks']);
    }

    private function book(PoolMember $member, ?int $session = null, array $extra = []): TestResponse
    {
        return $this->admin()->postJson('/api/v2/people-pool/bookings', $extra + ['operation_id' => (string) Str::uuid(), 'pool_member_id' => $member->id, 'activity_session_id' => $session ?? $this->session, 'kind' => 'career_talk']);
    }

    private function learnerName(string $key): string
    {
        $user = User::findOrFail($this->schoolA->ids[$key.'_user']);

        return trim($user->first_name.' '.$user->last_name);
    }

    public function test_invited_volunteer_consents_is_vetted_booked_and_sees_the_attendance_list_only_on_the_day(): void
    {
        [$member, $token] = $this->consented();
        $account = User::acrossSchools()->findOrFail($member->user_id);
        $this->assertSame([], SchoolMembership::where('user_id', $account->id)->pluck('id')->all(), 'Pool accounts hold no school membership');
        $this->assertFalse((bool) $account->password_change_required);
        $this->vet($member)->assertOk()->assertJsonPath('data.vetting_status', 'cleared')->assertJsonPath('data.vetting_expires_on', '2027-01-31')
            ->assertJsonPath('data.vettings.0.checked_by', (int) $this->superAdmin()->id)->assertJsonPath('data.bookable_today', true);
        $booking = $this->book($member)->assertOk()->assertJsonPath('data.status', 'booked')->assertJsonPath('data.vetted_for_session', true)->json('data.id');

        $this->api(token: $token)->getJson('/api/v2/pool/session')->assertOk()->assertJsonPath('data.vetted', true)->assertJsonPath('data.school.id', 1);
        $mine = $this->getJson('/api/v2/pool/bookings')->assertOk()->assertJsonPath('data.items.0.id', $booking)->assertJsonPath('data.items.0.attendance_open', false);
        $mine->assertDontSee($this->learnerName('asha'));
        $this->getJson('/api/v2/pool/bookings/'.$booking.'/attendance')->assertForbidden();

        $this->travelTo(now()->setDate(2026, 2, 12)->setTime(8, 0));
        $list = $this->api(token: $token)->getJson('/api/v2/pool/bookings/'.$booking.'/attendance')->assertOk()->assertJsonCount(2, 'data.learners');
        $list->assertSee($this->learnerName('asha'))->assertSee($this->learnerName('ben'))->assertDontSee('learner_id')->assertDontSee('@')
            ->assertDontSee($this->learnerName('cara'));
        $this->assertSame(['name', 'status'], array_keys($list->json('data.learners.0')));

        $this->travelTo(now()->setDate(2026, 2, 13)->setTime(8, 0));
        $this->api(token: $token)->getJson('/api/v2/pool/bookings/'.$booking.'/attendance')->assertForbidden();
    }

    public function test_alumni_come_from_the_schools_own_adult_leaver_records(): void
    {
        $asha = Students::findOrFail($this->schoolA->ids['asha']);
        User::whereKey($asha->user_id)->update(['dob' => '2007-01-15']);
        // Not a leaver yet.
        $this->admin()->postJson('/api/v2/people-pool/members', $this->inviteData(['kind' => 'alumnus', 'learner_id' => $asha->id]))->assertUnprocessable()->assertJsonValidationErrors('learner_id', 'data.errors');
        SchoolMembership::where('school_id', 1)->where('user_id', $asha->user_id)->where('role', 'Student')->update(['status' => 'ended', 'leaver_until' => '2026-01-31', 'ends_on' => '2026-01-31']);
        $member = $this->invite(['kind' => 'alumnus', 'learner_id' => $asha->id, 'email' => 'asha.alumna@example.test']);
        $this->assertSame((int) $asha->id, (int) $member->learner_id);
        $this->assertNotSame((int) $asha->user_id, (int) $member->user_id, 'An alumna gets a new adult pool account; the learner account is not converted');
        $this->admin()->postJson('/api/v2/people-pool/members', $this->inviteData(['kind' => 'alumnus', 'learner_id' => $asha->id, 'email' => 'asha.again@example.test']))->assertUnprocessable();

        $ben = Students::findOrFail($this->schoolA->ids['ben']);
        User::whereKey($ben->user_id)->update(['dob' => '2010-06-01']);
        SchoolMembership::where('school_id', 1)->where('user_id', $ben->user_id)->where('role', 'Student')->update(['status' => 'ended', 'leaver_until' => '2026-01-31', 'ends_on' => '2026-01-31']);
        $this->admin()->postJson('/api/v2/people-pool/members', $this->inviteData(['kind' => 'alumnus', 'learner_id' => $ben->id, 'email' => 'ben.young@example.test']))->assertUnprocessable()->assertJsonValidationErrors('learner_id', 'data.errors');
        // School B's leaver is not School A's record.
        $this->admin()->postJson('/api/v2/people-pool/members', $this->inviteData(['kind' => 'alumnus', 'learner_id' => $this->schoolB->ids['asha'], 'email' => 'baobab.alumna@example.test']))->assertUnprocessable();
    }

    public function test_unvetted_refused_expired_or_revoked_people_cannot_be_booked(): void
    {
        [$member, $token] = $this->consented();
        $this->book($member)->assertUnprocessable()->assertJsonValidationErrors('pool_member_id', 'data.errors');
        $this->vet($member, ['outcome' => 'refused', 'expires_on' => null, 'checks' => ['identity']])->assertOk()->assertJsonPath('data.vetting_status', 'refused');
        $this->book($member)->assertUnprocessable()->assertJsonValidationErrors('pool_member_id', 'data.errors');
        // Clearing needs every required check.
        $this->vet($member, ['checks' => ['identity']])->assertUnprocessable()->assertJsonValidationErrors('checks', 'data.errors');
        // A clearance that ends before the session date does not cover it.
        $this->vet($member, ['expires_on' => '2026-02-11'])->assertOk();
        $this->book($member)->assertUnprocessable()->assertJsonValidationErrors('pool_member_id', 'data.errors');
        $this->vet($member)->assertOk();
        $booking = $this->book($member)->assertOk()->json('data.id');
        $this->vet($member, ['outcome' => 'revoked', 'expires_on' => null, 'checks' => []])->assertOk()->assertJsonPath('data.vetting_status', 'revoked');
        $this->assertSame('cancelled', PoolBooking::findOrFail($booking)->status);
        $this->book($member)->assertUnprocessable();
        $this->api(token: $token)->getJson('/api/v2/pool/bookings')->assertOk()->assertJsonPath('data.vetted', false)->assertJsonCount(0, 'data.items');

        // A clearance that runs out between booking and the day closes the list.
        [$other, $otherToken] = $this->consented(['email' => 'thandi.volunteer@example.test']);
        $this->vet($other, ['expires_on' => '2026-02-12'])->assertOk();
        $otherBooking = $this->book($other)->assertOk()->json('data.id');
        PoolMember::whereKey($other->id)->update(['vetting_expires_on' => '2026-02-11']);
        $this->travelTo(now()->setDate(2026, 2, 12)->setTime(8, 0));
        $this->api(token: $otherToken)->getJson('/api/v2/pool/bookings/'.$otherBooking.'/attendance')->assertForbidden();
        $this->assertSame(4, PoolVetting::where('pool_member_id', $member->id)->count());
    }

    public function test_a_one_to_one_booking_is_refused(): void
    {
        [$member, $token] = $this->consented();
        $this->vet($member)->assertOk();
        [, $solo] = $this->groupSession(['asha'], true, 'Mentoring');
        $this->book($member, $solo)->assertUnprocessable()->assertJsonValidationErrors('activity_session_id', 'data.errors');
        [, $unstaffed] = $this->groupSession(['asha', 'ben'], false, 'Unstaffed');
        $this->book($member, $unstaffed)->assertUnprocessable()->assertJsonValidationErrors('activity_session_id', 'data.errors');
        $this->assertSame(0, PoolBooking::count());

        // A group that shrinks to one learner after booking is no longer shown.
        $booking = $this->book($member)->assertOk()->json('data.id');
        ActivityMembership::where('activity_group_id', $this->group)->where('learner_id', $this->schoolA->ids['ben'])->update(['active' => false]);
        $this->travelTo(now()->setDate(2026, 2, 12)->setTime(8, 0));
        $this->api(token: $token)->getJson('/api/v2/pool/bookings/'.$booking.'/attendance')->assertForbidden()->assertDontSee($this->learnerName('asha'));
    }

    public function test_a_pool_member_cannot_read_learner_records_marks_or_contact_details(): void
    {
        [$member, $token] = $this->consented();
        $this->vet($member)->assertOk();
        $this->book($member)->assertOk();
        $asha = $this->schoolA->ids['asha'];
        foreach ([
            '/api/v2/me/context', '/api/v2/activities/groups', '/api/v2/activities/groups/'.$this->group.'/roster', '/api/v2/people-pool/members',
            '/api/teacher/classes', '/api/student/subjects', '/api/parent/subjects?child_id='.$asha,
        ] as $path) {
            $response = $this->api(token: $token)->getJson($path);
            $this->assertContains($response->status(), [401, 403, 404], $path.' answered '.$response->status());
            $response->assertDontSee($this->learnerName('asha'));
        }
        $this->api(token: $token)->withHeaders(['X-School-Id' => '1'])->getJson('/api/v2/me/context')->assertForbidden();
        $this->api(token: $token)->putJson('/api/v2/activities/sessions/'.$this->session.'/attendance', [])->assertForbidden();
        // A pool account given any other token still has no school to enter.
        $this->api(User::acrossSchools()->findOrFail($member->user_id))->getJson('/api/v2/me/context')->assertForbidden();
    }

    public function test_wrong_school_never_sees_or_books_another_schools_pool(): void
    {
        [$member, $token] = $this->consented();
        $this->vet($member)->assertOk();
        // School B's own pool manager, with the pool switched on there.
        $adminB = app(CurrentSchool::class)->run(2, function () {
            $manager = User::findOrFail($this->schoolB->ids['tara_user']);
            $manager->givePermissionTo(PoolAccess::MANAGE, PoolAccess::VET);
            app(ModuleGate::class)->change('groups', true, $manager);
            app(ModuleGate::class)->change('people_pool', true, $manager);

            return $manager;
        });
        $b = fn () => $this->api($adminB)->withHeaders(['X-School-Id' => '2']);
        $b()->getJson('/api/v2/people-pool/members')->assertOk()->assertJsonPath('data.total', 0)->assertDontSee('Vusi');
        $b()->getJson('/api/v2/people-pool/members/'.$member->id)->assertNotFound();
        $b()->postJson('/api/v2/people-pool/members/'.$member->id.'/vetting', ['operation_id' => (string) Str::uuid(), 'revision' => $member->fresh()->revision, 'outcome' => 'revoked', 'checks' => [], 'checked_on' => '2026-02-01'])->assertNotFound();
        $b()->postJson('/api/v2/people-pool/bookings', ['operation_id' => (string) Str::uuid(), 'pool_member_id' => $member->id, 'activity_session_id' => $this->session, 'kind' => 'career_talk'])->assertNotFound();
        $this->assertSame('cleared', $member->fresh()->vetting_status);
        // The School A pool sign-in is not a School B one, by header or by sign-in.
        $this->api(token: $token)->withHeaders(['X-School-Id' => '2'])->getJson('/api/v2/pool/session')->assertForbidden();
        $this->signIn('vusi.volunteer@example.test', School::findOrFail(2)->slug, self::CHOSEN)->assertForbidden();
        // School A cannot book into School B's sessions.
        $sessionB = app(CurrentSchool::class)->run(2, fn () => ActivitySession::create(['activity_group_id' => ActivityGroup::create(['session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab careers', 'discipline' => 'Careers', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31'])->id, 'on_date' => '2026-02-12', 'name' => 'Baobab talk', 'venue' => 'Hall'])->id);
        $this->book($member, $sessionB)->assertNotFound();
    }

    public function test_wrong_role_cannot_manage_vet_or_book(): void
    {
        $member = $this->invite();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        foreach ([$teacher, $parent] as $actor) {
            $this->api($actor)->getJson('/api/v2/people-pool/members')->assertForbidden();
            $this->api($actor)->postJson('/api/v2/people-pool/members', $this->inviteData(['email' => 'other@example.test']))->assertForbidden();
            $this->api($actor)->postJson('/api/v2/people-pool/bookings', ['operation_id' => (string) Str::uuid(), 'pool_member_id' => $member->id, 'activity_session_id' => $this->session, 'kind' => 'career_talk'])->assertForbidden();
        }
        // Managing the pool is not vetting it.
        $teacher->givePermissionTo(PoolAccess::MANAGE);
        $this->api($teacher)->getJson('/api/v2/people-pool/members/'.$member->id)->assertOk()->assertJsonPath('data.vettings', []);
        $this->api($teacher)->postJson('/api/v2/people-pool/members/'.$member->id.'/vetting', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'outcome' => 'cleared', 'checks' => ['identity'], 'checked_on' => '2026-02-01', 'expires_on' => '2027-01-31'])->assertForbidden();
        $this->vet($member)->assertOk();
        // Vetting notes are for those who vet.
        $this->api($teacher)->getJson('/api/v2/people-pool/members/'.$member->id)->assertOk()->assertJsonPath('data.vettings.0.note', null);
        $this->admin()->getJson('/api/v2/people-pool/members/'.$member->id)->assertOk()->assertJsonPath('data.vettings.0.note', 'Synthetic register checks');
        $this->assertSame(0, PoolMember::where('consent_status', 'given')->count());
        // The pool sign-in is not a staff or family sign-in.
        $teacher->forceFill(['password' => Hash::make(self::PASSWORD)])->save();
        $this->signIn($teacher->email)->assertForbidden();
        // With the pool switched off nothing answers.
        app(ModuleGate::class)->change('people_pool', false, $this->superAdmin());
        $this->admin()->getJson('/api/v2/people-pool/members')->assertNotFound();
    }

    public function test_withdrawn_consent_removes_access_immediately(): void
    {
        [$member, $token] = $this->consented();
        $this->vet($member)->assertOk();
        $booking = $this->book($member)->assertOk()->json('data.id');
        $kept = User::acrossSchools()->findOrFail($member->user_id)->createToken(AppTokens::POOL, [PoolAccess::ability(1)])->plainTextToken;
        $this->api(token: $token)->postJson('/api/v2/pool/consent/withdraw')->assertOk()->assertJsonPath('data.consent_status', 'withdrawn');
        $this->api(token: $token)->getJson('/api/v2/pool/session')->assertUnauthorized();
        $this->api(token: $kept)->getJson('/api/v2/pool/bookings')->assertUnauthorized();
        $this->assertSame(0, User::acrossSchools()->findOrFail($member->user_id)->tokens()->count());
        $this->assertSame('cancelled', PoolBooking::findOrFail($booking)->status);
        $this->assertSame('consent_withdrawn', PoolBooking::findOrFail($booking)->cancel_reason);
        $this->book($member)->assertUnprocessable()->assertJsonValidationErrors('pool_member_id', 'data.errors');
        $this->vet($member)->assertConflict();
        $this->signIn('vusi.volunteer@example.test', '', self::CHOSEN)->assertForbidden();
        // Even a token that escaped revocation finds no consent.
        $escaped = User::acrossSchools()->findOrFail($member->user_id)->createToken(AppTokens::POOL, [PoolAccess::ability(1)])->plainTextToken;
        $this->api(token: $escaped)->getJson('/api/v2/pool/session')->assertForbidden();
        $this->api(token: $escaped)->getJson('/api/v2/pool/bookings/'.$booking.'/attendance')->assertForbidden();
    }

    public function test_a_switched_off_pool_refuses_members_but_withdrawal_still_works(): void
    {
        [$member, $token] = $this->consented();
        app(ModuleGate::class)->change('people_pool', false, $this->superAdmin());
        $this->api(token: $token)->getJson('/api/v2/pool/session')->assertForbidden();
        $this->api(token: $token)->getJson('/api/v2/pool/bookings')->assertForbidden();
        $this->api(token: $token)->postJson('/api/v2/pool/consent/withdraw')->assertOk();
        $this->assertSame('withdrawn', $member->fresh()->consent_status);
    }

    public function test_writes_are_idempotent_and_receipts_hold_no_personal_data(): void
    {
        $data = $this->inviteData();
        $first = $this->admin()->postJson('/api/v2/people-pool/members', $data)->assertOk()->json('data.id');
        $this->admin()->postJson('/api/v2/people-pool/members', $data)->assertOk()->assertJsonPath('data.id', $first);
        $this->assertSame(1, PoolMember::count());
        $this->assertSame(1, User::acrossSchools()->where('email', $data['email'])->count());
        $this->admin()->postJson('/api/v2/people-pool/members', ['first_name' => 'Someone'] + $data)->assertConflict();
        foreach (PoolOperation::all() as $operation) {
            $this->assertSame(['id'], array_keys($operation->receipt));
        }
        $this->assertStringNotContainsString(self::PASSWORD, (string) PoolOperation::firstOrFail()->request_hash);

        $member = PoolMember::findOrFail($first);
        $this->signIn($data['email'])->assertOk();
        $vetting = ['operation_id' => (string) Str::uuid(), 'revision' => $member->fresh()->revision, 'outcome' => 'cleared', 'checks' => ['identity', 'child_protection_register', 'sexual_offences_register'], 'checked_on' => '2026-02-01', 'expires_on' => '2027-01-31'];
        $this->admin()->postJson('/api/v2/people-pool/members/'.$member->id.'/vetting', $vetting)->assertOk();
        $this->admin()->postJson('/api/v2/people-pool/members/'.$member->id.'/vetting', $vetting)->assertOk();
        $this->assertSame(1, PoolVetting::count());
        // A stale revision is refused rather than overwriting.
        $this->admin()->postJson('/api/v2/people-pool/members/'.$member->id.'/vetting', ['operation_id' => (string) Str::uuid()] + $vetting)->assertConflict();

        $booking = ['operation_id' => (string) Str::uuid(), 'pool_member_id' => $member->id, 'activity_session_id' => $this->session, 'kind' => 'career_talk'];
        $id = $this->admin()->postJson('/api/v2/people-pool/bookings', $booking)->assertOk()->json('data.id');
        $this->admin()->postJson('/api/v2/people-pool/bookings', $booking)->assertOk()->assertJsonPath('data.id', $id);
        $this->admin()->postJson('/api/v2/people-pool/bookings', ['operation_id' => (string) Str::uuid()] + $booking)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertSame(1, PoolBooking::count());
        $cancel = ['operation_id' => (string) Str::uuid(), 'revision' => PoolBooking::findOrFail($id)->revision];
        $this->admin()->postJson('/api/v2/people-pool/bookings/'.$id.'/cancel', $cancel)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->admin()->postJson('/api/v2/people-pool/bookings/'.$id.'/cancel', $cancel)->assertOk()->assertJsonPath('data.status', 'cancelled');
        // Booking again after a cancellation reuses the one booking row.
        $this->admin()->postJson('/api/v2/people-pool/bookings', ['operation_id' => (string) Str::uuid()] + $booking)->assertOk()->assertJsonPath('data.id', $id)->assertJsonPath('data.status', 'booked');
        $this->assertSame(1, PoolBooking::count());
        // Replays recheck authority first.
        $this->superAdmin()->roles()->first()?->revokePermissionTo(PoolAccess::MANAGE);
        $this->admin()->postJson('/api/v2/people-pool/bookings', $booking)->assertForbidden();
    }
}
