<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\ClassTeacher;
use App\Models\Leave;
use App\Models\People\Enrolment;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\StudentOnlineExamStatus;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\NewYearService;
use App\Services\Academics\ReleasedMarks;
use App\Services\People\Leavers\AlumniInvitations;
use App\Services\People\Leavers\LeaverState;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The leaver state (Action Plan 1.21, part 2): learners who leave at the year
 * switch, and guardians whose children all left, read until 31 March and
 * write nothing; the office brings a leaver back or ends access sooner.
 * Specification: engineering/specs/academics/new-year-structures.md, "Leaver state".
 *
 * Asha and Ben leave at the switch into 2027; Cara continues. Farid is the
 * father of Asha and Cara, Mona Asha's mother, Gina Ben's guardian.
 */
class LeaverStateTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $next;

    /** @var \ArrayObject<int, int> learners the alumni hook heard of */
    private \ArrayObject $alumni;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 12, 20)->setTime(10, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = (int) SchoolTable::query('session_years')->insertGetId([
            'name' => '2027', 'start_date' => '2027-01-13', 'end_date' => '2027-12-08', 'free_app_use_date' => '2027-01-13', 'fee_due_date' => '2027-02-13', 'fee_due_charges' => 5, 'default' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids = $this->schoolA->ids;
        foreach (['asha' => 'section_a', 'ben' => 'section_a', 'cara' => 'section_c'] as $learner => $room) {
            Enrolment::create(['learner_id' => $ids[$learner], 'session_year_id' => $this->year, 'class_section_id' => $ids[$room], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic enrolment']);
        }
        Enrolment::create(['learner_id' => $ids['cara'], 'session_year_id' => $this->next, 'class_section_id' => $ids['section_a'], 'starts_on' => '2027-01-13', 'ends_on' => '2027-12-08', 'reason' => 'Synthetic promotion']);
        foreach (['asha' => [null, 0], 'ben' => [null, 0], 'cara' => [$ids['section_a'], 1]] as $learner => [$room, $status]) {
            StudentSessions::withoutEvents(fn () => StudentSessions::query()->updateOrCreate(
                ['student_id' => $ids[$learner], 'session_year_id' => $this->next],
                ['class_section_id' => $room, 'previous_session_year_id' => $this->year]));
            StudentSessions::query()->where('student_id', $ids[$learner])->where('session_year_id', $this->next)->update(['status' => $status, 'result' => 1]);
        }
        $this->alumni = new \ArrayObject;
        $this->app->instance(AlumniInvitations::class, new class($this->alumni) implements AlumniInvitations
        {
            /** @param  \ArrayObject<int, int>  $seen */
            public function __construct(private \ArrayObject $seen) {}

            public function leaverStarted(Students $learner, SchoolMembership $membership): void
            {
                $this->seen->append((int) $learner->id);
            }
        });
    }

    private function switchYear(): void
    {
        app(NewYearService::class)->switchTo(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), []);
        $this->fresh();
    }

    /** A new request: no cached sign-in, memberships or roles. */
    private function fresh(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->defaultHeaders = [];
        $this->app->forgetScopedInstances();
        app(CurrentSchool::class)->set(1);
    }

    private function as(string $who): self
    {
        $this->fresh();

        // A token issued now: the demo school's own are older than the 30 days Sanctum accepts here.
        return $this->withToken(User::findOrFail($this->schoolA->ids[$who.'_user'])->createToken('leaver-test')->plainTextToken);
    }

    private function membership(string $who, string $role): SchoolMembership
    {
        return SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->schoolA->ids[$who.'_user'])->where('role', $role)->sole();
    }

    private function writeProfile(string $who, string $learner): TestResponse
    {
        return $this->as($who)->putJson('/api/v2/people/'.$this->schoolA->ids[$learner.'_user'].'/profile',
            ['profile_type' => 'learner', 'values' => ['transport_route' => ['text' => 'Route Seven']]]);
    }

    /** A request made by $user, for helpers that read request() (the auth rebinding resets a resolver set before binding). */
    private function bindRequest(User $user, string $path, string $method, array $input = []): void
    {
        $this->app->instance('request', Request::create($path, $method, $input));
        request()->setUserResolver(fn () => $user);
    }

    private function transportField(): void
    {
        $service = app(ProfileDefinitions::class);
        $category = $service->category($this->superAdmin(), ['profile_type' => 'learner', 'key' => 'transport', 'label' => 'Transport']);
        $service->field($this->superAdmin(), ['category_id' => $category->id, 'key' => 'transport_route', 'label' => 'Transport route', 'type' => 'text',
            'visible_to' => ['owner', 'guardian', 'school_admin', 'teacher'], 'editable_by' => ['owner', 'guardian', 'school_admin']]);
        User::query()->whereIn('id', [$this->schoolA->ids['farid_user'], $this->schoolA->ids['gina_user'], $this->schoolA->ids['mona_user']])->update(['email_verified_at' => now()]);
        foreach (['asha', 'ben', 'cara'] as $learner) {
            app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids[$learner]));
        }
    }

    public function test_the_switch_makes_leavers_read_only_until_31_march_instead_of_deactivating_them(): void
    {
        $this->switchYear();

        foreach (['asha', 'ben'] as $learner) {
            $row = $this->membership($learner, 'Student');
            $this->assertSame([SchoolMembership::LEAVER, '2027-03-31', '2027-03-31'], [$row->status, $row->leaver_until->toDateString(), $row->ends_on->toDateString()]);
            $this->assertSame(1, (int) User::findOrFail($this->schoolA->ids[$learner.'_user'])->status, 'not deactivated');
        }
        $this->assertSame(SchoolMembership::ACTIVE, $this->membership('cara', 'Student')->status);
        // Mona and Gina have no current child left; Farid still has Cara.
        $this->assertSame([SchoolMembership::LEAVER, '2027-03-31'], [$this->membership('mona', 'Parent')->status, $this->membership('mona', 'Parent')->leaver_until->toDateString()]);
        $this->assertSame(SchoolMembership::LEAVER, $this->membership('gina', 'Parent')->status);
        $this->assertSame(SchoolMembership::ACTIVE, $this->membership('farid', 'Parent')->status);
        // The alumni hook heard of each leaver once.
        $expected = [$this->schoolA->ids['asha'], $this->schoolA->ids['ben']];
        $heard = $this->alumni->getArrayCopy();
        sort($heard);
        $this->assertSame($expected, $heard);

        // Leavers are not current learners: they drop out of groups, messages and lists.
        app(CurrentSchool::class)->set(1);
        $current = app(CurrentLearnerMembership::class)->users()->pluck('id')->all();
        $this->assertSame([$this->schoolA->ids['cara_user']], $current);
        $this->assertSame(2, app(LeaverState::class)->count());

        // A second switch changes nothing.
        $this->assertSame(0, app(LeaverState::class)->begin([$this->schoolA->ids['asha']], SessionYear::findOrFail($this->next)));
        // School B is untouched.
        $this->assertSame(0, SchoolMembership::query()->where('school_id', self::SCHOOL_B_ID)->where('status', '!=', SchoolMembership::ACTIVE)->count());
    }

    public function test_read_only_a_guardian_of_a_leaver_reads_but_is_refused_a_write(): void
    {
        $this->transportField();
        $this->switchYear();
        $ids = $this->schoolA->ids;

        // Gina's only child left: she reads Ben's marks, reports and profile ...
        $this->as('gina')->getJson('/api/v2/academics/marks/learners/'.$ids['ben'].'/periods')->assertOk()->assertJsonPath('data.learner_id', $ids['ben']);
        $this->as('gina')->getJson('/api/v2/academics/reports/learners/'.$ids['ben'])->assertOk();
        $this->as('gina')->getJson('/api/v2/people/'.$ids['ben_user'].'/profile?profile_type=learner')->assertOk();
        // ... and writes nothing.
        $this->writeProfile('gina', 'ben')->assertForbidden();
        $this->as('gina')->putJson('/api/v2/me/notification-preferences', ['preferences' => []])->assertForbidden();

        // Farid still has Cara at the school: he writes for Cara, not for Asha.
        $this->writeProfile('farid', 'asha')->assertForbidden();
        $this->assertSame(0, ProfileFieldValue::query()->count(), 'nothing written for a leaver');
        $this->writeProfile('farid', 'cara')->assertOk();
        $this->assertSame(1, ProfileFieldValue::query()->count());
        $this->as('farid')->getJson('/api/v2/academics/marks/learners/'.$ids['asha'].'/periods')->assertOk();
        // The profile form tells the guardian it is read-only.
        $this->assertFalse(app(ProfilePolicy::class)->update(User::findOrFail($ids['farid_user']), User::findOrFail($ids['asha_user']), 'learner'));

        // The leaver reads their own records and changes nothing.
        $this->as('ben')->getJson('/api/v2/academics/reports/learners/'.$ids['ben'])->assertOk();
        $this->as('ben')->getJson('/api/v2/me/profile')->assertOk();
        $this->as('ben')->getJson('/api/v2/me/context')->assertOk();
        $this->as('ben')->putJson('/api/v2/me/profile', ['values' => ['transport_route' => ['text' => 'Mine']]])->assertForbidden();
        $this->assertSame(1, ProfileFieldValue::query()->count());

        // The parent app (v1): Mona, whose only child left, still opens it; Farid cannot ask for leave for Asha.
        $this->as('mona')->getJson('/api/parent/get-profile-data')->assertOk()->assertJsonPath('error', false);
        $leaves = Leave::query()->count();
        $this->as('farid')->postJson('/api/parent/apply-leave', ['child_id' => $ids['asha'], 'reason' => 'Trip', 'leave_details' => []])->assertForbidden();
        // The same id written the other ways downstream code accepts is refused too.
        foreach (['+'.$ids['asha'], ' '.$ids['asha'], (string) $ids['asha'].' '] as $spelling) {
            $this->as('farid')->postJson('/api/parent/apply-leave', ['child_id' => $spelling, 'reason' => 'Trip', 'leave_details' => []])->assertForbidden();
        }
        $this->as('farid')->postJson('/api/parent/apply-leave', ['child_id' => [$ids['cara'], $ids['asha']], 'reason' => 'Trip', 'leave_details' => []])->assertForbidden();
        $this->assertSame($leaves, Leave::query()->count());
    }

    public function test_expiry_after_leaver_until_the_leaver_and_guardians_lose_access(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->travelTo(now()->setDate(2027, 3, 31)->setTime(23, 0));
        $this->as('ben')->getJson('/api/v2/academics/reports/learners/'.$ids['ben'])->assertOk();
        $this->as('gina')->getJson('/api/v2/academics/marks/learners/'.$ids['ben'].'/periods')->assertOk();

        $this->travelTo(now()->setDate(2027, 4, 1)->setTime(8, 0));
        $this->as('ben')->getJson('/api/v2/academics/reports/learners/'.$ids['ben'])->assertForbidden();
        $this->as('ben')->getJson('/api/v2/me/context')->assertForbidden();
        $this->as('gina')->getJson('/api/v2/academics/marks/learners/'.$ids['ben'].'/periods')->assertForbidden();
        $this->as('mona')->getJson('/api/v2/academics/marks/learners/'.$ids['asha'].'/periods')->assertForbidden();
        // Farid keeps the school for Cara, but Asha's records are no longer his to read.
        $this->as('farid')->getJson('/api/v2/academics/marks/learners/'.$ids['asha'].'/periods')->assertNotFound();
        $this->as('farid')->getJson('/api/v2/people/'.$ids['asha_user'].'/profile?profile_type=learner')->assertNotFound();
        $this->as('farid')->getJson('/api/v2/academics/marks/learners/'.$ids['cara'].'/periods')->assertOk();
        app(CurrentSchool::class)->set(1);
        $this->assertSame([], app(GuardianService::class)->guardianUserIds(Students::findOrFail($ids['asha']))->all());
        $this->assertSame(0, app(LeaverState::class)->count());
    }

    public function test_the_office_sees_leavers_and_brings_one_back_as_a_returning_learner(): void
    {
        $this->transportField();
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->actingAs($this->superAdmin());

        $this->get(route('leavers.index'))->assertOk()->assertSee('Ben')->assertSee('Leaver: read-only until 31 Mar 2027')->assertDontSee(self::SCHOOL_B_MARKER);
        $rows = collect($this->getJson(route('students.list.data', ['session_year_id' => $this->next]))->assertOk()->json('rows'))->keyBy('id');
        $this->assertSame('Leaver: read-only until 31 Mar 2027', $rows[$ids['ben']]['class_section_name']);
        $this->assertNotSame('Leaver: read-only until 31 Mar 2027', $rows[$ids['cara']]['class_section_name']);
        $this->get(route('home'))->assertOk()->assertSee('Leavers (read-only)');

        $this->post(route('leavers.restore', $ids['ben']), ['homeroom_id' => $ids['section_c']])->assertRedirect(route('leavers.index'));

        $row = $this->membership('ben', 'Student');
        $this->assertSame([SchoolMembership::ACTIVE, null, null], [$row->status, $row->leaver_until, $row->ends_on]);
        $this->assertSame(SchoolMembership::ACTIVE, $this->membership('gina', 'Parent')->status);
        $this->assertSame($ids['section_c'], (int) Enrolment::where('learner_id', $ids['ben'])->where('session_year_id', $this->next)->sole()->class_section_id);
        $session = StudentSessions::where('student_id', $ids['ben'])->where('session_year_id', $this->next)->sole();
        $this->assertSame([1, $ids['section_c']], [(int) $session->status, (int) $session->class_section_id]);
        // Ben and Gina may write again.
        $this->writeProfile('gina', 'ben')->assertOk();
    }

    public function test_the_office_ends_leaver_access_early(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->actingAs($this->superAdmin())->post(route('leavers.end', $ids['asha']), ['confirm' => 1])->assertRedirect(route('leavers.index'));

        $this->assertSame([SchoolMembership::ENDED, '2026-12-20'], [$this->membership('asha', 'Student')->status, $this->membership('asha', 'Student')->ends_on->toDateString()]);
        $this->assertSame(SchoolMembership::ENDED, $this->membership('mona', 'Parent')->status);
        $this->assertSame(SchoolMembership::ACTIVE, $this->membership('farid', 'Parent')->status);
        $this->assertSame(SchoolMembership::LEAVER, $this->membership('ben', 'Student')->status, 'other leavers keep their access');
        $this->as('asha')->getJson('/api/v2/academics/reports/learners/'.$ids['asha'])->assertForbidden();
        $this->as('farid')->getJson('/api/v2/academics/marks/learners/'.$ids['asha'].'/periods')->assertNotFound();
        $this->as('gina')->getJson('/api/v2/academics/marks/learners/'.$ids['ben'].'/periods')->assertOk();
        // Ending twice is refused.
        $this->fresh();
        $this->actingAs($this->superAdmin())->post(route('leavers.end', $ids['asha']), ['confirm' => 1])->assertSessionHasErrors('learner');
    }

    public function test_wrong_role_a_teacher_cannot_end_leaver_access_or_restore_a_leaver(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $before = SchoolMembership::query()->orderBy('id')->get(['id', 'status', 'leaver_until', 'ends_on'])->toArray();
        $enrolments = Enrolment::query()->count();
        $teacher = User::findOrFail($ids['tara_user']);

        $this->actingAs($teacher)->get(route('leavers.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('leavers.restore', $ids['ben']), ['homeroom_id' => $ids['section_a']])->assertForbidden();
        $this->actingAs($teacher)->post(route('leavers.end', $ids['ben']), ['confirm' => 1])->assertForbidden();

        $this->assertSame($before, SchoolMembership::query()->orderBy('id')->get(['id', 'status', 'leaver_until', 'ends_on'])->toArray());
        $this->assertSame($enrolments, Enrolment::query()->count());
        $this->expectException(HttpException::class);
        app(LeaverState::class)->endAccess($teacher, $ids['ben']);
    }

    public function test_wrong_school_school_bs_learner_cannot_be_restored_or_ended_from_school_a(): void
    {
        $this->switchYear();
        $b = $this->schoolB->ids;
        // School B's Ben is a leaver at School B.
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => SchoolMembership::query()->where('school_id', self::SCHOOL_B_ID)->where('user_id', $b['ben_user'])->where('role', 'Student')
            ->update(['status' => SchoolMembership::LEAVER, 'leaver_until' => '2027-03-31', 'ends_on' => '2027-03-31']));
        $before = SchoolMembership::query()->where('school_id', self::SCHOOL_B_ID)->orderBy('id')->get(['id', 'status', 'leaver_until', 'ends_on'])->toArray();
        $enrolments = Enrolment::query()->withoutGlobalScopes()->count();
        $this->fresh();
        $this->actingAs($this->superAdmin());

        $this->post(route('leavers.restore', $b['ben']), ['homeroom_id' => $this->schoolA->ids['section_a']])->assertNotFound();
        $this->post(route('leavers.restore', $this->schoolA->ids['ben']), ['homeroom_id' => $b['section_a']])->assertNotFound();
        $this->post(route('leavers.end', $b['ben']), ['confirm' => 1])->assertNotFound();
        $this->get(route('leavers.index'))->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);

        $this->assertSame($before, SchoolMembership::query()->where('school_id', self::SCHOOL_B_ID)->orderBy('id')->get(['id', 'status', 'leaver_until', 'ends_on'])->toArray());
        $this->assertSame($enrolments, Enrolment::query()->withoutGlobalScopes()->count());
        $this->assertSame(SchoolMembership::LEAVER, $this->membership('ben', 'Student')->status, 'School A\'s Ben unchanged by the refused restore');
    }

    public function test_the_wizard_preview_names_the_last_day_and_late_or_mid_year_leavers_get_90_days(): void
    {
        $this->actingAs($this->superAdmin())->get(route('new-year.index'))->assertOk()->assertSee('until 31 Mar 2027');
        $state = app(LeaverState::class);
        app(CurrentSchool::class)->set(1);
        $this->assertSame('2027-03-31', $state->untilForSwitch(SessionYear::findOrFail($this->next)));
        // A switch after that 31 March: 90 days from the switch, not the next March.
        $this->travelTo(now()->setDate(2027, 4, 5));
        $this->assertSame('2027-07-04', $state->untilForSwitch(SessionYear::findOrFail($this->next)));
        // Marked as leaving mid-year: 90 days from that day, with no 31 March cap.
        $this->travelTo(now()->setDate(2027, 6, 1));
        $this->assertSame('2027-08-30', $state->untilMidYear());
        $this->travelTo(now()->setDate(2027, 3, 25));
        $this->assertSame('2027-06-23', $state->untilMidYear());
        config(['leavers.access_days' => 30]);
        $this->assertSame('2027-04-24', $state->untilMidYear());
    }

    public function test_writes_that_name_a_leaver_by_a_record_id_are_refused_by_the_family_helpers(): void
    {
        $this->transportField();
        $ids = $this->schoolA->ids;
        $leave = Leave::query()->create(['user_id' => $ids['asha_user'], 'reason' => 'Dentist', 'from_date' => '2027-02-01', 'to_date' => '2027-02-01', 'status' => 0, 'session_year_id' => $this->year]);
        $this->switchYear();

        // v1: the leave is found through the parent's children, not a learner field.
        $this->as('farid')->postJson('/api/parent/delete-leave', ['leave_id' => $leave->id])->assertForbidden();
        $this->assertTrue(Leave::query()->whereKey($leave->id)->exists());
        // Approving, withdrawing and choosing a handle (LearnerAccessPolicy) are refused.
        $farid = User::findOrFail($ids['farid_user']);
        $this->bindRequest($farid, '/api/v2/guardian/approvals/'.$ids['asha'], 'POST', ['decision' => 'approve']);
        $this->assertTrue(Gate::forUser($farid)->inspect('approveAccess', Students::findOrFail($ids['asha']))->denied());
        $this->assertTrue(Gate::forUser($farid)->inspect('approveAccess', Students::findOrFail($ids['cara']))->allowed());
        // ReleasedMarks::learner (discipline acknowledgements, register QR, tutors) refuses a write and allows a read.
        $this->bindRequest($farid, '/api/v2/discipline/family/records/1/acknowledge', 'POST');
        try {
            app(ReleasedMarks::class)->learner($farid, $ids['asha']);
            $this->fail('A write for a leaver was allowed.');
        } catch (AccessDeniedHttpException) {
        }
        $this->assertSame($ids['cara'], (int) app(ReleasedMarks::class)->learner($farid, $ids['cara'])->id);
        $this->app->instance('request', Request::create('/api/v2/academics/reports/learners/'.$ids['asha'], 'GET'));
        $this->assertSame($ids['asha'], (int) app(ReleasedMarks::class)->learner($farid, $ids['asha'])->id);
    }

    public function test_a_guardian_or_former_learner_who_is_also_staff_writes_nothing_for_a_leaver(): void
    {
        app(CurrentSchool::class)->set(1);
        User::findOrFail($this->schoolA->ids['gina_user'])->assignRole('Teacher');
        User::findOrFail($this->schoolA->ids['ben_user'])->assignRole('Teacher');
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->assertSame([SchoolMembership::LEAVER, SchoolMembership::ACTIVE], [$this->membership('gina', 'Parent')->status, $this->membership('gina', 'Teacher')->status]);

        $leaves = Leave::query()->count();
        $this->as('gina')->postJson('/api/parent/apply-leave', ['child_id' => $ids['ben'], 'reason' => 'Trip', 'leave_details' => []])->assertForbidden();
        $this->assertSame($leaves, Leave::query()->count());
        $this->as('gina')->getJson('/api/v2/academics/marks/learners/'.$ids['ben'].'/periods')->assertOk();

        // Ben, now also staff: his learner role counts for reading, never for writing.
        $ben = User::findOrFail($ids['ben_user']);
        $this->bindRequest($ben, '/api/v2/me/profile', 'PUT');
        $this->assertNotContains('Student', app(ProfilePolicy::class)->roles($ben));
        // Someone else's roles (staff checking a guardian) still count Ben's learner role on a write.
        $this->assertContains('Student', app(ProfilePolicy::class)->roles($ben, false));
        $this->bindRequest($ben, '/api/v2/me/profile', 'GET');
        $this->assertContains('Student', app(ProfilePolicy::class)->roles($ben));
    }

    public function test_leavers_and_their_guardians_sign_in_and_read_through_the_v1_apps(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->fresh();
        $login = $this->postJson('/api/student/login', ['gr_number' => User::findOrFail($ids['ben_user'])->email, 'password' => 'secret123'])->assertOk()->assertJsonPath('error', false);
        $token = (string) $login->json('token');
        $this->assertNotSame('', $token);

        $learner = fn () => tap($this, fn () => $this->fresh())->withToken($token);
        $learner()->getJson('/api/student/get-profile-data')->assertOk()->assertJsonPath('error', false)->assertJsonPath('data.class_section_id', $ids['section_a']);
        $learner()->postJson('/api/student/get-leave-list', [])->assertOk();
        $learner()->getJson('/api/student/timetable')->assertForbidden();
        $statuses = StudentOnlineExamStatus::query()->count();
        $learner()->getJson('/api/student/get-online-exam-questions?exam_id='.$ids['past_online_exam'].'&exam_key=1')->assertForbidden();
        $this->assertSame($statuses, StudentOnlineExamStatus::query()->count());
        $learner()->postJson('/api/student/apply-leave', ['reason' => 'Trip', 'leave_details' => []])->assertForbidden();

        // Gina's only child left; Farid has one current child and one leaver.
        foreach (['gina', 'farid'] as $guardian) {
            $this->fresh();
            $this->postJson('/api/parent/login', ['email' => User::findOrFail($ids[$guardian.'_user'])->email, 'password' => 'secret123'])->assertOk()->assertJsonPath('error', false);
        }
    }

    public function test_after_expiry_the_context_lists_no_leaver_and_a_leaver_elsewhere_lands_on_their_current_school(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $children = fn () => collect($this->as('farid')->getJson('/api/v2/me/context')->assertOk()->json('data.children'))->pluck('id')->all();
        $this->assertContains($ids['asha'], $children());
        $this->travelTo(now()->setDate(2027, 4, 1));
        $this->assertSame([$ids['cara']], $children());

        // Ben a leaver at School A, a current member of School B.
        SchoolMembership::query()->insert(['user_id' => $ids['ben_user'], 'school_id' => self::SCHOOL_B_ID, 'role' => 'Parent', 'status' => SchoolMembership::ACTIVE, 'starts_on' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);
        $this->travelTo(now()->setDate(2027, 2, 1));
        $this->fresh();
        $this->assertSame(self::SCHOOL_B_ID, (int) app(MembershipService::class)->defaultSchool(User::findOrFail($ids['ben_user']))?->id);
    }

    public function test_guardians_settle_when_a_current_child_goes_and_ending_access_signs_people_out(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        // Cara removed: Farid's only child here is Asha, a leaver.
        app(MembershipService::class)->end(User::findOrFail($ids['cara_user']), 1, 'Student');
        app(LeaverState::class)->settleGuardians([$ids['farid_user']]);
        $this->assertSame([SchoolMembership::LEAVER, '2027-03-31'], [$this->membership('farid', 'Parent')->status, $this->membership('farid', 'Parent')->leaver_until->toDateString()]);

        $events = OutboxEvent::query()->where('type', 'context.changed')->count();
        $this->actingAs($this->superAdmin())->post(route('leavers.end', $ids['asha']), ['confirm' => 1])->assertRedirect();
        $this->assertSame(SchoolMembership::ENDED, $this->membership('farid', 'Parent')->status);
        $this->assertSame(SchoolMembership::ENDED, $this->membership('mona', 'Parent')->status);
        $this->assertSame($events + 1, OutboxEvent::query()->where('type', 'context.changed')->count());
        foreach (['asha', 'mona', 'farid'] as $who) {
            $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $ids[$who.'_user'])->count(), $who.' signed out');
        }
        $this->assertGreaterThan(0, DB::table('personal_access_tokens')->where('tokenable_id', $ids['ben_user'])->count());
    }

    public function test_former_learners_are_not_offered_as_current_learners(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->actingAs($this->superAdmin())->get(route('teaching-groups.create'))->assertOk()
            ->assertViewHas('learners', fn (array $learners) => ! array_key_exists($ids['ben'], $learners) && array_key_exists($ids['cara'], $learners));
    }

    public function test_expiry_follows_the_school_calendar_in_south_africa(): void
    {
        School::query()->whereKey(1)->update(['timezone' => 'Africa/Johannesburg']);
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->travelTo(CarbonImmutable::parse('2027-03-31 21:30:00', 'UTC'));
        $this->as('ben')->getJson('/api/v2/academics/reports/learners/'.$ids['ben'])->assertOk();
        // 22:30 UTC on 31 March is 00:30 on 1 April in Johannesburg.
        $this->travelTo(CarbonImmutable::parse('2027-03-31 22:30:00', 'UTC'));
        $this->as('ben')->getJson('/api/v2/academics/reports/learners/'.$ids['ben'])->assertForbidden();
        $this->as('gina')->getJson('/api/v2/academics/marks/learners/'.$ids['ben'].'/periods')->assertForbidden();
    }

    public function test_a_guardian_may_still_withdraw_consent_reject_a_handle_switch_an_item_off_and_accept_the_terms(): void
    {
        $this->transportField();
        $this->switchYear();
        $ids = $this->schoolA->ids;
        // Gina's only child left: she withdraws Ben's app approval, but cannot approve it.
        $this->as('gina')->postJson('/api/v2/guardian/approvals/'.$ids['ben'], ['decision' => 'approve', 'consent_version' => config('identity.consent_version')])->assertForbidden();
        $this->as('gina')->postJson('/api/v2/guardian/approvals/'.$ids['ben'], ['decision' => 'withdraw', 'consent_version' => config('identity.consent_version')])->assertOk();
        // Accepting the school's terms (every parent-app read needs it) is an acknowledgement.
        $this->as('gina')->postJson('/api/parent/policy-acceptance', ['document' => 'navigateed', 'version' => 'x'])->assertOk();
        // Decisions that only take access away are not writes for a leaver.
        foreach ([['/api/v2/guardian/handle-proposals/1', ['decision' => 'reject'], false], ['/api/v2/guardian/handle-proposals/1', ['decision' => 'approve'], true],
            ['/api/v2/parent/fees/accounts/1/optional-items/2/consents', ['active' => false], false], ['/api/v2/parent/fees/accounts/1/optional-items/2/consents', ['active' => true], true]] as [$path, $input, $writes]) {
            $this->app->instance('request', Request::create($path, 'POST', $input));
            $this->assertSame($writes, LeaverState::writing(), $path.' '.json_encode($input));
        }
    }

    public function test_a_guardian_whose_other_child_starts_later_is_not_made_a_leaver(): void
    {
        // Cara joins (an incoming transfer or import) after the switch day.
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->schoolA->ids['cara_user'])->where('role', 'Student')->update(['starts_on' => '2027-01-13']);
        $this->switchYear();
        $this->assertSame(SchoolMembership::ACTIVE, $this->membership('farid', 'Parent')->status);
        $this->assertSame(SchoolMembership::LEAVER, $this->membership('mona', 'Parent')->status);
    }

    public function test_a_leaver_cannot_open_an_online_exam_result_and_teachers_do_not_list_leavers_to_chat_with(): void
    {
        $this->switchYear();
        $ids = $this->schoolA->ids;
        $this->as('ben')->getJson('/api/student/get-online-exam-result?online_exam_id='.$ids['past_online_exam'])->assertForbidden();
        // Tara teaches Grade 5 A in the new year, where Cara now is; Ben's last homeroom was Grade 5 A.
        ClassTeacher::query()->create(['class_section_id' => $ids['section_a'], 'class_teacher_id' => $ids['tara_teacher'], 'session_year_id' => $this->next]);
        $list = $this->as('tara')->getJson('/api/teacher/get-user-list?isParent=0')->assertOk()->getContent();
        $this->assertStringContainsString('Cara', $list);
        $this->assertStringNotContainsString('"Ben"', $list);
    }
}
