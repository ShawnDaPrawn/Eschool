<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Activities\FixtureSelection;
use App\Models\Activities\FixtureSide;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Shared\SharedFixture;
use App\Models\Shared\SharedParticipant;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Activities\Fixtures\Competitions;
use App\Services\Activities\Fixtures\FixtureNotices;
use App\Services\Activities\Fixtures\Fixtures;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Shared\SharedRecords;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\AssertsSameJson;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Records shared between schools and their first use, inter-school fixtures
 * (Action Plan 5.1, 5.2): engineering/specs/platform/shared-records.md and
 * engineering/specs/activities/inter-school-fixtures.md.
 *
 * School A (1) hosts, Baobab High (2) is the opponent and Marula College (3)
 * is a third school that takes no part.
 */
class InterSchoolFixturesTest extends TestCase
{
    use AssertsSameJson;
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private const TODAY = '2026-03-10';

    private User $adminA;

    private User $adminB;

    private User $adminC;

    private User $coach;

    private User $farid;

    private User $gina;

    private User $faridB;

    private int $groupA;

    private int $groupB;

    private int $asha;

    private int $ben;

    private int $ashaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->travelTo(self::TODAY.' 10:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        DB::table('session_years')->whereIn('id', [$this->schoolA->ids['session_year'], $this->schoolB->ids['session_year']])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        config(['modules.groups' => ['always_on' => true]]);

        $this->adminA = $this->superAdmin();
        $this->inA(fn () => $this->adminA->givePermissionTo('activity-groups-manage'));
        $this->adminB = $this->admin(2, 'Baobab Office', 'office.baobab@example.com');
        School::query()->forceCreate(['id' => 3, 'name' => 'Marula College', 'slug' => 'marula-college', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(3);
        $this->adminC = $this->admin(3, 'Marula Office', 'office.marula@example.com');

        $this->coach = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->farid = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $this->faridB = $this->inB(fn () => User::findOrFail($this->schoolB->ids['farid_user']));
        $this->asha = $this->schoolA->ids['asha'];
        $this->ben = $this->schoolA->ids['ben'];
        $this->ashaB = $this->schoolB->ids['asha'];

        $this->groupA = $this->inA(fn () => $this->team($this->schoolA->ids['session_year'], 'Jacaranda Hockey Squad', [$this->asha], $this->schoolA->ids['section_a'], [$this->asha => $this->farid, $this->ben => $this->gina]));
        $this->groupB = $this->inB(fn () => $this->team($this->schoolB->ids['session_year'], 'Baobab Hockey Squad', [$this->ashaB], $this->schoolB->ids['section_a'], [$this->ashaB => $this->faridB]));
        $this->inA(fn () => ActivityStaff::create(['activity_group_id' => $this->groupA, 'user_id' => $this->coach->id, 'role' => 'facilitator', 'cascade' => false,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic coach', 'revision' => 1]));
    }

    /** Each request is its own sign-in: the guard does not remember the last token. */
    private function forgetGuards(): self
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function admin(int $school, string $name, string $email): User
    {
        $user = User::query()->forceCreate(['first_name' => $name, 'last_name' => 'Admin', 'email' => $email, 'password' => bcrypt('Secret-Pass-2026'), 'gender' => 'Female', 'mobile' => '0820000000', 'status' => 1]);
        app(CurrentSchool::class)->run($school, function () use ($user) {
            $user->assignRole('Super Admin');
            $user->givePermissionTo('activity-groups-manage');
        });
        SchoolMembership::query()->where('user_id', $user->id)->update(['starts_on' => '2026-01-01']);

        return $user;
    }

    /** A hockey group with its members, every learner enrolled and each guardian approved. */
    private function team(int $year, string $name, array $members, int $section, array $guardians): int
    {
        $group = ActivityGroup::create(['session_year_id' => $year, 'name' => $name, 'discipline' => 'Hockey', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        foreach ($guardians as $learner => $guardian) {
            Enrolment::create(['learner_id' => $learner, 'session_year_id' => $year, 'class_section_id' => $section, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
            $guardian->forceFill(['email_verified_at' => now()])->save();
            GuardianLink::updateOrCreate(['learner_id' => $learner, 'guardian_user_id' => $guardian->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
            app(ApprovalService::class)->decide($guardian, Students::findOrFail($learner), 'approve', config('identity.consent_version'));
        }
        foreach ($members as $learner) {
            ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic team', 'revision' => 1]);
        }

        return (int) $group->id;
    }

    private function inA(callable $fn): mixed
    {
        return app(CurrentSchool::class)->run(1, $fn);
    }

    private function inB(callable $fn): mixed
    {
        return app(CurrentSchool::class)->run(2, $fn);
    }

    private function as(User $user, int $school, callable $fn): mixed
    {
        $this->be($user);

        return app(CurrentSchool::class)->run($school, $fn);
    }

    private function refused(int $status, callable $fn): void
    {
        try {
            $fn();
            $this->fail("Expected $status");
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        }
    }

    private function op(array $data = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function fixture(array $extra = []): array
    {
        return $this->as($this->adminA, 1, fn () => app(Fixtures::class)->create($this->adminA, $this->op($extra + [
            'activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda U14 A', 'discipline' => 'Hockey', 'on_date' => self::TODAY, 'starts_at' => '14:30',
            'venue' => 'Jacaranda fields', 'field' => 'Field 2', 'host_is_home' => true, 'guest_school_id' => 2,
        ])));
    }

    private function accept(int $id): array
    {
        return $this->as($this->adminB, 2, fn () => app(Fixtures::class)->decide($this->adminB, $id, $this->op(['decision' => 'accept', 'activity_group_id' => $this->groupB, 'team_label' => 'Baobab U14'])));
    }

    private function sideA(int $id, bool $shared, int $revision = 1, ?User $actor = null): array
    {
        $actor ??= $this->adminA;

        return $this->as($actor, 1, fn () => app(Fixtures::class)->saveSide($actor, $id, $this->op(['revision' => $revision, 'team_label' => 'Jacaranda U14 A', 'sheet_shared' => $shared,
            'notes' => 'Private A note about injuries', 'selections' => [['learner_id' => $this->asha, 'sheet_label' => null, 'position' => 'Goalkeeper']]])));
    }

    private function sideB(int $id, bool $shared): array
    {
        return $this->as($this->adminB, 2, fn () => app(Fixtures::class)->saveSide($this->adminB, $id, $this->op(['revision' => 1, 'team_label' => 'Baobab U14', 'sheet_shared' => $shared,
            'notes' => 'Baobab private note', 'selections' => [['learner_id' => $this->ashaB, 'sheet_label' => 'Baobab No. 9', 'position' => 'Forward']]])));
    }

    private function report(User $actor, int $school, int $id, int $host, int $guest, ?string $operation = null): array
    {
        return $this->as($actor, $school, fn () => app(Fixtures::class)->report($actor, $id, ['operation_id' => $operation ?? (string) Str::uuid(), 'host_score' => $host, 'guest_score' => $guest]));
    }

    private function see(User $actor, int $school, int $id): array
    {
        return $this->as($actor, $school, fn () => app(Fixtures::class)->view($actor, $id));
    }

    /** Asha's father (guardian link) and mother (legacy parent record). */
    private function guardiansOfAsha(): array
    {
        $ids = [$this->farid->id, $this->schoolA->ids['mona_user']];
        sort($ids);

        return $ids;
    }

    /** @return list<int> recipients of fixture notices in a school */
    private function noticed(int $school): array
    {
        $ids = Notification::acrossSchools()->where('school_id', $school)->where('type', FixtureNotices::TYPE)->pluck('id')->all();

        return OutboxEvent::query()->where('type', 'notification.requested')->get()->filter(fn ($e) => in_array((int) $e->payload['notification_id'], $ids, true))
            ->flatMap(fn ($e) => $e->payload['user_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    public function test_two_schools_arrange_a_fixture_share_what_they_choose_and_confirm_the_result(): void
    {
        $created = $this->fixture();
        $id = $created['id'];
        $this->assertSame(['host', 'accepted', 'invited'], [$created['my_role'], $created['my_state'], $created['guest']['state']]);
        $this->assertSame('Baobab High', $created['guest']['name']);

        $invited = $this->see($this->adminB, 2, $id);
        $this->assertSame(['guest', 'invited', true], [$invited['my_role'], $invited['my_state'], $invited['can_decide']]);
        $this->assertSame([self::TODAY, '14:30', 'Jacaranda fields', 'Field 2'], [$invited['on_date'], $invited['starts_at'], $invited['venue'], $invited['field']]);

        $accepted = $this->accept($id);
        $this->assertTrue($accepted['open']);
        $this->assertSame('Jacaranda U14 A', $accepted['host']['team_label'], 'once both accepted, team names are shared');
        $this->sideA($id, false);
        $this->sideB($id, true);

        $a = $this->see($this->adminA, 1, $id);
        $this->assertSame('Baobab U14', $a['guest']['team_label']);
        $this->assertSame([['sheet_label' => 'Baobab No. 9', 'position' => 'Forward']], $a['their_sheet'], 'B chose to share its sheet');
        $this->assertSame('Asha D.', $a['side']['selections'][0]['sheet_label'], 'a first name and initial by default');
        $this->assertNull($this->see($this->adminB, 2, $id)['their_sheet'], 'A did not share its sheet');

        $this->assertSame('awaiting', $this->report($this->adminA, 1, $id, 2, 1)['result']['state']);
        $this->assertSame('them', $this->see($this->adminA, 1, $id)['result']['waiting_for']);
        $this->assertSame([], $this->noticed(1));
        $confirmed = $this->report($this->adminB, 2, $id, 2, 1);
        $this->assertSame(['confirmed', 2, 1], [$confirmed['result']['state'], $confirmed['result']['host_score'], $confirmed['result']['guest_score']]);

        // Each school tells its own families, inside its own school.
        $this->assertSame($this->guardiansOfAsha(), $this->noticed(1));
        $this->assertSame([$this->faridB->id, $this->schoolB->ids['mona_user']], $this->noticed(2), 'father and mother');
        $family = $this->as($this->farid, 1, fn () => app(Fixtures::class)->forLearner($this->farid, $this->asha))['items'];
        $this->assertSame([['state' => 'confirmed', 'our_score' => 2, 'their_score' => 1], 'Baobab High', true, 'Jacaranda U14 A'],
            [$family[0]['result'], $family[0]['opponent'], $family[0]['home'], $family[0]['team_label']]);
        $familyB = $this->as($this->faridB, 2, fn () => app(Fixtures::class)->forLearner($this->faridB, $this->ashaB))['items'];
        $this->assertSame([['state' => 'confirmed', 'our_score' => 1, 'their_score' => 2], false], [$familyB[0]['result'], $familyB[0]['home']]);
    }

    public function test_the_wrong_role_is_refused_and_an_assigned_coach_keeps_only_their_own_side(): void
    {
        $theo = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->refused(403, fn () => $this->as($theo, 1, fn () => app(Fixtures::class)->create($theo, $this->op())));
        $this->refused(403, fn () => $this->as($this->farid, 1, fn () => app(Fixtures::class)->listing($this->farid)));
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->refused(404, fn () => $this->see($theo, 1, $id));
        $this->refused(403, fn () => $this->see($this->farid, 1, $id));
        $this->assertSame([], $this->as($theo, 1, fn () => app(Fixtures::class)->listing($theo))['items']);

        // The assigned coach sees and keeps the side, and reports the score...
        $this->assertSame($id, $this->as($this->coach, 1, fn () => app(Fixtures::class)->listing($this->coach))['items'][0]['id']);
        $this->sideA($id, false, 1, $this->coach);
        $this->assertSame('awaiting', $this->report($this->coach, 1, $id, 1, 0)['result']['state']);
        // ...but cannot answer, reschedule or start fixtures or competitions.
        $this->refused(403, fn () => $this->as($this->coach, 1, fn () => app(Fixtures::class)->schedule($this->coach, $id, $this->op())));
        $this->refused(403, fn () => $this->as($this->coach, 1, fn () => app(Competitions::class)->create($this->coach, $this->op())));
        // A guest manager cannot change the host's fixture details.
        $this->refused(403, fn () => $this->as($this->adminB, 2, fn () => app(Fixtures::class)->schedule($this->adminB, $id, $this->op(['revision' => 1, 'on_date' => self::TODAY, 'venue' => 'Elsewhere', 'host_is_home' => true, 'status' => 'scheduled']))));
    }

    public function test_a_third_school_sees_nothing_of_the_fixture_or_competition(): void
    {
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->sideA($id, true);
        $this->report($this->adminA, 1, $id, 3, 3);
        $this->report($this->adminB, 2, $id, 3, 3);
        $competition = $this->as($this->adminA, 1, fn () => app(Competitions::class)->create($this->adminA, $this->op(['name' => 'Highveld Hockey League', 'kind' => 'sport', 'discipline' => 'Hockey', 'season' => '2026', 'points_win' => 3, 'points_draw' => 1, 'points_loss' => 0])))['id'];

        $c = fn (callable $fn) => $this->as($this->adminC, 3, $fn);
        $this->refused(404, fn () => $c(fn () => app(Fixtures::class)->view($this->adminC, $id)));
        $this->refused(404, fn () => $c(fn () => app(Fixtures::class)->decide($this->adminC, $id, $this->op(['decision' => 'accept', 'activity_group_id' => 1, 'team_label' => 'Marula']))));
        $this->refused(404, fn () => $c(fn () => app(Fixtures::class)->report($this->adminC, $id, $this->op(['host_score' => 9, 'guest_score' => 0]))));
        $this->refused(404, fn () => $c(fn () => app(Fixtures::class)->saveSide($this->adminC, $id, $this->op(['revision' => 1, 'team_label' => 'x', 'sheet_shared' => true, 'selections' => []]))));
        $this->refused(404, fn () => $c(fn () => app(Fixtures::class)->schedule($this->adminC, $id, $this->op(['revision' => 1, 'on_date' => self::TODAY, 'venue' => 'Marula', 'host_is_home' => true, 'status' => 'cancelled']))));
        $this->refused(404, fn () => $c(fn () => app(Competitions::class)->view($this->adminC, $competition)));
        $this->refused(404, fn () => $c(fn () => app(Competitions::class)->invite($this->adminC, $competition, $this->op(['school_id' => 3]))));
        $this->assertSame([], $c(fn () => app(Fixtures::class)->listing($this->adminC))['items']);
        $this->assertSame([], $c(fn () => app(Competitions::class)->listing($this->adminC))['items']);
        $this->assertSame(0, $c(fn () => SharedParticipant::query()->count()), 'no participant row reaches the third school');
        $this->assertSame([], $this->noticed(3));
        $this->assertSame('scheduled', SharedFixture::findOrFail($id)->status);
    }

    public function test_the_opposing_school_never_sees_your_roster_learners_or_notes(): void
    {
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->sideA($id, false);
        $this->sideB($id, false);
        $this->report($this->adminA, 1, $id, 1, 0);
        $this->report($this->adminB, 2, $id, 1, 0);

        $b = json_encode($this->see($this->adminB, 2, $id));
        foreach (['Asha Demo', 'Asha D.', 'Goalkeeper', 'Private A note', 'Jacaranda Hockey Squad', '"learner_id":'.$this->asha.','] as $secret) {
            $this->assertStringNotContainsString($secret, $b);
        }
        $this->assertNull(json_decode($b, true)['their_sheet']);
        $a = json_encode($this->see($this->adminA, 1, $id));
        foreach (['Baobab Demo', 'Baobab No. 9', 'Forward', 'Baobab private note', 'Baobab Hockey Squad'] as $secret) {
            $this->assertStringNotContainsString($secret, $a);
        }

        // Sharing the sheet shares the labels and positions only, never names or ids.
        $this->sideA($id, true, $this->see($this->adminA, 1, $id)['side']['revision']);
        $shared = $this->see($this->adminB, 2, $id);
        $this->assertSame([['sheet_label' => 'Asha D.', 'position' => 'Goalkeeper']], $shared['their_sheet']);
        $this->assertStringNotContainsString('Asha Demo', json_encode($shared));
        $this->assertStringNotContainsString('Private A note', json_encode($shared));

        // The opposing school's guardians see only their own child's team.
        $familyB = json_encode($this->as($this->faridB, 2, fn () => app(Fixtures::class)->forLearner($this->faridB, $this->ashaB)));
        $this->assertStringNotContainsString('Asha D.', $familyB);
        $this->refused(404, fn () => $this->as($this->faridB, 2, fn () => app(Fixtures::class)->forLearner($this->faridB, $this->asha)));

        // Only declared columns of another school's rows can ever be read.
        foreach ([[FixtureSide::class, ['notes']], [FixtureSide::class, ['activity_group_id']], [FixtureSelection::class, ['learner_id']], [Students::class, ['id']]] as [$model, $columns]) {
            try {
                $this->as($this->adminB, 2, fn () => app(SharedRecords::class)->published($model, 1, $columns, fn ($q) => $q));
                $this->fail("Read $model ".implode(',', $columns));
            } catch (LogicException) {
            }
        }
    }

    public function test_a_school_that_has_not_accepted_sees_nothing_beyond_the_other_schools_name(): void
    {
        $id = $this->fixture()['id'];
        $this->sideA($id, true);
        $invited = $this->see($this->adminB, 2, $id);
        $this->assertSame(['invited', false, null, null, null], [$invited['my_state'], $invited['open'], $invited['host']['team_label'], $invited['their_sheet'], $invited['side']]);
        $this->assertSame('none', $invited['result']['state']);
        $this->assertStringNotContainsString('Jacaranda U14 A', json_encode($invited));
        $this->assertStringNotContainsString('Asha', json_encode($invited));
        $host = $this->see($this->adminA, 1, $id);
        $this->assertSame(['Baobab High', 'invited', null], [$host['guest']['name'], $host['guest']['state'], $host['guest']['team_label']]);
        // No score until both accepted; the invited school cannot write its side.
        $this->refused(409, fn () => $this->report($this->adminA, 1, $id, 1, 0));
        $this->refused(409, fn () => $this->sideB($id, true));

        $this->as($this->adminB, 2, fn () => app(Fixtures::class)->decide($this->adminB, $id, $this->op(['decision' => 'decline'])));
        $declined = $this->see($this->adminB, 2, $id);
        $this->assertSame(['declined', false, null], [$declined['my_state'], $declined['open'], $declined['their_sheet']]);
        $this->assertStringNotContainsString('Jacaranda U14 A', json_encode($declined));
        $this->assertSame('declined', $this->see($this->adminA, 1, $id)['guest']['state']);
        $this->refused(409, fn () => $this->accept($id));
        $this->refused(409, fn () => $this->report($this->adminA, 1, $id, 1, 0));
        $this->assertSame(0, $this->inB(fn () => FixtureSide::query()->count()));
        $this->refused(404, fn () => $this->as($this->farid, 1, fn () => app(Fixtures::class)->forLearner($this->farid, $this->ben)));
        $this->assertSame([], $this->as($this->farid, 1, fn () => app(Fixtures::class)->forLearner($this->farid, $this->asha))['items'], 'families see a fixture once both schools accepted');
    }

    public function test_a_guardian_of_a_learner_not_in_the_team_sees_nothing(): void
    {
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->sideA($id, false);
        $this->report($this->adminA, 1, $id, 4, 2);
        $this->report($this->adminB, 2, $id, 4, 2);

        $this->assertNotContains($this->gina->id, $this->noticed(1));
        $this->assertSame([], $this->as($this->gina, 1, fn () => app(Fixtures::class)->forLearner($this->gina, $this->ben))['items']);
        $this->refused(404, fn () => $this->as($this->gina, 1, fn () => app(Fixtures::class)->forLearner($this->gina, $this->asha)));
        $notice = Notification::acrossSchools()->where('school_id', 1)->where('type', FixtureNotices::TYPE)->sole();
        // Only routing identifiers may enter delivery, regardless of JSON key order.
        $this->assertSameJson(['link' => FixtureNotices::LINK, 'fixture_id' => $id, 'result_revision' => 1], $notice->delivery_payload);
        $this->inA(function () use ($notice) {
            UserNotification::firstOrCreate(['user_id' => $this->gina->id, 'notification_id' => $notice->id]);
            UserNotification::firstOrCreate(['user_id' => $this->farid->id, 'notification_id' => $notice->id]);
            $this->be($this->gina);
            $this->assertSame(0, NotificationVisibility::forUser(Notification::query()->where('type', FixtureNotices::TYPE), $this->gina->id, 4)->count());
            $this->assertFalse(app(FixtureNotices::class)->allows($this->gina, $notice->type, $notice->delivery_payload));
            $this->be($this->farid);
            $this->assertSame(1, NotificationVisibility::forUser(Notification::query()->where('type', FixtureNotices::TYPE), $this->farid->id, 4)->count());
            $this->assertTrue(app(FixtureNotices::class)->allows($this->farid, $notice->type, $notice->delivery_payload));
            // Taken off the team sheet: the notice is withdrawn too.
            FixtureSelection::query()->delete();
            $this->assertSame(0, NotificationVisibility::forUser(Notification::query()->where('type', FixtureNotices::TYPE), $this->farid->id, 4)->count());
            $this->assertFalse(app(FixtureNotices::class)->allows($this->farid, $notice->type, $notice->delivery_payload));
        });
    }

    public function test_score_confirmation_is_idempotent_and_conflicting_scores_are_disputed_until_they_agree(): void
    {
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->sideA($id, false);
        $operation = (string) Str::uuid();
        $first = $this->report($this->adminA, 1, $id, 2, 1, $operation);
        $this->assertSame($first, $this->report($this->adminA, 1, $id, 2, 1, $operation), 'the same operation returns its receipt');
        $this->refused(409, fn () => $this->report($this->adminA, 1, $id, 5, 1, $operation));
        $this->report($this->adminA, 1, $id, 2, 1);

        $disputed = $this->report($this->adminB, 2, $id, 1, 1);
        $this->assertSame(['disputed', null, null], [$disputed['result']['state'], $disputed['result']['host_score'], $disputed['result']['guest_score']]);
        $this->assertSame(['host_score' => 2, 'guest_score' => 1], $disputed['result']['their_report']);
        $this->assertSame(['host_score' => 1, 'guest_score' => 1], $this->see($this->adminA, 1, $id)['result']['their_report']);
        $this->assertSame([], $this->noticed(1));

        $this->assertSame('confirmed', $this->report($this->adminB, 2, $id, 2, 1)['result']['state']);
        $this->report($this->adminB, 2, $id, 2, 1);
        $this->report($this->adminA, 1, $id, 2, 1);
        $this->assertSame($this->guardiansOfAsha(), $this->noticed(1), 'agreeing again sends nothing more');
        $this->assertSame(1, SharedFixture::findOrFail($id)->result_revision);

        // A school that changes its report reopens the result; the old notice is withdrawn.
        $notice = Notification::acrossSchools()->where('school_id', 1)->where('type', FixtureNotices::TYPE)->sole();
        $reopened = $this->report($this->adminA, 1, $id, 3, 1);
        $this->assertSame(['disputed', null], [$reopened['result']['state'], $reopened['result']['host_score']]);
        $this->assertFalse($this->as($this->farid, 1, fn () => app(FixtureNotices::class)->allows($this->farid, $notice->type, $notice->delivery_payload)));
        $this->assertSame('pending', $this->as($this->farid, 1, fn () => app(Fixtures::class)->forLearner($this->farid, $this->asha))['items'][0]['result']['state']);
        $this->assertSame('confirmed', $this->report($this->adminB, 2, $id, 3, 1)['result']['state']);
        $this->assertSame(2, SharedFixture::findOrFail($id)->result_revision);

        // A future fixture cannot be scored yet.
        $later = $this->fixture(['on_date' => '2026-04-01'])['id'];
        $this->accept($later);
        $this->refused(422, fn () => $this->report($this->adminA, 1, $later, 1, 0));
    }

    public function test_an_opponent_outside_navigateed_is_confirmed_by_the_host(): void
    {
        $created = $this->fixture(['guest_school_id' => null, 'external_opponent' => 'Riverside Hockey Club', 'host_is_home' => false]);
        $this->assertSame(['Riverside Hockey Club', true, true], [$created['guest']['name'], $created['guest']['external'], $created['open']]);
        $this->sideA($created['id'], false);
        $result = $this->report($this->adminA, 1, $created['id'], 0, 2);
        $this->assertSame(['confirmed', 0, 2], [$result['result']['state'], $result['result']['host_score'], $result['result']['guest_score']]);
        $this->assertSame($this->guardiansOfAsha(), $this->noticed(1));
        $family = $this->as($this->farid, 1, fn () => app(Fixtures::class)->forLearner($this->farid, $this->asha))['items'][0];
        $this->assertSame(['Riverside Hockey Club', false, 0, 2], [$family['opponent'], $family['home'], $family['result']['our_score'], $family['result']['their_score']]);
        $this->assertSame(0, $this->inB(fn () => SharedParticipant::query()->count()));
        // Exactly one kind of opponent.
        $this->refused(422, fn () => $this->fixture(['external_opponent' => 'Riverside Hockey Club']));
        $this->refused(422, fn () => $this->fixture(['guest_school_id' => 1]));
    }

    public function test_competition_standings_are_shared_with_accepted_members_only(): void
    {
        $competition = $this->as($this->adminA, 1, fn () => app(Competitions::class)->create($this->adminA, $this->op(['name' => 'Highveld Hockey League', 'kind' => 'sport', 'discipline' => 'Hockey', 'season' => '2026', 'points_win' => 3, 'points_draw' => 1, 'points_loss' => 0])))['id'];
        // B is not a member yet: no fixture against it in the league.
        $this->refused(422, fn () => $this->fixture(['shared_competition_id' => $competition]));
        $this->refused(422, fn () => $this->as($this->adminA, 1, fn () => app(Competitions::class)->create($this->adminA, $this->op(['name' => 'Debate', 'kind' => 'academic', 'discipline' => 'Debating', 'season' => '2026', 'points_win' => 3, 'points_draw' => 1, 'points_loss' => 0]))));
        $this->as($this->adminA, 1, fn () => app(Competitions::class)->invite($this->adminA, $competition, $this->op(['school_id' => 2])));
        $invited = $this->as($this->adminB, 2, fn () => app(Competitions::class)->view($this->adminB, $competition));
        $this->assertSame(['invited', [], [], true], [$invited['my_state'], $invited['members'], $invited['standings'], $invited['can_decide']]);
        $this->refused(403, fn () => $this->as($this->adminB, 2, fn () => app(Competitions::class)->invite($this->adminB, $competition, $this->op(['school_id' => 3]))));
        $this->as($this->adminB, 2, fn () => app(Competitions::class)->decide($this->adminB, $competition, $this->op(['decision' => 'accept'])));

        $league = $this->fixture(['shared_competition_id' => $competition]);
        $this->assertSame('Highveld Hockey League', $league['competition']['name']);
        $this->accept($league['id']);
        $this->report($this->adminA, 1, $league['id'], 2, 1);
        $this->report($this->adminB, 2, $league['id'], 2, 1);
        $friendly = $this->fixture(['shared_competition_id' => $competition, 'guest_school_id' => null, 'external_opponent' => 'Riverside Hockey Club']);
        $this->report($this->adminA, 1, $friendly['id'], 1, 1);
        $this->fixture();  // Not in the league: never counted.

        $table = $this->as($this->adminB, 2, fn () => app(Competitions::class)->view($this->adminB, $competition))['standings'];
        $teams = array_column($table, 'team');
        $this->assertSame(['Jacaranda U14 A', 'Riverside Hockey Club', 'Baobab High Baobab U14'], [substr($teams[0], -15), $teams[1], $teams[2]]);
        $this->assertSame([2, 1, 1, 0, 3, 2, 4], [$table[0]['played'], $table[0]['won'], $table[0]['drawn'], $table[0]['lost'], $table[0]['scored'], $table[0]['conceded'], $table[0]['points']]);
        $this->assertSame([1, 0, 0, 1, 0], [$table[2]['played'], $table[2]['won'], $table[2]['drawn'], $table[2]['lost'], $table[2]['points']]);
        $this->assertStringNotContainsString('Asha', json_encode($table));
    }

    public function test_the_api_follows_the_contract_for_both_schools(): void
    {
        $tokenA = $this->adminA->createToken('test')->plainTextToken;
        $tokenB = $this->adminB->createToken('test')->plainTextToken;
        $api = fn (string $token, int $school) => $this->forgetGuards()->flushHeaders()->withToken($token)->withHeaders(['X-School-Id' => (string) $school]);

        $api($tokenA, 1)->getJson('/api/v2/activities/fixture-schools?search=Baob')->assertOk()->assertJsonPath('data.items', [['id' => 2, 'name' => 'Baobab High']]);
        $api($tokenA, 1)->getJson('/api/v2/activities/fixture-schools?search=ab')->assertUnprocessable();
        $id = $api($tokenA, 1)->postJson('/api/v2/activities/fixtures', $this->op(['activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda U14 A', 'discipline' => 'Hockey',
            'on_date' => self::TODAY, 'starts_at' => '14:30', 'venue' => 'Jacaranda fields', 'host_is_home' => true, 'guest_school_id' => 2]))->assertOk()->json('data.id');
        $api($tokenB, 2)->getJson('/api/v2/activities/fixtures')->assertOk()->assertJsonPath('data.items.0.my_state', 'invited');
        $api($tokenB, 2)->postJson("/api/v2/activities/fixtures/$id/decision", $this->op(['decision' => 'accept', 'activity_group_id' => $this->groupB, 'team_label' => 'Baobab U14']))->assertOk()->assertJsonPath('data.open', true);
        $api($tokenA, 1)->putJson("/api/v2/activities/fixtures/$id/side", $this->op(['revision' => 1, 'team_label' => 'Jacaranda U14 A', 'sheet_shared' => true, 'notes' => null,
            'selections' => [['learner_id' => $this->asha, 'sheet_label' => 'No. 1', 'position' => null]]]))->assertOk()->assertJsonPath('data.side.selections.0.sheet_label', 'No. 1');
        $api($tokenA, 1)->putJson("/api/v2/activities/fixtures/$id", $this->op(['revision' => 1, 'on_date' => self::TODAY, 'starts_at' => '15:00', 'venue' => 'Jacaranda fields', 'field' => null, 'host_is_home' => true, 'status' => 'scheduled']))
            ->assertOk()->assertJsonPath('data.starts_at', '15:00');
        $api($tokenA, 1)->postJson("/api/v2/activities/fixtures/$id/result", $this->op(['host_score' => 1, 'guest_score' => 0]))->assertOk()->assertJsonPath('data.result.state', 'awaiting');
        $api($tokenB, 2)->postJson("/api/v2/activities/fixtures/$id/result", $this->op(['host_score' => 1, 'guest_score' => 0]))->assertOk()->assertJsonPath('data.result.state', 'confirmed');
        $api($tokenB, 2)->getJson("/api/v2/activities/fixtures/$id")->assertOk()->assertJsonPath('data.their_sheet.0.sheet_label', 'No. 1');
        // Another school's fixture id, a school the user is not at, and the wrong role.
        $api($tokenB, 2)->getJson('/api/v2/activities/fixtures/'.($id + 1000))->assertNotFound();
        $api($tokenA, 2)->getJson("/api/v2/activities/fixtures/$id")->assertForbidden();
        $tokenC = $this->adminC->createToken('test')->plainTextToken;
        $api($tokenC, 3)->getJson("/api/v2/activities/fixtures/$id")->assertNotFound();
        $api($tokenC, 3)->postJson("/api/v2/activities/fixtures/$id/result", $this->op(['host_score' => 9, 'guest_score' => 0]))->assertNotFound();
        $faridToken = $this->farid->createToken('test')->plainTextToken;
        $api($faridToken, 1)->getJson("/api/v2/activities/fixtures/$id")->assertForbidden();
        $api($faridToken, 1)->getJson("/api/v2/activities/learners/{$this->asha}/fixtures")->assertOk()->assertJsonPath('data.items.0.result.our_score', 1);
        $api($faridToken, 1)->getJson("/api/v2/activities/learners/{$this->ben}/fixtures")->assertNotFound();

        $competition = $api($tokenA, 1)->postJson('/api/v2/activities/competitions', $this->op(['name' => 'Highveld Hockey League', 'kind' => 'sport', 'discipline' => 'Hockey', 'season' => '2026', 'points_win' => 3, 'points_draw' => 1, 'points_loss' => 0]))
            ->assertOk()->json('data.id');
        $api($tokenA, 1)->postJson("/api/v2/activities/competitions/$competition/invitations", $this->op(['school_id' => 2]))->assertOk()->assertJsonPath('data.members.1.state', 'invited');
        $api($tokenB, 2)->getJson('/api/v2/activities/competitions')->assertOk()->assertJsonPath('data.items.0.my_state', 'invited');
        $api($tokenB, 2)->postJson("/api/v2/activities/competitions/$competition/decision", $this->op(['decision' => 'accept']))->assertOk()->assertJsonPath('data.my_state', 'accepted');
        $api($tokenB, 2)->getJson("/api/v2/activities/competitions/$competition")->assertOk()->assertJsonPath('data.standings', []);
        $api($tokenC, 3)->getJson("/api/v2/activities/competitions/$competition")->assertNotFound();
    }

    public function test_the_panel_pages_work_for_managers_only(): void
    {
        $this->actingAs($this->adminA);
        $this->get('/activity-fixtures')->assertOk()->assertSee('Arrange a fixture')->assertDontSee('Baobab');
        $this->get('/activity-fixtures?school_search=Baobab')->assertOk()->assertSee('Baobab High');
        $this->post('/activity-fixtures', $this->op(['activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda U14 A', 'discipline' => 'Hockey', 'on_date' => self::TODAY,
            'starts_at' => '', 'venue' => 'Jacaranda fields', 'field' => '', 'host_is_home' => '1', 'guest_school_id' => '2', 'external_opponent' => '', 'shared_competition_id' => '']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $id = (int) SharedFixture::query()->latest('id')->value('id');
        $this->get("/activity-fixtures/$id")->assertOk()->assertSee('Jacaranda U14 A')->assertSee('has not answered yet');
        $this->post('/activity-fixtures', $this->op(['activity_group_id' => $this->groupA, 'team_label' => 'X', 'discipline' => 'Hockey', 'on_date' => self::TODAY, 'venue' => 'Jacaranda fields', 'host_is_home' => '1']))
            ->assertRedirect('/activity-fixtures')->assertSessionHasErrors();

        $this->accept($id);
        $this->actingAs($this->adminA);
        $this->post("/activity-fixtures/$id/side", $this->op(['revision' => 1, 'team_label' => 'Jacaranda U14 A', 'pick' => [$this->asha], 'label' => [$this->asha => 'No. 4'], 'position' => [$this->asha => ''], 'sheet_shared' => '1', 'notes' => '']))
            ->assertRedirect("/activity-fixtures/$id")->assertSessionHasNoErrors();
        $this->post("/activity-fixtures/$id/result", $this->op(['host_score' => '2', 'guest_score' => '0']))->assertRedirect("/activity-fixtures/$id")->assertSessionHasNoErrors();
        $this->get("/activity-fixtures/$id")->assertOk()->assertSee('No. 4')->assertSee('Waiting for the other school to report the score.');
        $this->post('/activity-fixtures/competitions', $this->op(['name' => 'Highveld Hockey League', 'kind' => 'sport', 'discipline' => 'Hockey', 'season' => '2026', 'points_win' => '3', 'points_draw' => '1', 'points_loss' => '0']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $competition = (int) DB::table('shared_competitions')->latest('id')->value('id');
        $this->get("/activity-fixtures/competitions/$competition")->assertOk()->assertSee('Standings');

        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']));
        $this->get('/activity-fixtures')->assertForbidden();
        $this->get("/activity-fixtures/$id")->assertForbidden();
        $this->post("/activity-fixtures/$id/result", $this->op(['host_score' => '9', 'guest_score' => '0']))->assertForbidden();
        $this->actingAs($this->adminC);
        $this->get("/activity-fixtures/$id")->assertNotFound();
    }

    /**
     * Both schools report at once: the second transaction waits on the fixture
     * lock, but its first plain read (the authority checks in once()) already
     * fixed its REPEATABLE READ snapshot. The other school's report must be
     * read with a locking read after the fixture lock, which reads the latest
     * committed row, or matching scores stay "awaiting" for good.
     */
    public function test_the_other_schools_report_is_read_from_current_locked_state(): void
    {
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->report($this->adminB, 2, $id, 2, 1);
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = ['sql' => strtolower($query->sql), 'bindings' => $query->bindings];
        });

        $this->assertSame('confirmed', $this->report($this->adminA, 1, $id, 2, 1)['result']['state']);

        $lock = collect($queries)->search(fn ($q) => str_contains($q['sql'], 'from `shared_fixtures`') && str_contains($q['sql'], 'for update'));
        $read = collect($queries)->search(fn ($q) => str_starts_with($q['sql'], 'select `reported_host_score`, `reported_guest_score` from `shared_fixture_sides`') && in_array(2, $q['bindings'], true));
        $this->assertIsInt($lock, 'the fixture row is locked');
        $this->assertIsInt($read, 'Baobab\'s report is read');
        $this->assertGreaterThan($lock, $read, 'the report is read after the fixture lock');
        $this->assertMatchesRegularExpression('/lock in share mode|for share|for update/', $queries[$read]['sql'], 'with a locking read');
    }

    /**
     * The same race, simulated with two connections: a report committed after
     * the transaction's first read is invisible to a plain read, and visible
     * to the locking read report() uses.
     */
    public function test_a_locking_read_sees_a_report_committed_after_the_transactions_snapshot(): void
    {
        $connection = config('database.connections.'.config('database.default'));
        config(['database.connections.fixtures_reader' => $connection, 'database.connections.fixtures_writer' => $connection]);
        $writer = DB::connection('fixtures_writer');
        $reader = DB::connection('fixtures_reader');
        $writer->statement('SET FOREIGN_KEY_CHECKS=0');
        $sideId = $writer->table('shared_fixture_sides')->insertGetId(['school_id' => 2, 'shared_fixture_id' => 990001, 'activity_group_id' => 990001, 'team_label' => 'Baobab U14',
            'sheet_shared' => 0, 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $default = DB::getDefaultConnection();
        try {
            DB::setDefaultConnection('fixtures_reader');
            $reader->beginTransaction();
            try {
                // The transaction's first plain read fixes its snapshot.
                $this->assertNull($reader->table('shared_fixture_sides')->where('id', $sideId)->value('reported_host_score'));
                // Baobab's own transaction commits its report meanwhile.
                $writer->table('shared_fixture_sides')->where('id', $sideId)->update(['reported_host_score' => 2, 'reported_guest_score' => 1]);
                $read = fn (bool $current) => $this->inA(fn () => app(SharedRecords::class)->published(FixtureSide::class, 2, ['reported_host_score', 'reported_guest_score'],
                    fn ($q) => $q->where('shared_fixture_id', 990001), $current)->first());
                $this->assertNull($read(false)->reported_host_score, 'a plain read still sees the snapshot');
                $this->assertSame([2, 1], [$read(true)->reported_host_score, $read(true)->reported_guest_score], 'the locking read sees the committed report');
            } finally {
                $reader->rollBack();
            }
        } finally {
            DB::setDefaultConnection($default);
            $writer->table('shared_fixture_sides')->where('id', $sideId)->delete();
            $writer->statement('SET FOREIGN_KEY_CHECKS=1');
            DB::purge('fixtures_reader');
            DB::purge('fixtures_writer');
        }
    }

    public function test_a_new_date_must_suit_both_teams_and_their_team_sheets(): void
    {
        $id = $this->fixture()['id'];
        $this->accept($id);
        $this->sideA($id, true);
        $this->sideB($id, true);
        $move = fn (string $date, string $status = 'scheduled') => $this->as($this->adminA, 1, fn () => app(Fixtures::class)->schedule($this->adminA, $id, $this->op([
            'revision' => $this->see($this->adminA, 1, $id)['revision'], 'on_date' => $date, 'starts_at' => null, 'venue' => 'Jacaranda fields', 'field' => null, 'host_is_home' => true, 'status' => $status])));
        $refusal = function (callable $fn): string {
            try {
                $fn();
                $this->fail('The new date was accepted');
            } catch (HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());

                return $e->getMessage();
            }
        };

        // Baobab's group season ends before the new date.
        $this->inB(fn () => ActivityGroup::query()->whereKey($this->groupB)->update(['ends_on' => '2026-03-31']));
        $message = $refusal(fn () => $move('2026-04-15'));
        $this->assertStringNotContainsString('Baobab', $message);
        $this->inB(fn () => ActivityGroup::query()->whereKey($this->groupB)->update(['ends_on' => '2026-12-31']));

        // A learner on our own sheet leaves the group before the new date.
        $this->inA(fn () => ActivityMembership::query()->where('learner_id', $this->asha)->update(['ends_on' => '2026-04-10']));
        $refusal(fn () => $move('2026-04-15'));
        $this->inA(fn () => ActivityMembership::query()->where('learner_id', $this->asha)->update(['ends_on' => '2026-12-31']));

        // A learner on Baobab's sheet leaves its group: refused, naming nobody.
        $this->inB(fn () => ActivityMembership::query()->where('learner_id', $this->ashaB)->update(['ends_on' => '2026-04-10']));
        $message = $refusal(fn () => $move('2026-04-15'));
        $this->assertStringNotContainsString('Asha', $message);
        $this->assertStringNotContainsString('Baobab', $message);
        $this->assertSame(self::TODAY, SharedFixture::findOrFail($id)->on_date->toDateString(), 'nothing moved');

        // Cancelling never needs the teams to fit.
        $cancelled = $move(self::TODAY, 'cancelled');
        $this->assertSame('cancelled', $cancelled['status']);
        $this->inB(fn () => ActivityMembership::query()->where('learner_id', $this->ashaB)->update(['ends_on' => '2026-12-31']));

        // A date that suits both teams and both sheets moves, sheets intact.
        $other = $this->fixture()['id'];
        $this->accept($other);
        $this->sideA($other, true);
        $moved = $this->as($this->adminA, 1, fn () => app(Fixtures::class)->schedule($this->adminA, $other, $this->op([
            'revision' => 1, 'on_date' => '2026-04-15', 'starts_at' => '10:00', 'venue' => 'Jacaranda fields', 'field' => null, 'host_is_home' => true, 'status' => 'scheduled'])));
        $this->assertSame(['2026-04-15', 1], [$moved['on_date'], count($moved['side']['selections'])]);
    }

    public function test_shared_records_are_only_reached_through_the_shared_records_boundary(): void
    {
        foreach ((array) config('shared_records.published') as $columns) {
            $this->assertSame([], array_intersect($columns, ['learner_id', 'notes', 'activity_group_id', 'reported_by', 'school_id']));
        }
        $allowed = ['app/Services/Activities/Fixtures/', 'app/Services/Platform/Shared/', 'app/Models/Shared/', 'config/shared_records.php', 'app/Services/Platform/Tenancy/SchoolOwnedTables.php'];
        foreach ([...File::allFiles(app_path()), ...File::allFiles(config_path())] as $file) {
            $path = str_replace(base_path().'/', '', $file->getPathname());
            if (collect($allowed)->contains(fn ($prefix) => str_starts_with($path, $prefix))) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/\bShared(Fixture|Competition|Participant)\b|shared_(fixtures|competitions|participants)\b/', $file->getContents(), "$path reads shared records directly");
        }
    }
}
