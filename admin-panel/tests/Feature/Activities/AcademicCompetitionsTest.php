<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Academics\GradeLevel;
use App\Models\Activities\AcademicAwardLink;
use App\Models\Activities\AcademicReasoningReview;
use App\Models\Activities\AcademicResult;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Ai\AiFlag;
use App\Models\Ai\AiUsage;
use App\Models\ClassSection;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardPresentation;
use App\Models\Shared\SharedFixture;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\Fixtures\AcademicAwards;
use App\Services\Activities\Fixtures\AcademicContests;
use App\Services\Activities\Fixtures\FixtureNotices;
use App\Services\Activities\Fixtures\Fixtures;
use App\Services\Activities\Fixtures\ReasoningFeedbackModeration;
use App\Services\Activities\Fixtures\ReasoningReviews;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Recognition\RecognitionCatalog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Academic competitions and debating between schools (Action Plan 5.3):
 * engineering/specs/activities/academic-competitions.md.
 *
 * School A (1) hosts a debate against Baobab High (2); Marula College (3)
 * takes no part. Tara is School A's adjudicator, Theo a teacher with no part,
 * Asha School A's entrant (father Farid) and Ben a learner not entered
 * (guardian Gina). AI feedback runs through the real gateway with a synthetic
 * provider that records exactly what the model would have seen.
 */
class AcademicCompetitionsTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private const TODAY = '2026-03-10';

    private const SPEECH = 'Asha argued that school uniforms reduce costs for families, because one set of clothes lasts all year. Jacaranda Debaters rely on a survey of 200 parents. '
        .'Baobab High and the Baobab Speakers say uniforms limit expression, but that ignores the cost point entirely. Tara Demo adjudicated. Everyone agrees with us, so we must be right.';

    private User $adminA;

    private User $adminB;

    private User $adminC;

    private User $tara;

    private User $theo;

    private User $farid;

    private User $gina;

    private User $faridB;

    private int $groupA;

    private int $groupB;

    private int $asha;

    private int $ben;

    private int $ashaB;

    private int $award;

    /** The feature's settings as config/ai.php ships them. */
    private array $feature;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->travelTo(self::TODAY.' 10:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        DB::table('session_years')->whereIn('id', [$this->schoolA->ids['session_year'], $this->schoolB->ids['session_year']])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        config(['modules.groups' => ['always_on' => true], 'modules.awards' => ['always_on' => true]]);
        ReasoningFakeProvider::reset();
        $this->feature = config('ai.inference.features.reasoning_feedback');

        $this->adminA = $this->superAdmin();
        $this->inA(fn () => $this->adminA->givePermissionTo('activity-groups-manage'));
        $this->adminB = $this->admin(2, 'Baobab Office', 'office.baobab@example.com');
        School::query()->forceCreate(['id' => 3, 'name' => 'Marula College', 'slug' => 'marula-college', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(3);
        $this->adminC = $this->admin(3, 'Marula Office', 'office.marula@example.com');

        $this->tara = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->theo = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->farid = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $this->faridB = $this->inB(fn () => User::findOrFail($this->schoolB->ids['farid_user']));
        $this->asha = $this->schoolA->ids['asha'];
        $this->ben = $this->schoolA->ids['ben'];
        $this->ashaB = $this->schoolB->ids['asha'];

        $this->groupA = $this->inA(fn () => $this->team($this->schoolA->ids['session_year'], 'Jacaranda Debating Society', [$this->asha], $this->schoolA->ids['section_a'], [$this->asha => $this->farid, $this->ben => $this->gina]));
        $this->groupB = $this->inB(fn () => $this->team($this->schoolB->ids['session_year'], 'Baobab Debating Society', [$this->ashaB], $this->schoolB->ids['section_a'], [$this->ashaB => $this->faridB]));
        $this->award = $this->inA(fn () => $this->awards());
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

    /** A debating group with its members, every learner enrolled and each guardian approved. */
    private function team(int $year, string $name, array $members, int $section, array $guardians): int
    {
        $group = ActivityGroup::create(['session_year_id' => $year, 'name' => $name, 'discipline' => 'Debating', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
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

    /** School A's own award catalogue: a school-wide category with a repeatable debating award. */
    private function awards(): int
    {
        $grade = (int) GradeLevel::query()->where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $grade])->save();
        $catalog = app(RecognitionCatalog::class);
        $this->be($this->adminA);
        $category = $catalog->awardCategory($this->adminA, $this->op(['name' => 'Speaking and debating', 'parent_id' => null, 'scope_kind' => 'school', 'grade_level_id' => null, 'subject_id' => null, 'activity_group_id' => null, 'active' => true]));

        return (int) $catalog->definition($this->adminA, $this->op(['award_category_id' => $category->id, 'name' => 'Debating colours', 'criteria' => 'Represented the school in a debate', 'frequency' => 'repeatable', 'active' => true]))->id;
    }

    private function ai(array $changes = [], bool $module = true): void
    {
        config(['ai.inference.enabled' => true, 'ai.inference.features.reasoning_feedback' => $changes + array_replace($this->feature, [
            'enabled' => true, 'provider' => 'synthetic', 'provider_class' => ReasoningFakeProvider::class, 'model' => 'synthetic-reasoning-model', 'cents_per_million_tokens' => 100,
        ])]);
        config(['modules.reasoning_feedback' => $module ? ['always_on' => true] : ['default' => false, 'dependencies' => []]]);
        // The shared moderation provider is the only network call faked; the feature's own rules still run.
        $this->app->instance(OpenAiModerator::class, new ReasoningFakeModerator);
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

    /** A debate hosted by School A against Baobab, accepted, with both sheets and Tara as School A's adjudicator. */
    private function debate(bool $accepted = true): int
    {
        $id = $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->create($this->adminA, $this->op([
            'kind' => 'debate', 'motion' => 'This house would abolish school uniforms', 'format' => 'World Schools', 'rounds' => 1, 'host_stance' => 'opposition',
            'activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda Debaters', 'discipline' => 'Debating', 'on_date' => self::TODAY, 'starts_at' => '14:00',
            'venue' => 'Jacaranda hall', 'host_is_home' => true, 'guest_school_id' => 2,
        ])))['id'];
        if (! $accepted) {
            return $id;
        }
        $this->as($this->adminB, 2, fn () => app(Fixtures::class)->decide($this->adminB, $id, $this->op(['decision' => 'accept', 'activity_group_id' => $this->groupB, 'team_label' => 'Baobab Speakers'])));
        $this->as($this->adminA, 1, fn () => app(Fixtures::class)->saveSide($this->adminA, $id, $this->op(['revision' => 1, 'team_label' => 'Jacaranda Debaters', 'sheet_shared' => false,
            'notes' => 'Private A note', 'selections' => [['learner_id' => $this->asha, 'sheet_label' => null, 'position' => 'First speaker']]])));
        $this->as($this->adminB, 2, fn () => app(Fixtures::class)->saveSide($this->adminB, $id, $this->op(['revision' => 1, 'team_label' => 'Baobab Speakers', 'sheet_shared' => true,
            'notes' => 'Baobab private note', 'selections' => [['learner_id' => $this->ashaB, 'sheet_label' => 'Baobab Speaker 1', 'position' => 'First speaker']]])));
        $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->adjudicators($this->adminA, $id, $this->op(['adjudicators' => [['user_id' => $this->tara->id], ['name' => 'Outside Judge Nomvula']]])));

        return $id;
    }

    private function judged(User $actor, int $school, int $id, string $state, int $host, int $guest, string $winner, array $entrants = []): array
    {
        return $this->as($actor, $school, function () use ($actor, $id, $state, $host, $guest, $winner, $entrants) {
            $revision = AcademicResult::query()->where('shared_fixture_id', $id)->value('revision') ?? 0;

            return app(AcademicContests::class)->result($actor, $id, $this->op(['state' => $state, 'revision' => $revision, 'host_score' => $host, 'guest_score' => $guest,
                'winner' => $winner, 'entrants' => $entrants, 'reasons' => 'Private adjudicators\' reasons']));
        });
    }

    private function see(User $actor, int $school, int $id): array
    {
        return $this->as($actor, $school, fn () => app(AcademicContests::class)->view($actor, $id));
    }

    private function review(User $actor, int $school, int $id, string $text = self::SPEECH, string $side = 'ours'): array
    {
        return $this->as($actor, $school, fn () => app(ReasoningReviews::class)->request($actor, $id, $this->op(['text' => $text, 'source' => 'speech', 'from_side' => $side])));
    }

    /** @return list<int> recipients of competition result notices in a school */
    private function noticed(int $school): array
    {
        $ids = Notification::acrossSchools()->where('school_id', $school)->where('type', FixtureNotices::TYPE)->pluck('id')->all();

        return OutboxEvent::query()->where('type', 'notification.requested')->get()->filter(fn ($e) => in_array((int) $e->payload['notification_id'], $ids, true))
            ->flatMap(fn ($e) => $e->payload['user_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    public function test_two_schools_hold_a_debate_and_the_adjudicators_result_confirms_once_both_agree(): void
    {
        $id = $this->debate();
        $a = $this->see($this->adminA, 1, $id);
        $this->assertSame(['debate', 'team', 'This house would abolish school uniforms', 'World Schools', 1, 'opposition'],
            [$a['academic']['kind'], $a['academic']['entry'], $a['academic']['motion'], $a['academic']['format'], $a['academic']['rounds'], $a['academic']['my_stance']]);
        $this->assertSame(['Tara Demo', 'Outside Judge Nomvula'], array_column($a['academic']['adjudicators'], 'name'));
        $b = $this->see($this->adminB, 2, $id);
        $this->assertSame('proposition', $b['academic']['my_stance'], 'the guest takes the other side');
        $this->assertSame('Jacaranda Debaters', $b['host']['team_label']);

        // Tara, A's adjudicator, keeps a draft: nothing is reported and Baobab sees nothing of it.
        $draft = $this->judged($this->tara, 1, $id, 'draft', 210, 195, 'host', [['learner_id' => $this->asha, 'score' => 72, 'place' => 1]]);
        $this->assertSame(['draft', 'none'], [$draft['academic']['result']['state'], $draft['result']['state']]);
        $this->assertNull($this->see($this->adminB, 2, $id)['academic']['result']);

        // Points do not decide a debate: the adjudicators name the winner. A draw is not a debate result.
        $this->refused(422, fn () => $this->judged($this->tara, 1, $id, 'final', 200, 200, 'draw'));
        $submitted = $this->judged($this->tara, 1, $id, 'final', 210, 195, 'host', [['learner_id' => $this->asha, 'score' => 72, 'place' => 1]]);
        $this->assertSame(['submitted', 'awaiting'], [$submitted['academic']['result']['state'], $submitted['result']['state']]);
        $this->assertSame([], $this->noticed(1));

        // Baobab's adjudicators disagree on the winner: disputed until they agree.
        $disputed = $this->judged($this->adminB, 2, $id, 'final', 210, 195, 'guest');
        $this->assertSame(['disputed', 'host'], [$disputed['result']['state'], $disputed['academic']['their_winner']]);
        $confirmed = $this->judged($this->adminB, 2, $id, 'final', 210, 195, 'host');
        $this->assertSame(['confirmed', 210, 195, 'host'], [$confirmed['result']['state'], $confirmed['result']['host_score'], $confirmed['result']['guest_score'], $confirmed['result']['winner']]);
        $this->assertSame([1, 'host'], [(int) SharedFixture::findOrFail($id)->result_revision, SharedFixture::findOrFail($id)->winner]);

        // Each school tells its own families, inside its own school, that a competition result is ready.
        $this->assertSame(collect([$this->farid->id, $this->schoolA->ids['mona_user']])->sort()->values()->all(), $this->noticed(1));
        $this->assertSame('Competition result', Notification::acrossSchools()->where('school_id', 1)->where('type', FixtureNotices::TYPE)->value('title'));
        $family = $this->as($this->farid, 1, fn () => app(AcademicContests::class)->forLearner($this->farid, $this->asha))['items'];
        $this->assertSame(['debate', 'Baobab High', 'opposition', ['state' => 'confirmed', 'our_score' => 210, 'their_score' => 195, 'outcome' => 'won']],
            [$family[0]['kind'], $family[0]['opponent'], $family[0]['stance'], $family[0]['result']]);
        $this->assertStringNotContainsString('place', json_encode($family), 'no place or ranking of a child for families');
        $familyB = $this->as($this->faridB, 2, fn () => app(AcademicContests::class)->forLearner($this->faridB, $this->ashaB))['items'];
        $this->assertSame('lost', $familyB[0]['result']['outcome']);

        // A league of debates counts the adjudicators' winner, not the points.
        $this->assertSame(409, (function () use ($id) {
            try {
                $this->as($this->adminA, 1, fn () => app(Fixtures::class)->report($this->adminA, $id, $this->op(['host_score' => 1, 'guest_score' => 0])));
            } catch (HttpException $e) {
                return $e->getStatusCode();
            }

            return 200;
        })(), 'a fixture score cannot overwrite an adjudicated result');
    }

    public function test_quiz_points_decide_the_winner_and_an_outside_school_is_confirmed_by_the_host(): void
    {
        $id = $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->create($this->adminA, $this->op([
            'kind' => 'quiz', 'motion' => 'General knowledge', 'activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda Quizzers', 'discipline' => 'Quiz',
            'on_date' => self::TODAY, 'venue' => 'Library', 'host_is_home' => true, 'external_opponent' => 'Riverside Academy',
        ])))['id'];
        $this->refused(422, fn () => $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->create($this->adminA, $this->op([
            'kind' => 'quiz', 'motion' => 'General knowledge', 'host_stance' => 'proposition', 'activity_group_id' => $this->groupA, 'team_label' => 'X', 'discipline' => 'Quiz',
            'on_date' => self::TODAY, 'venue' => 'Library', 'host_is_home' => true, 'external_opponent' => 'Riverside Academy']))));
        $this->refused(422, fn () => $this->judged($this->adminA, 1, $id, 'final', 40, 55, 'host'));
        $confirmed = $this->judged($this->adminA, 1, $id, 'final', 40, 55, 'guest');
        $this->assertSame(['confirmed', 'guest', 'Riverside Academy'], [$confirmed['result']['state'], $confirmed['result']['winner'], $confirmed['guest']['name']]);
        $this->assertSame([$id], array_column($this->as($this->adminA, 1, fn () => app(AcademicContests::class)->listing($this->adminA))['items'], 'id'));
    }

    public function test_the_wrong_role_is_refused_and_a_coach_cannot_adjudicate(): void
    {
        $id = $this->debate();
        $this->refused(403, fn () => $this->as($this->theo, 1, fn () => app(AcademicContests::class)->create($this->theo, $this->op())));
        $this->refused(404, fn () => $this->see($this->theo, 1, $id));
        $this->assertSame([], $this->as($this->theo, 1, fn () => app(AcademicContests::class)->listing($this->theo))['items']);
        $this->refused(403, fn () => $this->see($this->farid, 1, $id));
        $this->refused(403, fn () => $this->as($this->farid, 1, fn () => app(AcademicContests::class)->listing($this->farid)));

        // The adjudicator sees the competition but does not manage it.
        $this->assertSame($id, $this->as($this->tara, 1, fn () => app(AcademicContests::class)->listing($this->tara))['items'][0]['id']);
        $this->refused(403, fn () => $this->as($this->tara, 1, fn () => app(AcademicContests::class)->adjudicators($this->tara, $id, $this->op(['adjudicators' => []]))));
        $this->refused(403, fn () => $this->as($this->tara, 1, fn () => app(AcademicContests::class)->details($this->tara, $id, $this->op(['motion' => 'Changed', 'revision' => 1]))));

        // A coach of the team keeps the team sheet but cannot record a result or ask for AI feedback.
        $coach = $this->theo;
        $this->inA(fn () => ActivityStaff::create(['activity_group_id' => $this->groupA, 'user_id' => $coach->id, 'role' => 'facilitator', 'cascade' => false,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic coach', 'revision' => 1]));
        $this->assertSame([], $this->see($coach, 1, $id)['academic']['adjudicators']);
        $this->refused(403, fn () => $this->judged($coach, 1, $id, 'final', 1, 0, 'host'));
        $this->ai();
        $this->refused(403, fn () => $this->review($coach, 1, $id));

        // The guest's manager cannot change the host's motion; adjudicators must be the school's staff.
        $this->refused(403, fn () => $this->as($this->adminB, 2, fn () => app(AcademicContests::class)->details($this->adminB, $id, $this->op(['motion' => 'Baobab motion', 'revision' => 2]))));
        $this->refused(422, fn () => $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->adjudicators($this->adminA, $id, $this->op(['adjudicators' => [['user_id' => $this->farid->id]]]))));
        $this->refused(422, fn () => $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->adjudicators($this->adminA, $id, $this->op(['adjudicators' => [['user_id' => $this->adminB->id]]]))));
        $this->assertSame([], ReasoningFakeProvider::$inputs);
    }

    public function test_another_school_never_reads_your_roster_adjudication_feedback_or_awards(): void
    {
        $this->ai();
        $id = $this->debate();
        $this->judged($this->tara, 1, $id, 'draft', 210, 195, 'host', [['learner_id' => $this->asha, 'score' => 72, 'place' => 1]]);
        $reviewA = $this->review($this->tara, 1, $id)['id'];
        $ashaName = $this->inA(fn () => Students::with('user')->findOrFail($this->asha)->user);

        // Baobab takes part, but sees none of A's learners, adjudicators, draft result or feedback.
        $b = $this->see($this->adminB, 2, $id);
        $json = json_encode($b);
        foreach (['Tara', 'Nomvula', 'Private A note', 'Private adjudicators'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
        $this->assertStringNotContainsString($ashaName->first_name.' '.$ashaName->last_name, $json);
        $this->assertSame([[], [], null], [$b['academic']['adjudicators'], $b['academic']['reviews'], $b['academic']['result']]);
        $this->refused(404, fn () => $this->as($this->adminB, 2, fn () => app(ReasoningReviews::class)->show($this->adminB, $id, $reviewA)));
        $this->refused(404, fn () => $this->as($this->adminB, 2, fn () => app(ReasoningReviews::class)->share($this->adminB, $id, $reviewA, $this->op(['learner_id' => $this->ashaB, 'text' => 'Baobab writes into A feedback here.']))));

        // Baobab cannot award A's learners, nor score them, nor share feedback with them.
        $this->refused(422, fn () => $this->as($this->adminB, 2, fn () => app(AcademicAwards::class)->link($this->adminB, $id, $this->op(['award_definition_id' => $this->award, 'basis' => 'participation',
            'learner_ids' => [$this->asha], 'presented_on' => self::TODAY]))));
        $this->refused(422, fn () => $this->judged($this->adminB, 2, $id, 'draft', 1, 0, 'host', [['learner_id' => $this->asha, 'score' => 1, 'place' => 1]]));
        $this->assertSame(0, AwardPresentation::acrossSchools()->where('learner_id', $this->asha)->count());

        // A third school sees nothing at all.
        $c = fn (callable $fn) => $this->as($this->adminC, 3, $fn);
        $this->refused(404, fn () => $c(fn () => app(AcademicContests::class)->view($this->adminC, $id)));
        $this->refused(404, fn () => $c(fn () => app(AcademicContests::class)->result($this->adminC, $id, $this->op(['state' => 'final', 'revision' => 0, 'host_score' => 1, 'guest_score' => 0, 'winner' => 'host', 'entrants' => []]))));
        $this->refused(404, fn () => $c(fn () => app(AcademicContests::class)->adjudicators($this->adminC, $id, $this->op(['adjudicators' => []]))));
        $this->refused(404, fn () => $c(fn () => app(ReasoningReviews::class)->show($this->adminC, $id, $reviewA)));
        $this->refused(404, fn () => $c(fn () => app(ReasoningReviews::class)->request($this->adminC, $id, $this->op(['text' => self::SPEECH, 'source' => 'speech', 'from_side' => 'ours']))));
        $this->refused(404, fn () => $c(fn () => app(AcademicAwards::class)->link($this->adminC, $id, $this->op(['award_definition_id' => $this->award, 'basis' => 'participation', 'learner_ids' => [$this->asha], 'presented_on' => self::TODAY]))));
        $this->assertSame([], $c(fn () => app(AcademicContests::class)->listing($this->adminC))['items']);
        $this->assertSame(0, $c(fn () => AcademicReasoningReview::query()->count() + AcademicResult::query()->count()));

        // A school that was invited and has not accepted sees only the host's name and the facts.
        $pending = $this->debate(false);
        $invited = $this->see($this->adminB, 2, $pending);
        $this->assertSame([null, 'invited'], [$invited['host']['team_label'], $invited['my_state']]);
        $this->refused(409, fn () => $this->as($this->adminB, 2, fn () => app(AcademicContests::class)->adjudicators($this->adminB, $pending, $this->op(['adjudicators' => []]))));
    }

    public function test_ai_feedback_is_redacted_advice_for_the_adjudicator_and_never_the_result(): void
    {
        $this->ai();
        $id = $this->debate();
        $schoolA = School::query()->whereKey(1)->value('name');
        $asha = $this->inA(fn () => Students::with('user')->findOrFail($this->asha)->user);
        $review = $this->review($this->tara, 1, $id);
        $this->assertSame('completed', $review['state']);
        $this->assertSame(ReasoningFeedbackModeration::KEYS, array_keys($review['feedback']));
        $this->assertSame('opposition', $review['stance']);

        // What the model saw: the motion, stance and text, with every school, team, learner and adult name removed.
        $this->assertCount(1, ReasoningFakeProvider::$inputs);
        $sent = ReasoningFakeProvider::$inputs[0];
        foreach ([$asha->first_name, $asha->last_name, 'Baobab', 'Jacaranda', $schoolA, 'Tara', 'Debaters', 'Speakers'] as $name) {
            $this->assertStringNotContainsString((string) $name, $sent, "$name reached the provider");
        }
        $this->assertStringContainsString('[name] argued that school uniforms', $sent);
        $this->assertSame(['reasoning_feedback', 'debate', 'opposition', 'speech'], [json_decode($sent, true)['task'], json_decode($sent, true)['kind'], json_decode($sent, true)['stance'], json_decode($sent, true)['source']]);

        // The receipt holds a reference, no learner and no name; the stored request is cleared.
        $usage = $this->inA(fn () => AiUsage::query()->where('feature', 'reasoning_feedback')->sole());
        $this->assertSame([null, ['academic_reasoning_review:'.$review['id']], 'completed'], [$usage->learner_id, $usage->inference_sources, $usage->state]);
        $row = $this->inA(fn () => AcademicReasoningReview::findOrFail($review['id']));
        $this->assertNull($row->input);
        $this->assertStringNotContainsString((string) $asha->first_name, json_encode($row->feedback));

        // The AI never decides the result: nothing is recorded, reported or confirmed.
        $this->assertSame(['none', null, null, null], [SharedFixture::findOrFail($id)->result_state, SharedFixture::findOrFail($id)->winner, SharedFixture::findOrFail($id)->host_score,
            $this->inA(fn () => AcademicResult::query()->where('shared_fixture_id', $id)->first())]);
        $this->assertNull($this->see($this->tara, 1, $id)['academic']['result']);

        // An answer that gives a verdict or a score is quarantined and shown to nobody.
        foreach (['verdict', 'score'] as $mode) {
            ReasoningFakeProvider::$mode = $mode;
            $held = $this->review($this->tara, 1, $id, self::SPEECH.' '.$mode);
            $this->assertSame(['quarantined', null], [$held['state'], $held['feedback']]);
        }
        $this->assertSame('none', SharedFixture::findOrFail($id)->result_state);
        $this->assertSame(0, $this->inA(fn () => AcademicResult::query()->count()));

        // Not shared with anyone until the adjudicator writes their own feedback for their own entrant.
        $this->assertSame([], $this->as($this->farid, 1, fn () => app(AcademicContests::class)->forLearner($this->farid, $this->asha))['items'][0]['feedback']);
        $this->refused(422, fn () => $this->as($this->tara, 1, fn () => app(ReasoningReviews::class)->share($this->tara, $id, $review['id'], $this->op(['learner_id' => $this->ben, 'text' => 'Well argued, keep building on the cost point.']))));
        $this->refused(403, fn () => $this->as($this->adminA, 1, fn () => app(ReasoningReviews::class)->share($this->adminA, $id, $review['id'], $this->op(['learner_id' => $this->asha, 'text' => 'Well argued, keep building on the cost point.']))));
        $shared = $this->as($this->tara, 1, fn () => app(ReasoningReviews::class)->share($this->tara, $id, $review['id'], $this->op(['learner_id' => $this->asha, 'text' => 'Well argued, keep building on the cost point.'])));
        $this->assertSame($this->asha, $shared['shared']['learner_id']);
        $family = $this->as($this->farid, 1, fn () => app(AcademicContests::class)->forLearner($this->farid, $this->asha))['items'][0]['feedback'];
        $this->assertSame([['text' => 'Well argued, keep building on the cost point.', 'shared_on' => self::TODAY]], $family);
        $this->assertStringNotContainsString('structure', json_encode($family), 'the AI text itself never reaches the family');

        // Feedback on the other school's speaker cannot be shared with anyone.
        $theirs = $this->review($this->tara, 1, $id, 'The proposition speaker claimed that uniforms limit expression, citing one interview, and did not answer the cost argument at all in rebuttal.', 'theirs');
        $this->assertSame('proposition', $theirs['stance']);
        $this->refused(409, fn () => $this->as($this->tara, 1, fn () => app(ReasoningReviews::class)->share($this->tara, $id, $theirs['id'], $this->op(['learner_id' => $this->asha, 'text' => 'Feedback on the other speaker.']))));
    }

    public function test_ai_feedback_is_off_by_default_switched_per_school_budgeted_and_guards_safeguarding_phrases(): void
    {
        $id = $this->debate();
        // Off by default: refused before anything is stored or sent.
        $this->assertFalse(config('ai.inference.enabled'));
        $this->assertFalse(config('ai.inference.features.reasoning_feedback.enabled'));
        $this->assertFalse(config('modules.reasoning_feedback.default'));
        config(['modules.reasoning_feedback' => ['always_on' => true]]);
        $this->refused(409, fn () => $this->review($this->tara, 1, $id));
        $this->assertFalse($this->see($this->tara, 1, $id)['academic']['reasoning_feedback']);

        // The platform switched it on, but this school has not.
        $this->ai(module: false);
        $this->refused(403, fn () => $this->review($this->tara, 1, $id));
        $this->assertSame(0, AcademicReasoningReview::query()->count());

        // The school's daily budget is spent: no provider call, the request is answered as unavailable.
        $this->ai(['school_daily_tokens' => 100]);
        $capped = $this->review($this->tara, 1, $id);
        $this->assertSame(['unavailable', 'school_daily_cap', null], [$capped['state'], $capped['failure_code'], $capped['feedback']]);
        $this->assertSame([], ReasoningFakeProvider::$inputs);

        // A safeguarding phrase in the text is held for the school's reviewers before any network call.
        $this->ai();
        $phrase = json_decode((string) file_get_contents(resource_path('ai/safeguarding/en.json')), true)['categories']['self_harm'][0];
        $held = $this->review($this->tara, 1, $id, 'In the second half the speaker said something worrying: I want to '.$phrase.' after this round. The rest argued the cost point well.');
        $this->assertSame(['quarantined', null], [$held['state'], $held['feedback']]);
        $this->assertSame([], ReasoningFakeProvider::$inputs, 'nothing reached the provider');
        $usage = $this->inA(fn () => AiUsage::query()->where('feature', 'reasoning_feedback')->where('state', 'quarantined')->sole());
        $this->assertSame(['safeguarding_disclosure'], $this->inA(fn () => AiFlag::query()->where('ai_usage_id', $usage->id)->pluck('code')->all()));
        $this->assertNull($this->inA(fn () => AcademicReasoningReview::findOrFail($held['id'])->input));
        // School B's reviewers see none of it.
        $this->assertSame([], $this->inB(fn () => AiFlag::query()->pluck('id')->all()));

        // Quiz competitions do not offer AI feedback.
        $quiz = $this->as($this->adminA, 1, fn () => app(AcademicContests::class)->create($this->adminA, $this->op([
            'kind' => 'quiz', 'motion' => 'General knowledge', 'activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda Quizzers', 'discipline' => 'Quiz',
            'on_date' => self::TODAY, 'venue' => 'Library', 'host_is_home' => true, 'external_opponent' => 'Riverside Academy'])))['id'];
        $this->refused(422, fn () => $this->review($this->adminA, 1, $quiz));
    }

    public function test_a_replayed_request_is_answered_once_and_a_changed_one_conflicts(): void
    {
        $this->ai();
        $id = $this->debate();
        $operation = (string) Str::uuid();
        $ask = fn (string $text) => $this->as($this->tara, 1, fn () => app(ReasoningReviews::class)->request($this->tara, $id, ['operation_id' => $operation, 'text' => $text, 'source' => 'speech', 'from_side' => 'ours']));
        $first = $ask(self::SPEECH);
        $this->assertSame($first, $ask(self::SPEECH));
        $this->assertCount(1, ReasoningFakeProvider::$inputs);
        $this->refused(409, fn () => $ask(self::SPEECH.' And one more point.'));
    }

    public function test_a_guardian_sees_only_their_own_child(): void
    {
        $this->ai();
        $id = $this->debate();
        $this->judged($this->tara, 1, $id, 'final', 210, 195, 'host');
        $this->judged($this->adminB, 2, $id, 'final', 210, 195, 'host');
        $this->assertCount(1, $this->as($this->farid, 1, fn () => app(AcademicContests::class)->forLearner($this->farid, $this->asha))['items']);
        // Ben is not entered: his guardian sees nothing, and cannot read Asha's.
        $this->assertSame([], $this->as($this->gina, 1, fn () => app(AcademicContests::class)->forLearner($this->gina, $this->ben))['items']);
        $this->refused(404, fn () => $this->as($this->gina, 1, fn () => app(AcademicContests::class)->forLearner($this->gina, $this->asha)));
        // Baobab's guardian cannot read School A's child, even with the child's id.
        $this->refused(404, fn () => $this->as($this->faridB, 2, fn () => app(AcademicContests::class)->forLearner($this->faridB, $this->asha)));
    }

    public function test_a_result_links_to_the_schools_own_awards_for_taking_part_and_placing(): void
    {
        $id = $this->debate();
        $link = fn (string $basis, ?string $operation = null) => $this->as($this->adminA, 1, fn () => app(AcademicAwards::class)->link($this->adminA, $id, [
            'operation_id' => $operation ?? (string) Str::uuid(), 'award_definition_id' => $this->award, 'basis' => $basis, 'learner_ids' => [$this->asha], 'presented_on' => self::TODAY]));

        // Placing needs the confirmed result; taking part does not.
        $this->refused(409, fn () => $link('placing'));
        $operation = (string) Str::uuid();
        $view = $link('participation', $operation);
        $this->assertSame([['learner_id' => $this->asha, 'basis' => 'participation', 'title' => 'Debating colours']],
            array_map(fn ($a) => array_diff_key($a, ['award_presentation_id' => true]), $view['academic']['awards']));
        $presentation = $this->inA(fn () => AwardPresentation::query()->where('learner_id', $this->asha)->sole());
        $this->assertSame(['catalogue', 'Debate: This house would abolish school uniforms · Took part'], [$presentation->source_kind, $presentation->event]);
        // A replay presents nothing new; the same learner and basis is never awarded twice.
        $link('participation', $operation);
        $link('participation');
        $this->assertSame(1, $this->inA(fn () => AwardPresentation::query()->where('learner_id', $this->asha)->count()));

        $this->judged($this->tara, 1, $id, 'final', 180, 200, 'guest', [['learner_id' => $this->asha, 'score' => 75, 'place' => 1]]);
        $this->judged($this->adminB, 2, $id, 'final', 180, 200, 'guest');
        $link('placing');
        $this->assertSame(['participation', 'placing'], $this->inA(fn () => AcademicAwardLink::query()->orderBy('id')->pluck('basis')->all()));
        $family = $this->as($this->farid, 1, fn () => app(AcademicContests::class)->forLearner($this->farid, $this->asha))['items'][0];
        $this->assertSame(['Debating colours', 'Debating colours'], $family['awards']);
        $this->assertSame('lost', $family['result']['outcome']);

        // A teacher without the awards role, and a manager of another school, cannot award.
        $this->refused(403, fn () => $this->as($this->tara, 1, fn () => app(AcademicAwards::class)->link($this->tara, $id, $this->op(['award_definition_id' => $this->award, 'basis' => 'participation', 'learner_ids' => [$this->asha], 'presented_on' => self::TODAY]))));
        // Baobab's own entrant, with School A's award: School A's catalogue does not exist for Baobab.
        $this->refused(404, fn () => $this->as($this->adminB, 2, fn () => app(AcademicAwards::class)->link($this->adminB, $id, $this->op(['award_definition_id' => $this->award, 'basis' => 'participation', 'learner_ids' => [$this->ashaB], 'presented_on' => self::TODAY]))));
        $this->assertSame(0, $this->inB(fn () => AwardPresentation::query()->count()));
    }

    public function test_the_api_follows_the_contract(): void
    {
        $this->ai();
        $tokenA = $this->adminA->createToken('test')->plainTextToken;
        $tokenB = $this->adminB->createToken('test')->plainTextToken;
        $tokenT = $this->tara->createToken('test')->plainTextToken;
        $api = fn (string $token, int $school) => $this->forgetGuards()->flushHeaders()->withToken($token)->withHeaders(['X-School-Id' => (string) $school]);

        $id = $api($tokenA, 1)->postJson('/api/v2/activities/academic-competitions', $this->op(['kind' => 'debate', 'motion' => 'This house would abolish homework', 'format' => null, 'rounds' => null,
            'host_stance' => 'proposition', 'activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda Debaters', 'discipline' => 'Debating', 'on_date' => self::TODAY,
            'starts_at' => null, 'venue' => 'Jacaranda hall', 'host_is_home' => true, 'guest_school_id' => 2]))->assertOk()->assertJsonPath('data.academic.kind', 'debate')->json('data.id');
        $api($tokenB, 2)->getJson('/api/v2/activities/academic-competitions')->assertOk()->assertJsonPath('data.items.0.my_state', 'invited');
        $api($tokenB, 2)->postJson("/api/v2/activities/fixtures/$id/decision", $this->op(['decision' => 'accept', 'activity_group_id' => $this->groupB, 'team_label' => 'Baobab Speakers']))->assertOk();
        $api($tokenA, 1)->putJson("/api/v2/activities/fixtures/$id/side", $this->op(['revision' => 1, 'team_label' => 'Jacaranda Debaters', 'sheet_shared' => false, 'notes' => null,
            'selections' => [['learner_id' => $this->asha, 'sheet_label' => null, 'position' => null]]]))->assertOk();
        $api($tokenA, 1)->putJson("/api/v2/activities/academic-competitions/$id/details", $this->op(['motion' => 'This house would abolish homework', 'format' => 'World Schools', 'rounds' => 2, 'host_stance' => 'proposition', 'revision' => 1]))
            ->assertOk()->assertJsonPath('data.academic.rounds', 2);
        $api($tokenA, 1)->putJson("/api/v2/activities/academic-competitions/$id/adjudicators", $this->op(['adjudicators' => [['user_id' => $this->tara->id]]]))->assertOk()->assertJsonPath('data.academic.adjudicators.0.name', 'Tara Demo');
        $review = $api($tokenT, 1)->postJson("/api/v2/activities/academic-competitions/$id/reasoning-reviews", $this->op(['text' => self::SPEECH, 'source' => 'written', 'from_side' => 'ours']))
            ->assertOk()->assertJsonPath('data.state', 'completed')->json('data.id');
        $api($tokenT, 1)->getJson("/api/v2/activities/academic-competitions/$id/reasoning-reviews/$review")->assertOk()->assertJsonPath('data.source', 'written');
        $api($tokenT, 1)->postJson("/api/v2/activities/academic-competitions/$id/reasoning-reviews/$review/share", $this->op(['learner_id' => $this->asha, 'text' => 'A clear structure; add one more example.']))
            ->assertOk()->assertJsonPath('data.shared.learner_id', $this->asha);
        $api($tokenT, 1)->putJson("/api/v2/activities/academic-competitions/$id/result", $this->op(['state' => 'final', 'revision' => 0, 'host_score' => 150, 'guest_score' => 140, 'winner' => 'host',
            'entrants' => [['learner_id' => $this->asha, 'score' => 70, 'place' => 2]], 'reasons' => null]))->assertOk()->assertJsonPath('data.result.state', 'awaiting');
        $api($tokenB, 2)->putJson("/api/v2/activities/academic-competitions/$id/result", $this->op(['state' => 'final', 'revision' => 0, 'host_score' => 150, 'guest_score' => 140, 'winner' => 'host', 'entrants' => [], 'reasons' => null]))
            ->assertOk()->assertJsonPath('data.result.winner', 'host');
        $api($tokenA, 1)->postJson("/api/v2/activities/academic-competitions/$id/awards", $this->op(['award_definition_id' => $this->award, 'basis' => 'placing', 'learner_ids' => [$this->asha], 'presented_on' => self::TODAY]))
            ->assertOk()->assertJsonPath('data.academic.awards.0.basis', 'placing');
        $api($tokenB, 2)->getJson("/api/v2/activities/academic-competitions/$id/reasoning-reviews/$review")->assertNotFound();
        $api($tokenB, 2)->getJson("/api/v2/activities/fixtures/$id")->assertOk()->assertJsonPath('data.result.winner', 'host');
        $faridToken = $this->farid->createToken('test')->plainTextToken;
        $api($faridToken, 1)->getJson("/api/v2/activities/learners/{$this->asha}/academic-competitions")->assertOk()->assertJsonPath('data.items.0.result.outcome', 'won')
            ->assertJsonPath('data.items.0.feedback.0.text', 'A clear structure; add one more example.');
        $api($faridToken, 1)->getJson("/api/v2/activities/learners/{$this->ben}/academic-competitions")->assertNotFound();
        $api($faridToken, 1)->getJson("/api/v2/activities/academic-competitions/$id")->assertForbidden();
        $api($tokenA, 2)->getJson("/api/v2/activities/academic-competitions/$id")->assertForbidden();
        $tokenC = $this->adminC->createToken('test')->plainTextToken;
        $api($tokenC, 3)->getJson("/api/v2/activities/academic-competitions/$id")->assertNotFound();
        $api($tokenC, 3)->putJson("/api/v2/activities/academic-competitions/$id/result", $this->op(['state' => 'final', 'revision' => 0, 'host_score' => 1, 'guest_score' => 0, 'winner' => 'host', 'entrants' => []]))->assertNotFound();
    }

    public function test_the_panel_pages_work_for_managers_only(): void
    {
        $this->actingAs($this->adminA);
        $this->get('/academic-competitions')->assertOk()->assertSee('Arrange an academic competition')->assertDontSee('Baobab');
        $this->post('/academic-competitions', $this->op(['kind' => 'debate', 'motion' => 'This house would ban phones at school', 'format' => '', 'rounds' => '', 'host_stance' => 'proposition',
            'activity_group_id' => $this->groupA, 'team_label' => 'Jacaranda Debaters', 'discipline' => 'Debating', 'on_date' => self::TODAY, 'starts_at' => '', 'venue' => 'Jacaranda hall',
            'host_is_home' => '1', 'guest_school_id' => '', 'external_opponent' => 'Riverside Academy']))->assertRedirect()->assertSessionHasNoErrors();
        $id = (int) SharedFixture::query()->latest('id')->value('id');
        $this->get("/academic-competitions/$id")->assertOk()->assertSee('This house would ban phones at school')->assertSee('Your adjudicators');
        $this->post("/academic-competitions/$id/adjudicators", $this->op(['staff' => [$this->tara->id], 'outside' => "Outside Judge Nomvula\n"]))->assertRedirect("/academic-competitions/$id")->assertSessionHasNoErrors();
        $this->post("/activity-fixtures/$id/side", $this->op(['revision' => 1, 'team_label' => 'Jacaranda Debaters', 'pick' => [$this->asha], 'label' => [$this->asha => ''], 'position' => [$this->asha => ''], 'notes' => '']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/academic-competitions/$id/result", $this->op(['state' => 'final', 'revision' => '0', 'host_score' => '120', 'guest_score' => '100', 'winner' => 'host',
            'entrant_score' => [$this->asha => '60'], 'entrant_place' => [$this->asha => '1'], 'reasons' => '']))->assertRedirect("/academic-competitions/$id")->assertSessionHasNoErrors();
        $this->get("/academic-competitions/$id")->assertOk()->assertSee('Tara Demo')->assertSee('Won by');
        $this->post("/academic-competitions/$id/awards", $this->op(['award_definition_id' => $this->award, 'basis' => 'placing', 'learner_ids' => [$this->asha], 'presented_on' => self::TODAY]))
            ->assertRedirect("/academic-competitions/$id")->assertSessionHasNoErrors();
        // Off by default: asking for AI feedback is refused with a message, not an error page.
        $this->post("/academic-competitions/$id/review", $this->op(['text' => self::SPEECH, 'source' => 'speech', 'from_side' => 'ours']))->assertRedirect("/academic-competitions/$id")->assertSessionHasErrors();

        $this->actingAs($this->theo);
        $this->get('/academic-competitions')->assertForbidden();
        $this->get("/academic-competitions/$id")->assertForbidden();
        $this->post("/academic-competitions/$id/result", $this->op(['state' => 'final']))->assertForbidden();
        $this->actingAs($this->adminC);
        $this->get("/academic-competitions/$id")->assertNotFound();
    }

    /** Each request is its own sign-in: the guard does not remember the last token. */
    private function forgetGuards(): self
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }
}

/** A synthetic provider for tests only: it records exactly what the model would have seen. */
class ReasoningFakeProvider implements InferenceProvider
{
    /** @var list<string> */
    public static array $inputs = [];

    public static string $mode = 'ok';

    public static function reset(): void
    {
        self::$inputs = [];
        self::$mode = 'ok';
    }

    public function preflight(ProviderRequest $request): void {}

    public function infer(ProviderRequest $request): ProviderResponse
    {
        self::$inputs[] = (string) $request->input;
        $feedback = ['structure' => 'Two clear claims, signposted at the start.', 'evidence' => 'One survey is cited; its size is given but not its source.',
            'rebuttal' => 'The expression argument is dismissed rather than answered.', 'fallacies' => [['name' => 'Bandwagon', 'explanation' => '"Everyone agrees with us" is not a reason.']],
            'summary' => 'A clear case on cost that would be stronger with a sourced example and a direct answer to expression.'];
        if (self::$mode === 'verdict') {
            $feedback['summary'] = 'The opposition should win this debate.';
        } elseif (self::$mode === 'score') {
            $feedback = ['score' => 8] + $feedback;
        }

        return new ProviderResponse(json_encode($feedback, JSON_THROW_ON_ERROR), 40);
    }
}

/** Stands in for the shared external moderation call only. */
class ReasoningFakeModerator extends OpenAiModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void {}

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        return [];
    }
}
