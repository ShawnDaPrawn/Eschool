<?php

declare(strict_types=1);

namespace Tests\Feature\PeerTutoring;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityMembership;
use App\Models\ClassSchool;
use App\Models\Growth\MasteryRecord;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\PeerTutoring\ServiceHour;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\PeerTutoring\PeerMatches;
use App\Services\PeerTutoring\PeerMeasure;
use App\Services\PeerTutoring\PeerSessions;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Action Plan 4.7 "Every child teaches something": fairer matching, activity
 * skills as topics and the school measure.
 * engineering/specs/community/peer-tutoring.md.
 */
class PeerMatchingFairnessTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T08:00:00Z'));
        config(['identity.approval_enforced' => true]);
        SchoolModule::updateOrCreate(['module' => 'peer_tutoring'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        foreach (['tara', 'theo', 'asha', 'ben', 'cara', 'farid', 'gina'] as $name) {
            $this->actor($name)->forceFill(['password_change_required' => false, 'email_verified_at' => now()])->save();
        }
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $this->grade('6')]);
        foreach (['ben' => 'section_a', 'cara' => 'section_c'] as $learner => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$learner], 'class_section_id' => $this->schoolA->ids[$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
            TeachingGroupMembership::create(['teaching_group_id' => $this->group, 'learner_id' => $this->schoolA->ids[$learner],
                'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up']);
            foreach ($this->nodes as $topic) {
                MasteryRecord::create(['subject_id' => $this->subject, 'learner_id' => $this->schoolA->ids[$learner], 'topic_id' => $topic,
                    'audience' => 'staff', 'level' => 4, 'score_bp' => 9000, 'confidence_bp' => 6000, 'evidence_count' => 2, 'computed_at' => now()]);
            }
        }
        foreach (['asha' => 'farid', 'ben' => 'gina', 'cara' => 'farid'] as $learner => $guardian) {
            GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->actor($guardian)->id],
                ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
            app(ApprovalService::class)->decide($this->actor($guardian), Students::findOrFail($this->schoolA->ids[$learner]), 'approve', config('identity.consent_version'));
        }
    }

    private function grade(string $code): int
    {
        return (int) GradeLevel::where('code', $code)->value('id');
    }

    private function input(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'teaching_group_id' => $this->group,
            'tutor_learner_id' => $this->schoolA->ids['ben'], 'tutee_learner_id' => $this->schoolA->ids['asha'], 'topic_id' => $this->nodes[$this->keys[1]],
            'venue' => 'Supervised school library', 'starts_at' => '2026-02-10T14:00', 'ends_at' => '2026-02-10T15:00'];
    }

    private function skillInput(int $activity, array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'teaching_group_id' => $this->group,
            'tutor_learner_id' => $this->schoolA->ids['cara'], 'tutee_learner_id' => $this->schoolA->ids['asha'], 'activity_group_id' => $activity,
            'venue' => 'Supervised chess room', 'starts_at' => '2026-02-11T14:00', 'ends_at' => '2026-02-11T15:00'];
    }

    /** Chess club with Cara as captain and Ben as a member. */
    private function chess(): int
    {
        $admin = $this->superAdmin();
        $admin->givePermissionTo('activity-groups-manage');
        app(ModuleGate::class)->change('groups', true, $admin);
        $group = app(ActivityGroups::class)->save($admin, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year,
            'parent_id' => null, 'name' => 'Chess club', 'discipline' => 'Chess', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10',
            'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null, 'minimum_age' => null, 'maximum_age' => null, 'active' => true]);
        foreach (['cara' => 'captain', 'ben' => 'member'] as $learner => $role) {
            app(ActivityGroups::class)->allocation($admin, (int) $group['id'], null, false, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
                'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10', 'reason' => 'Synthetic chess allocation', 'learner_id' => $this->schoolA->ids[$learner], 'role' => $role]);
        }
        app(ModuleGate::class)->forget();

        return (int) $group['id'];
    }

    private function ranked(?User $actor = null): array
    {
        return app(PeerMatches::class)->ranked($actor ?? $this->actor(), $this->group, $this->schoolA->ids['asha']);
    }

    /** @return list<array{0: int, 1: string, 2: int}> tutor, kind, tutored this term */
    private function order(array $matches): array
    {
        return array_values(array_unique(array_map(fn (array $m) => [$m['tutor_learner_id'], $m['kind'], $m['tutored_this_term']], $matches), SORT_REGULAR));
    }

    private function refused(callable $call, array $codes = [403, 404, 409, 422]): void
    {
        try {
            $call();
            $this->fail('A refused peer action succeeded.');
        } catch (ModelNotFoundException|HttpExceptionInterface $error) {
            $this->assertContains($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 404, $codes);
        }
    }

    private function api(User $actor, string $purpose, int $school = 1): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken($purpose)->plainTextToken)
            ->withHeaders(['X-School-Id' => (string) $school, 'X-Account-Id' => (string) $actor->id]);
    }

    private function completeAt(PeerTutoringSession $row, string $at, int $minutes = 40): PeerTutoringSession
    {
        $this->travelTo(CarbonImmutable::parse($at));

        return app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', ['operation_id' => (string) Str::uuid(), 'revision' => $row->revision, 'minutes' => $minutes]);
    }

    public function test_matches_favour_learners_who_have_not_tutored_this_term(): void
    {
        $ben = $this->schoolA->ids['ben'];
        $cara = $this->schoolA->ids['cara'];
        $this->assertLessThan($cara, $ben);
        $this->assertSame([[$ben, 'topic', 0], [$cara, 'topic', 0]], $this->order($this->ranked()));
        $this->assertSame('Term 1', $this->ranked()[0]['term']);

        $row = app(PeerSessions::class)->create($this->actor(), $this->input());
        $this->assertSame([[$cara, 'topic', 0], [$ben, 'topic', 1]], $this->order($this->ranked()));
        $api = app(PeerMatches::class)->forLearner($this->actor(), $this->group, $this->schoolA->ids['asha']);
        $this->assertSame($cara, $api[0]['tutor_learner_id']);
        $this->assertSame(['tutor_learner_id', 'tutor_name', 'tutee_learner_id', 'topic_id', 'topic_name', 'reason'], array_keys($api[0]));

        $this->actingAs($this->actor())->get('/peer-tutoring?group='.$this->group.'&learner='.$this->schoolA->ids['asha'])->assertOk()
            ->assertSeeInOrder(['Cara', 'Has not tutored yet this term', 'Ben', 'Has tutored once this term'])
            ->assertSee('Learners who have tutored least this term are offered first.');

        app(PeerSessions::class)->change($this->actor(), $row->id, 'cancel', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Not needed']);
        $this->assertSame([[$ben, 'topic', 0], [$cara, 'topic', 0]], $this->order($this->ranked()), 'a cancelled session does not count');
    }

    public function test_sessions_from_an_earlier_term_do_not_count(): void
    {
        Term::whereKey($this->term)->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
        Term::create(['name' => 'Term 2', 'session_year_id' => $this->year, 'start_date' => '2026-02-01', 'end_date' => '2026-03-31', 'grade_from' => 0, 'grade_to' => 12]);
        PeerTutoringSession::create(['session_year_id' => $this->year, 'teaching_group_id' => $this->group, 'topic_id' => $this->nodes[$this->keys[1]],
            'tutor_learner_id' => $this->schoolA->ids['ben'], 'tutee_learner_id' => $this->schoolA->ids['asha'], 'supervisor_user_id' => $this->actor()->id,
            'starts_at' => '2026-01-20 12:00:00', 'ends_at' => '2026-01-20 13:00:00', 'venue' => 'Last term', 'status' => 'completed', 'revision' => 2, 'verified_minutes' => 30]);
        $matches = $this->ranked();
        $this->assertSame('Term 2', $matches[0]['term']);
        $this->assertSame([[$this->schoolA->ids['ben'], 'topic', 0], [$this->schoolA->ids['cara'], 'topic', 0]], $this->order($matches));
    }

    public function test_eligibility_rules_still_apply_to_learners_who_have_not_tutored(): void
    {
        app(PeerSessions::class)->create($this->actor(), $this->input());
        // Cara has not tutored, but her guardian withdraws consent: she is not offered.
        app(ApprovalService::class)->decide($this->actor('farid'), Students::findOrFail($this->schoolA->ids['cara']), 'withdraw', config('identity.consent_version'));
        $this->assertSame([[$this->schoolA->ids['ben'], 'topic', 1]], $this->order($this->ranked()));
        // Weak evidence is not offered either, however fair it would be.
        MasteryRecord::where('learner_id', $this->schoolA->ids['ben'])->update(['level' => 2]);
        $this->assertSame([], $this->ranked());
    }

    public function test_a_captain_can_be_matched_to_teach_an_activity_skill_under_the_same_supervision(): void
    {
        $chess = $this->chess();
        $skills = array_values(array_filter($this->ranked(), fn (array $m) => $m['kind'] === 'activity_skill'));
        $this->assertCount(1, $skills, 'the captain is offered; a plain member is not');
        $this->assertSame([$this->schoolA->ids['cara'], $chess, 'Chess club', null, 'activity_skill'],
            [$skills[0]['tutor_learner_id'], $skills[0]['activity_group_id'], $skills[0]['topic_name'], $skills[0]['topic_id'], $skills[0]['reason']]);
        $this->assertSame([], array_filter(app(PeerMatches::class)->forLearner($this->actor(), $this->group, $this->schoolA->ids['asha']), fn (array $m) => $m['topic_id'] === null),
            'the v2 API carries mastery topics only');

        $row = app(PeerSessions::class)->createSkill($this->actor(), $this->skillInput($chess));
        $this->assertSame([null, $chess, 'Chess club', $this->actor()->id], [$row->topic_id, (int) $row->activity_group_id, $row->skill_name, (int) $row->supervisor_user_id]);
        $this->assertSame([$this->schoolA->ids['cara'], 'activity_skill', 1], $this->order(array_filter($this->ranked(), fn (array $m) => $m['kind'] === 'activity_skill'))[0]);
        $this->actingAs($this->actor())->get('/peer-tutoring')->assertOk()->assertSee('Activity skill: Chess club');

        $done = $this->completeAt($row, '2026-02-11T14:00:00Z');
        $this->assertSame('completed', $done->status);
        $this->assertSame(40, (int) ServiceHour::where('peer_tutoring_session_id', $row->id)->sum('minutes'));

        $teacher = AppTokens::TEACHER;
        $listed = $this->api($this->actor(), $teacher)->getJson('/api/v2/peer-tutoring/sessions?persona=staff')->assertOk()->json('data.items');
        $this->assertNotContains($row->id, array_column($listed, 'id'));
        $this->api($this->actor(), $teacher)->getJson('/api/v2/peer-tutoring/sessions/'.$row->id.'?persona=staff')->assertNotFound();
        $this->api($this->actor(), $teacher)->postJson('/api/v2/peer-tutoring/sessions/'.$row->id.'/reverse',
            ['persona' => 'staff', 'operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Not on the API'])->assertNotFound();
        $this->assertSame('completed', $row->fresh()->status);
    }

    public function test_activity_skill_needs_the_groups_module_and_a_captaincy(): void
    {
        $chess = $this->chess();
        ActivityMembership::where('learner_id', $this->schoolA->ids['cara'])->update(['active' => false]);
        $this->assertSame([], array_filter($this->ranked(), fn (array $m) => $m['kind'] === 'activity_skill'));
        $this->refused(fn () => app(PeerSessions::class)->createSkill($this->actor(), $this->skillInput($chess)), [409]);
        ActivityMembership::where('learner_id', $this->schoolA->ids['cara'])->update(['active' => true]);
        app(ModuleGate::class)->change('groups', false, $this->superAdmin());
        app(ModuleGate::class)->forget();
        $this->assertSame([], array_filter($this->ranked(), fn (array $m) => $m['kind'] === 'activity_skill'));
        $this->refused(fn () => app(PeerSessions::class)->createSkill($this->actor(), $this->skillInput($chess)), [409]);
        // Ben is a plain member, not the captain.
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        app(ModuleGate::class)->forget();
        $this->refused(fn () => app(PeerSessions::class)->createSkill($this->actor(), $this->skillInput($chess, ['tutor_learner_id' => $this->schoolA->ids['ben']])), [409]);
        $this->assertSame(0, PeerTutoringSession::count());
    }

    public function test_panel_schedules_an_activity_skill_and_the_wrong_role_is_refused(): void
    {
        $chess = $this->chess();
        $staff = $this->actor();
        $this->actingAs($staff)->get('/peer-tutoring?group='.$this->group.'&learner='.$this->schoolA->ids['asha'])->assertOk()
            ->assertSee('Activity skill: Chess club')->assertSee('name="activity_group_id" value="'.$chess.'"', false)
            ->assertSee(route('peer-tutoring-panel.skill'), false);
        $body = $this->skillInput($chess) + ['expected_school_id' => 1, 'expected_account_id' => $staff->id];
        $this->post('/peer-tutoring/skill-sessions', $body)->assertRedirect('/peer-tutoring');
        $this->post('/peer-tutoring/skill-sessions', $body)->assertRedirect('/peer-tutoring');
        $this->assertSame(1, PeerTutoringSession::whereNotNull('activity_group_id')->count());
        $this->post('/peer-tutoring/skill-sessions', $body + ['topic_id' => $this->nodes[$this->keys[1]]])->assertUnprocessable();

        $before = PeerTutoringSession::count();
        foreach (['theo', 'farid'] as $name) {
            $other = $this->actor($name);
            $this->actingAs($other)->post('/peer-tutoring/skill-sessions', $this->skillInput($chess, ['starts_at' => '2026-02-12T14:00', 'ends_at' => '2026-02-12T15:00'])
                + ['expected_school_id' => 1, 'expected_account_id' => $other->id])->assertForbidden();
            $this->refused(fn () => app(PeerSessions::class)->createSkill($other, $this->skillInput($chess, ['starts_at' => '2026-02-12T14:00', 'ends_at' => '2026-02-12T15:00'])), [403, 404]);
        }
        $this->assertSame($before, PeerTutoringSession::count());
    }

    public function test_another_schools_activity_group_or_learner_is_refused_and_left_unchanged(): void
    {
        $this->chess();
        $foreignGroup = DB::table('activity_groups')->insertGetId(['school_id' => 2, 'session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab chess',
            'discipline' => 'Chess', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10', 'gender' => 'any', 'active' => true,
            'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $foreignBefore = DB::table('activity_groups')->where('school_id', 2)->get()->toArray();
        $this->refused(fn () => app(PeerSessions::class)->createSkill($this->actor(), $this->skillInput($foreignGroup)), [404, 409]);
        $this->refused(fn () => app(PeerSessions::class)->createSkill($this->actor(), $this->skillInput($foreignGroup, ['tutor_learner_id' => $this->schoolB->ids['cara']])), [404, 409]);
        $this->refused(fn () => app(PeerSessions::class)->create($this->actor(), $this->input(['tutor_learner_id' => $this->schoolB->ids['ben']])), [404, 409]);
        $this->assertSame(0, PeerTutoringSession::count());
        $this->assertSame(0, DB::table('peer_tutoring_sessions')->where('school_id', 2)->count());
        $this->assertEquals($foreignBefore, DB::table('activity_groups')->where('school_id', 2)->get()->toArray());
    }

    public function test_school_measure_counts_learners_who_tutored_this_term_per_grade_without_names(): void
    {
        $this->actor()->givePermissionTo('academic-structure-manage');
        $done = app(PeerSessions::class)->create($this->actor(), $this->input());
        $this->completeAt($done, '2026-02-10T14:00:00Z');
        // Cara is scheduled to tutor Asha: a scheduled session does not count yet.
        $this->travelTo(CarbonImmutable::parse('2026-02-10T08:00:00Z'));
        app(PeerSessions::class)->create($this->actor(), $this->input(['tutor_learner_id' => $this->schoolA->ids['cara'], 'tutee_learner_id' => $this->schoolA->ids['asha'],
            'starts_at' => '2026-02-12T14:00', 'ends_at' => '2026-02-12T15:00']));
        $rows = app(PeerMeasure::class)->rows($this->actor());
        $this->assertSame([['Grade 6', 1, 0, 0], ['Grade 7', 2, 1, 50]], array_map(fn (array $r) => [$r['grade'], $r['learners'], $r['tutored'], $r['share']], $rows));
        $this->assertSame('Term 1', $rows[0]['term']);

        $page = $this->actingAs($this->actor())->get('/peer-tutoring/measure')->assertOk()
            ->assertSee('Every child teaches something')->assertSeeInOrder(['Grade 7', '2', '1', '50%']);
        foreach (['Asha', 'Ben', 'Cara'] as $name) {
            $page->assertDontSee($name);
        }
        $this->get('/peer-tutoring')->assertOk()->assertSee(route('peer-tutoring-panel.measure'), false);
        $this->get('/peer-tutoring/measure?grade=7')->assertUnprocessable();

        // A corrected session no longer counts.
        $this->travelTo(CarbonImmutable::parse('2026-02-10T16:00:00Z'));
        app(PeerSessions::class)->change($this->actor(), $done->id, 'reverse', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Wrong learner recorded']);
        $this->assertSame(0, app(PeerMeasure::class)->rows($this->actor())[1]['tutored']);
    }

    public function test_grade_head_sees_only_their_grade_and_other_roles_are_refused(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade('6'),
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Grade 6 head']);
        $this->assertSame(['Grade 6'], array_column(app(PeerMeasure::class)->rows($this->actor('theo')), 'grade'));
        $this->actingAs($this->actor('theo'))->get('/peer-tutoring/measure')->assertOk()->assertSee('Grade 6')->assertDontSee('Grade 7');

        foreach (['tara', 'farid', 'asha'] as $name) {
            $this->refused(fn () => app(PeerMeasure::class)->rows($this->actor($name)), [403, 404]);
            $this->actingAs($this->actor($name))->get('/peer-tutoring/measure')->assertForbidden();
        }
        $this->actingAs($this->actor())->get('/peer-tutoring')->assertOk()->assertDontSee(route('peer-tutoring-panel.measure'), false);
    }

    public function test_school_measure_never_counts_another_school(): void
    {
        $this->actor()->givePermissionTo('academic-structure-manage');
        $this->completeAt(app(PeerSessions::class)->create($this->actor(), $this->input()), '2026-02-10T14:00:00Z');
        $rows = app(CurrentSchool::class)->run(2, function () {
            foreach (['peer_tutoring', 'mastery'] as $module) {
                SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
            }
            app(ModuleGate::class)->forget();
            SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
            ClassSchool::whereKey($this->schoolB->ids['class_a'])->update(['grade_level_id' => $this->grade('7')]);
            Enrolment::create(['learner_id' => $this->schoolB->ids['ben'], 'class_section_id' => $this->schoolB->ids['section_a'], 'session_year_id' => $this->schoolB->ids['session_year'],
                'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
            $other = User::findOrFail($this->schoolB->ids['tara_user']);
            $other->forceFill(['password_change_required' => false])->save();
            $other->givePermissionTo('academic-structure-manage');

            return app(PeerMeasure::class)->rows($other);
        });
        $this->assertSame([['Grade 7', 1, 0]], array_map(fn (array $r) => [$r['grade'], $r['learners'], $r['tutored']], $rows));
        $this->assertSame([['Grade 6', 1, 0], ['Grade 7', 2, 1]], array_map(fn (array $r) => [$r['grade'], $r['learners'], $r['tutored']], app(PeerMeasure::class)->rows($this->actor())));
    }
}
