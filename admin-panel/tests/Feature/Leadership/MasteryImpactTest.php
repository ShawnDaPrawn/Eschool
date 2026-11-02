<?php

declare(strict_types=1);

namespace Tests\Feature\Leadership;

use App\Jobs\Growth\CaptureMasteryGrowth;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryGrowthSnapshot;
use App\Models\Growth\MasteryRecord;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\MasteryGrowth;
use App\Services\Growth\MasteryRecompute;
use App\Services\Leadership\MasteryImpact;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class MasteryImpactTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        // The scenario pins the term (2026-01-01 to 2026-03-31), the year and the memberships; pin the time of day too.
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        config(['modules.leadership_dashboard' => ['always_on' => true]]);
        app(ModuleGate::class)->forget();
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
    }

    private function at(string $date): void
    {
        $this->travelTo(CarbonImmutable::parse($date.' 10:00:00', 'Africa/Johannesburg'));
    }

    private function topic(int $index): int
    {
        return (int) $this->nodes[$this->keys[$index]];
    }

    private function evidence(int $topic, int $achieved, string $observed, ?int $learner = null, string $audience = 'staff'): void
    {
        MasteryEvidence::create(['subject_id' => $this->subject, 'learner_id' => $learner ?? $this->schoolA->ids['asha'], 'topic_id' => $topic,
            'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'audience' => $audience, 'source_type' => 'synthetic_prior',
            'source_key' => 'synthetic:'.++$this->sequence, 'achieved_units' => $achieved * 100, 'maximum_units' => 1000, 'observed_at' => $observed]);
    }

    /** Number foundation held at level 3, Fractions rose from 0 to 1, Decimals first measured this term. */
    private function growth(): void
    {
        $this->evidence($this->topic(0), 8, '2025-12-01 10:00:00');
        $this->evidence($this->topic(1), 2, '2025-12-01 10:00:00');
        $this->capture('4.00', '9.00');
        app(MasteryRecompute::class)->group($this->group);
    }

    private function row(int $topic, string $audience = 'staff'): MasteryGrowthSnapshot
    {
        return MasteryGrowthSnapshot::where('topic_id', $topic)->where('audience', $audience)->where('learner_id', $this->schoolA->ids['asha'])->sole();
    }

    private function values(): array
    {
        return MasteryGrowthSnapshot::orderBy('id')->get()->map(fn ($r) => collect($r->getAttributes())->except(['updated_at'])->all())->all();
    }

    private function lead(User $user, string $kind, ?int $grade, string $ends = '2026-12-31'): void
    {
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $user->id, 'session_year_id' => $this->year, 'kind' => $kind,
            'grade_level_id' => $grade, 'starts_on' => '2026-01-01', 'ends_on' => $ends, 'reason' => 'Synthetic impact appointment']);
    }

    private function refused(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected a refusal');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_capture_replays_term_start_and_latest_once_per_learner_topic_term_audience_and_version(): void
    {
        $this->growth();
        $result = app(MasteryGrowth::class)->capture();
        $this->assertSame(['terms' => 1, 'ambiguous_terms' => 0, 'rows' => 9, 'closed' => 0, 'invalid' => 0], $result);
        $this->assertSame(9, MasteryGrowthSnapshot::count());
        $held = $this->row($this->topic(0));
        $this->assertSame([3, 8000, 1, 3, 8000, 1], [$held->start_level, $held->start_score_bp, $held->start_evidence_count, $held->end_level, $held->end_score_bp, $held->end_evidence_count]);
        $rose = $this->row($this->topic(1));
        $this->assertSame(['replayed', 0, 2000, 2000, 1, 1, 3000, 4000, 2], [$rose->start_basis, $rose->start_level, $rose->start_score_bp, $rose->start_confidence_bp,
            $rose->start_evidence_count, $rose->end_level, $rose->end_score_bp, $rose->end_confidence_bp, $rose->end_evidence_count]);
        $this->assertSame([$this->term, $this->year, $this->grade, 'recent20-v1', '2026-02-10', false],
            [(int) $rose->term_id, (int) $rose->session_year_id, (int) $rose->grade_level_id, $rose->policy_version, $rose->end_as_of->toDateString(), $rose->end_final]);
        $first = $this->row($this->topic(2));
        $this->assertNull($first->start_level);
        $this->assertSame(0, $first->start_evidence_count);
        $this->assertSame(4, $first->end_level);
        // No practice evidence: unknown, never a zero level or score.
        $practice = $this->row($this->topic(1), 'practice');
        $this->assertSame([null, null, null, null], [$practice->start_level, $practice->start_score_bp, $practice->end_level, $practice->end_score_bp]);

        $before = $this->values();
        $this->assertSame(9, app(MasteryGrowth::class)->capture()['rows']);
        $this->assertSame($before, $this->values());

        // Evidence that arrives later, even dated before the term, never moves a recorded start.
        $this->evidence($this->topic(2), 1, '2025-12-15 10:00:00');
        app(MasteryRecompute::class)->learner($this->schoolA->ids['asha'], $this->subject);
        app(MasteryGrowth::class)->capture();
        $first->refresh();
        $this->assertSame([null, 0, 2, 5000, 2], [$first->start_level, $first->start_evidence_count, $first->end_level, $first->end_score_bp, $first->end_evidence_count]);
    }

    public function test_term_end_uses_retained_marks_before_a_later_correction_and_release(): void
    {
        $this->growth();
        $this->release();
        $this->at('2026-04-05');
        $this->capture('10.00', '1.00', 1);
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        app(MasteryGrowth::class)->capture();
        $this->assertSame(3000, $this->row($this->topic(1))->end_score_bp);
        $this->assertSame(9000, $this->row($this->topic(2))->end_score_bp);
        $this->assertSame(4000, $this->row($this->topic(1), 'family')->end_score_bp);
        $this->assertSame(9000, $this->row($this->topic(2), 'family')->end_score_bp);
    }

    public function test_rostered_topics_without_any_evidence_are_visible(): void
    {
        $report = app(MasteryImpact::class)->read($this->superAdmin(), $this->term);
        $this->assertCount(3, $report['grades'][0]['topics']);
        foreach ($report['grades'][0]['topics'] as $topic) {
            $this->assertSame(1, $topic['no_evidence']);
            $this->assertSame(0, $topic['evidence_now']);
        }
    }

    public function test_term_end_settles_within_the_closing_window_and_is_then_left_alone(): void
    {
        $this->growth();
        app(MasteryGrowth::class)->capture();
        $this->at('2026-04-05');
        $this->evidence($this->topic(1), 10, '2026-04-02 10:00:00');
        app(MasteryRecompute::class)->learner($this->schoolA->ids['asha'], $this->subject);
        app(MasteryGrowth::class)->capture();
        $row = $this->row($this->topic(1));
        $this->assertSame([1, 3000, 2, '2026-03-31', true], [$row->end_level, $row->end_score_bp, $row->end_evidence_count, $row->end_as_of->toDateString(), $row->end_final]);
        $this->at('2026-04-20');
        $before = $this->values();
        $this->assertSame(['terms' => 0, 'ambiguous_terms' => 0, 'rows' => 0, 'closed' => 0, 'invalid' => 0], app(MasteryGrowth::class)->capture());
        $this->assertSame($before, $this->values());
    }

    public function test_another_model_version_keeps_its_own_rows_and_an_unrecorded_start_stays_unknown(): void
    {
        $ben = $this->schoolA->ids['ben'];
        $cara = $this->schoolA->ids['cara'];
        $record = fn (int $learner, int $level) => MasteryRecord::updateOrCreate(['subject_id' => $this->subject, 'learner_id' => $learner, 'topic_id' => $this->topic(2),
            'audience' => 'staff'], ['level' => $level, 'score_bp' => $level * 2000, 'confidence_bp' => 2000, 'evidence_count' => 1, 'policy_version' => 'other-v9', 'computed_at' => now()]);
        $this->at('2026-01-01');
        $record($ben, 1);
        app(MasteryGrowth::class)->capture();
        $this->at('2026-02-10');
        $record($ben, 3);
        $record($cara, 2);
        app(MasteryGrowth::class)->capture();
        $first = MasteryGrowthSnapshot::where('learner_id', $ben)->sole();
        $this->assertSame(['other-v9', 'captured', 1, 3, false], [$first->policy_version, $first->start_basis, $first->start_level, $first->end_level, $first->end_final]);
        $late = MasteryGrowthSnapshot::where('learner_id', $cara)->sole();
        $this->assertSame(['unavailable', null, null, null, 2], [$late->start_basis, $late->start_level, $late->start_score_bp, $late->start_evidence_count, $late->end_level]);
        // After the term a stored record keeps changing; its snapshot does not.
        $this->at('2026-04-05');
        $record($ben, 0);
        $this->assertSame(2, app(MasteryGrowth::class)->capture()['closed']);
        $first->refresh();
        $this->assertSame([3, true], [$first->end_level, $first->end_final]);

        $report = app(MasteryImpact::class)->read($this->superAdmin(), $this->term, 'other-v9');
        $this->assertSame(['other-v9'], $report['versions']);
        $totals = $report['grades'][0]['totals'];
        $this->assertSame([1, 0, 0, 0, 1], [$totals['rose'], $totals['held'], $totals['lower'], $totals['first_evidence'], $totals['start_unknown']]);
        // Without recent20-v1 rows the only version is shown; asking for recent20-v1 never mixes another version in.
        $this->assertSame('other-v9', app(MasteryImpact::class)->read($this->superAdmin(), $this->term)['policy_version']);
        $this->assertSame([], app(MasteryImpact::class)->read($this->superAdmin(), $this->term, 'recent20-v1')['grades']);
    }

    public function test_principal_reads_whole_school_counts_without_any_learner_detail(): void
    {
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic impact fixture']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['ben'], 'mark_rule' => 'catch_up',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Enrolled subject'], $this->group);
        $this->growth();
        app(MasteryGrowth::class)->capture();
        $principal = $this->actor('tara');
        $this->lead($principal, 'principal', null);
        $report = app(MasteryImpact::class)->read($principal);
        $this->assertSame(['school', $this->term, 'recent20-v1', ['recent20-v1']], [$report['scope'], $report['term']['id'], $report['policy_version'], $report['versions']]);
        $grade = $report['grades'][0];
        $this->assertSame($this->grade, $grade['grade_id']);
        $byTopic = collect($grade['topics'])->keyBy('topic_name');
        $this->assertSame(['rose' => 0, 'held' => 1, 'lower' => 0, 'first_evidence' => 0, 'start_unknown' => 0, 'no_evidence' => 1, 'evidence_at_start' => 1, 'evidence_now' => 1],
            collect($byTopic['Number foundation'])->only(MasteryImpact::COUNTS)->all());
        $this->assertSame([1, 0, 1], [$byTopic['Fractions']['rose'], $byTopic['Fractions']['first_evidence'], $byTopic['Fractions']['no_evidence']]);
        $this->assertSame([0, 1, 0, 1], [$byTopic['Decimals']['rose'], $byTopic['Decimals']['first_evidence'], $byTopic['Decimals']['evidence_at_start'], $byTopic['Decimals']['no_evidence']]);
        $this->assertSame([1, 1, 0, 1, 3], [$grade['totals']['rose'], $grade['totals']['held'], $grade['totals']['lower'], $grade['totals']['first_evidence'], $grade['totals']['no_evidence']]);
        $this->assertStringNotContainsString('learner', json_encode($report));

        $this->actingAs($principal)->get('/leadership-dashboard/mastery-impact')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Mastery impact')->assertSee('Fractions')->assertSee('Rose a level')->assertDontSee('Asha')->assertDontSee('Baobab');
        $this->get('/leadership-dashboard')->assertOk()->assertSee(route('mastery-impact.index'));
    }

    public function test_grade_head_sees_only_their_grade_and_ordinary_staff_parents_and_operators_are_refused(): void
    {
        $this->growth();
        app(MasteryGrowth::class)->capture();
        $eight = (int) GradeLevel::where('code', '8')->value('id');
        MasteryGrowthSnapshot::create(['term_id' => $this->term, 'session_year_id' => $this->year, 'subject_id' => $this->subject, 'topic_id' => $this->topic(0),
            'grade_level_id' => $eight, 'learner_id' => $this->schoolA->ids['cara'], 'audience' => 'staff', 'policy_version' => 'recent20-v1', 'start_basis' => 'replayed',
            'start_level' => 3, 'start_score_bp' => 7000, 'start_confidence_bp' => 2000, 'start_evidence_count' => 1, 'end_level' => 2, 'end_score_bp' => 6000,
            'end_confidence_bp' => 4000, 'end_evidence_count' => 2, 'end_as_of' => '2026-02-10', 'end_final' => false, 'captured_at' => now()]);
        $this->assertSame([$this->grade, $eight], array_column(app(MasteryImpact::class)->read($this->superAdmin())['grades'], 'grade_id'));
        $head = $this->actor('theo');
        $this->actingAs($head)->get('/leadership-dashboard/mastery-impact')->assertForbidden();
        $this->lead($head, 'grade_head', $eight);
        $report = app(MasteryImpact::class)->read($head);
        $this->assertSame(['grades', [$eight]], [$report['scope'], array_column($report['grades'], 'grade_id')]);
        $this->assertSame(1, $report['grades'][0]['totals']['lower']);
        $this->get('/leadership-dashboard/mastery-impact')->assertOk()->assertSee('You see the grades you lead as grade head.')->assertSee('Grade 8')->assertDontSee('Grade 7');
        // The grade head does not gain the principal's leadership dashboard.
        $this->get('/leadership-dashboard')->assertForbidden();
        AcademicLeadership::where('user_id', $head->id)->update(['ends_on' => '2026-02-09']);
        $this->get('/leadership-dashboard/mastery-impact')->assertForbidden();

        $count = MasteryGrowthSnapshot::count();
        $this->actingAs($this->actor('farid'))->get('/leadership-dashboard/mastery-impact')->assertRedirect(route('login'));
        $principal = $this->actor('tara');
        $this->lead($principal, 'principal', null);
        $principal->forceFill(['password_change_required' => true])->save();
        $this->refused(fn () => app(MasteryImpact::class)->read($principal));
        $principal->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $principal->id]);
        $this->actingAs($principal)->get('/leadership-dashboard/mastery-impact')->assertForbidden();
        $this->assertSame($count, MasteryGrowthSnapshot::count());
        SchoolModule::where('module', 'mastery')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->actingAs($this->superAdmin())->get('/leadership-dashboard/mastery-impact')->assertNotFound();
    }

    public function test_school_b_terms_and_rows_never_cross_into_school_a_and_capture_stays_in_its_school(): void
    {
        $this->growth();
        app(MasteryGrowth::class)->capture();
        $bTerm = $this->schoolB->ids['term'];
        $bRow = app(CurrentSchool::class)->run(2, function () use ($bTerm) {
            Term::whereKey($bTerm)->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);

            return MasteryGrowthSnapshot::create(['term_id' => $bTerm, 'session_year_id' => $this->schoolB->ids['session_year'], 'subject_id' => $this->schoolB->ids['maths'],
                'topic_id' => $this->topic(0), 'grade_level_id' => $this->grade, 'learner_id' => $this->schoolB->ids['asha'], 'audience' => 'staff',
                'policy_version' => 'recent20-v1', 'start_basis' => 'replayed', 'start_level' => 1, 'start_score_bp' => 3000, 'start_confidence_bp' => 2000,
                'start_evidence_count' => 1, 'end_level' => 4, 'end_score_bp' => 9000, 'end_confidence_bp' => 4000, 'end_evidence_count' => 2,
                'end_as_of' => '2026-02-10', 'end_final' => false, 'captured_at' => now()])->fresh()->getAttributes();
        });
        $this->assertSame(9, MasteryGrowthSnapshot::count());
        $principal = $this->actor('tara');
        $this->lead($principal, 'principal', null);
        $this->actingAs($principal)->get('/leadership-dashboard/mastery-impact?term_id='.$bTerm)->assertNotFound();
        $report = app(MasteryImpact::class)->read($principal);
        $this->assertSame(1, $report['grades'][0]['totals']['rose']);
        $this->assertNotContains($bTerm, array_column($report['terms'], 'id'));

        // School A's capture and School B's (module off there) leave the other school's rows alone.
        $this->artisan('mastery:growth-snapshots', ['school' => 1, '--backfill' => true])->assertSuccessful();
        $this->artisan('mastery:growth-snapshots', ['school' => 2, '--backfill' => true])->assertFailed();
        $this->assertSame($bRow, MasteryGrowthSnapshot::acrossSchools()->findOrFail($bRow['id'])->getAttributes());
        $this->assertSame(1, MasteryGrowthSnapshot::acrossSchools()->where('school_id', 2)->count());
        app(CurrentSchool::class)->run(2, function () use ($principal) {
            try {
                app(MasteryImpact::class)->read($principal);
                $this->fail('No School B membership');
            } catch (HttpException|ModelNotFoundException $e) {
                $this->assertTrue($e instanceof ModelNotFoundException || in_array($e->getStatusCode(), [403, 404], true));
            }
        });
    }

    public function test_nightly_schedule_queues_one_capture_per_enabled_school_and_the_job_writes_rows(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === 'mastery-growth-snapshots');
        $this->assertNotNull($event);
        $this->assertStringContainsString('mastery:growth-snapshots', $event->command);
        $this->assertSame('20 4 * * *', $event->expression);
        Queue::fake();
        $this->artisan('mastery:growth-snapshots')->assertSuccessful();
        Queue::assertPushed(CaptureMasteryGrowth::class, 1);
        Queue::assertPushed(CaptureMasteryGrowth::class, fn ($job) => $job->schoolId === 1);
        $this->artisan('mastery:growth-snapshots', ['school' => 'x'])->assertFailed();

        $this->growth();
        (new CaptureMasteryGrowth(1))->handle(app(CurrentSchool::class), app(MasteryGrowth::class));
        $this->assertSame(9, MasteryGrowthSnapshot::count());
    }
}
