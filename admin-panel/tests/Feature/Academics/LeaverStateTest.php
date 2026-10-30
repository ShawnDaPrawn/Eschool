<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Leave;
use App\Models\People\Enrolment;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\NewYearService;
use App\Services\People\Leavers\AlumniInvitations;
use App\Services\People\Leavers\LeaverState;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Testing\TestResponse;
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

    public function test_the_wizard_preview_names_the_last_day_and_a_late_switch_moves_it_to_next_march(): void
    {
        $this->actingAs($this->superAdmin())->get(route('new-year.index'))->assertOk()->assertSee('until 31 Mar 2027');
        $this->travelTo(now()->setDate(2027, 5, 2));
        app(CurrentSchool::class)->set(1);
        $this->assertSame('2028-03-31', app(LeaverState::class)->defaultUntil(SessionYear::findOrFail($this->next)));
    }
}
