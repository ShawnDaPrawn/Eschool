<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Jobs\Growth\RecomputeMastery;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasterySettings;
use App\Models\Platform\AuditLog;
use App\Services\Growth\MasteryModels;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/** Action Plan 3.8: mastery model v2 beside recent20-v1, chosen per school. */
class MasteryModelV2Test extends TestCase
{
    use UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
    }

    private function records(?string $version = null): array
    {
        $topics = array_flip($this->nodes);

        return MasteryRecord::query()->when($version, fn ($q) => $q->where('policy_version', $version))->orderBy('policy_version')->orderBy('topic_id')->orderBy('audience')->get()
            ->map(fn ($r) => ['topic' => array_search($topics[$r->topic_id], $this->keys, true), 'audience' => $r->audience, 'level' => $r->level, 'score_bp' => $r->score_bp,
                'confidence_bp' => $r->confidence_bp, 'evidence_count' => $r->evidence_count, 'policy_version' => $r->policy_version])->all();
    }

    private function topics(array $view): array
    {
        return array_map(fn ($t) => [$t['name'], $t['level'], $t['confidence_bp'], $t['evidence_count']], $view['topics']);
    }

    private function useModel(string $version): void
    {
        app(MasteryModels::class)->change($this->superAdmin(), ['model_version' => $version, 'expected_revision' => app(TopicGraph::class)->settings()['revision']]);
    }

    /** Marks on two topics, released to the family, and one correct practice answer on Fractions. */
    private function fixtureLearner(): void
    {
        $this->capture();
        $this->approve();
        $this->review($this->issue());
        $this->graph(true, app(TopicGraph::class)->settings()['revision']);
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
    }

    /** The recent20-v1 numbers recorded from main (becc6684) before this step changed any code. */
    private function recordedV1(): array
    {
        $row = fn ($topic, $audience, $level, $score, $confidence, $count) => ['topic' => $topic, 'audience' => $audience, 'level' => $level, 'score_bp' => $score,
            'confidence_bp' => $confidence, 'evidence_count' => $count, 'policy_version' => 'recent20-v1'];

        return [$row(2, 'family', 4, 9000, 2000, 1), $row(2, 'practice', null, null, 0, 0), $row(2, 'staff', 4, 9000, 2000, 1),
            $row(1, 'family', 1, 4000, 2000, 1), $row(1, 'practice', null, null, 0, 0), $row(1, 'staff', 1, 4000, 2000, 1)];
    }

    public function test_default_school_keeps_recent20_v1_records_and_views_exactly_as_before(): void
    {
        $this->assertSame('recent20-v1', app(MasteryModels::class)->active());
        $this->capture();
        $this->graph();
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        $this->assertSame($this->recordedV1(), $this->records());
        $view = $this->masteryView();
        $this->assertSame('recent20-v1', $view['policy_version']);
        $this->assertSame([['Decimals', 4, 2000, 1], ['Fractions', 1, 2000, 1], ['Number foundation', null, 0, 0]], $this->topics($view));
        $this->assertSame('recent20-v1', $this->masteryView('farid', staff: false)['policy_version']);
        $this->assertSame('recent20-v1', app(TopicGraph::class)->settings()['policy_version']);
        $this->assertSame(['question:'.$this->questions[0], 'question:'.$this->questions[1]], MasteryEvidence::where('audience', 'staff')->orderBy('item_key')->pluck('item_key')->all());
    }

    public function test_v2_school_golden_values_for_the_fixture_learner_beside_unchanged_v1(): void
    {
        Queue::fake();
        $this->useModel('weighted-decay-v2');
        $this->fixtureLearner();
        $row = fn ($topic, $audience, $level, $score, $confidence, $count, $version) => ['topic' => $topic, 'audience' => $audience, 'level' => $level, 'score_bp' => $score,
            'confidence_bp' => $confidence, 'evidence_count' => $count, 'policy_version' => $version];
        // recent20-v1 sums units: one 1-unit practice answer beside a 1000-unit mark barely registers.
        $this->assertSame([
            $row(2, 'family', 4, 9000, 2000, 1, 'recent20-v1'), $row(2, 'practice', null, null, 0, 0, 'recent20-v1'), $row(2, 'staff', 4, 9000, 2000, 1, 'recent20-v1'),
            $row(1, 'family', 1, 4006, 4000, 2, 'recent20-v1'), $row(1, 'practice', 4, 10000, 2000, 1, 'recent20-v1'), $row(1, 'staff', 1, 4006, 4000, 2, 'recent20-v1'),
        ], $this->records('recent20-v1'));
        $this->assertSame([
            $row(2, 'family', 2, 6333, 769, 1, 'weighted-decay-v2'), $row(2, 'practice', null, null, 0, 0, 'weighted-decay-v2'), $row(2, 'staff', 2, 6333, 769, 1, 'weighted-decay-v2'),
            $row(1, 'family', 2, 5400, 1111, 2, 'weighted-decay-v2'), $row(1, 'practice', 2, 6000, 400, 1, 'weighted-decay-v2'), $row(1, 'staff', 2, 5400, 1111, 2, 'weighted-decay-v2'),
        ], $this->records('weighted-decay-v2'));
        $view = $this->masteryView();
        $this->assertSame('weighted-decay-v2', $view['policy_version']);
        $this->assertSame([['Decimals', 2, 769, 1], ['Fractions', 2, 1111, 2], ['Number foundation', null, 0, 0]], $this->topics($view));
        $this->assertSame('weighted-decay-v2', $this->masteryView('farid', staff: false)['policy_version']);
        // A month without new evidence: v2 fades towards the midpoint, v1 stays as it was.
        $this->travel(30)->days();
        app(MasteryRecompute::class)->learner($this->schoolA->ids['asha'], $this->subject);
        $this->assertSame($row(2, 'staff', 2, 6286, 732, 1, 'weighted-decay-v2'), $this->records('weighted-decay-v2')[2]);
        $this->assertSame($row(1, 'staff', 2, 5383, 1043, 2, 'weighted-decay-v2'), $this->records('weighted-decay-v2')[5]);
        $this->assertSame($row(2, 'staff', 4, 9000, 2000, 1, 'recent20-v1'), $this->records('recent20-v1')[2]);
        $this->assertSame($row(1, 'staff', 1, 4006, 4000, 2, 'recent20-v1'), $this->records('recent20-v1')[5]);
    }

    public function test_switching_back_to_v1_is_immediate_and_stored_v2_rows_keep_their_label(): void
    {
        Queue::fake();
        $this->useModel('weighted-decay-v2');
        $this->capture();
        app(MasteryRecompute::class)->group($this->group);
        $v2 = $this->records('weighted-decay-v2');
        $this->useModel('recent20-v1');
        $view = $this->masteryView();
        $this->assertSame('recent20-v1', $view['policy_version']);
        $this->assertSame([['Decimals', 4, 2000, 1], ['Fractions', 1, 2000, 1], ['Number foundation', null, 0, 0]], $this->topics($view));
        $this->capture('10.00', '10.00', 1);
        app(MasteryRecompute::class)->group($this->group);
        // While the school is on v1, v2 rows are neither recomputed nor relabelled.
        $this->assertSame($v2, $this->records('weighted-decay-v2'));
        $this->assertSame(4, collect($this->masteryView()['topics'])->firstWhere('name', 'Fractions')['level']);
    }

    public function test_curriculum_publisher_switches_the_model_in_the_panel_with_audit_and_queued_rebuild(): void
    {
        Queue::fake();
        $this->actingAs($this->superAdmin())->get('/growth-mastery')->assertOk()->assertSee('Mastery model for this school')
            ->assertSee('Mastery model v1: the last 20 answers, marks added up');
        $audits = AuditLog::where('action', 'growth.mastery_model_changed')->count();
        $this->post('/growth-mastery/model', ['model_version' => 'weighted-decay-v2', 'expected_revision' => 0])->assertRedirect(route('mastery.index'))
            ->assertSessionHas('success');
        $this->assertSame('weighted-decay-v2', MasterySettings::sole()->model_version);
        $this->assertSame(1, MasterySettings::sole()->revision);
        $audit = AuditLog::where('action', 'growth.mastery_model_changed')->latest('id')->firstOrFail();
        $this->assertSame($audits + 1, AuditLog::where('action', 'growth.mastery_model_changed')->count());
        $this->assertSame(['model_version' => 'recent20-v1'], $audit->before);
        $this->assertSame('weighted-decay-v2', $audit->after['model_version']);
        $this->assertSame((int) $this->superAdmin()->id, (int) $audit->actor_id);
        Queue::assertPushed(RecomputeMastery::class, fn ($job) => $job->groupId === $this->group && $job->schoolId === 1);
        // A stale revision and an unknown model are refused without a change.
        $this->post('/growth-mastery/model', ['model_version' => 'recent20-v1', 'expected_revision' => 0])->assertRedirect(route('mastery.index'))->assertSessionHasErrors('model_version');
        $this->post('/growth-mastery/model', ['model_version' => 'made-up-v9', 'expected_revision' => 1])->assertSessionHasErrors('model_version');
        $this->assertSame('weighted-decay-v2', MasterySettings::sole()->model_version);
        $this->assertSame($audits + 1, AuditLog::where('action', 'growth.mastery_model_changed')->count());
        // The graph editor's revision moved with the model, so its open form must reload.
        $this->assertSame(1, app(TopicGraph::class)->settings()['revision']);
        $this->capture();
        app(MasteryRecompute::class)->group($this->group);
        $this->actingAs($this->actor())->get('/growth-mastery?group_id='.$this->group)->assertOk()
            ->assertSee('data-mastery-version="weighted-decay-v2"', false)->assertSee('Mastery model v2: weighted by recency, difficulty and forgetting')
            ->assertDontSee('Mastery model for this school');
    }

    public function test_wrong_role_cannot_switch_the_model_and_nothing_is_written(): void
    {
        Queue::fake();
        $settings = MasterySettings::count();
        $audits = AuditLog::where('action', 'growth.mastery_model_changed')->count();
        $this->actingAs($this->actor())->post('/growth-mastery/model', ['model_version' => 'weighted-decay-v2', 'expected_revision' => 0])->assertForbidden();
        $this->actingAs($this->actor('farid'))->post('/growth-mastery/model', ['model_version' => 'weighted-decay-v2', 'expected_revision' => 0])->assertRedirect(route('login'));
        foreach (['tara', 'asha', 'farid'] as $name) {
            try {
                app(MasteryModels::class)->change($this->actor($name), ['model_version' => 'weighted-decay-v2', 'expected_revision' => 0]);
                $this->fail('Only a curriculum publisher may change the mastery model.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame($settings, MasterySettings::count());
        $this->assertSame($audits, AuditLog::where('action', 'growth.mastery_model_changed')->count());
        $this->assertSame('recent20-v1', app(MasteryModels::class)->active());
        Queue::assertNothingPushed();
    }

    public function test_school_b_model_choice_does_not_change_school_a(): void
    {
        Queue::fake();
        $current = app(CurrentSchool::class);
        $current->run(2, fn () => MasterySettings::create(['family_marks_enabled' => false, 'revision' => 4, 'model_version' => 'weighted-decay-v2']));
        $this->assertSame('weighted-decay-v2', $current->run(2, fn () => app(MasteryModels::class)->active()));
        $this->assertSame('recent20-v1', app(MasteryModels::class)->active());
        $this->capture();
        app(MasteryRecompute::class)->group($this->group);
        $this->assertSame(0, MasteryRecord::where('policy_version', 'weighted-decay-v2')->count());
        $this->assertSame('recent20-v1', $this->masteryView()['policy_version']);
        // School A's own switch writes only School A's settings.
        $before = DB::table('mastery_settings')->where('school_id', 2)->first();
        $this->useModel('weighted-decay-v2');
        $this->useModel('recent20-v1');
        $this->assertEquals($before, DB::table('mastery_settings')->where('school_id', 2)->first());
        $this->assertSame(0, DB::table('mastery_records')->where('school_id', 2)->count());
        Queue::assertNotPushed(RecomputeMastery::class, fn ($job) => $job->schoolId === 2);
    }

    public function test_item_keys_are_backfilled_and_rollback_refuses_retained_v2_choice(): void
    {
        $this->fixtureLearner();
        $expected = MasteryEvidence::orderBy('id')->pluck('item_key', 'id')->all();
        $this->assertNotContains(null, $expected);
        $this->assertContains('practice_item:'.DB::table('practice_items')->value('id'), $expected);
        MasteryEvidence::query()->update(['item_key' => null]);
        $migration = require database_path('migrations/2026_11_04_120000_add_mastery_model_versions.php');
        (fn () => $this->backfillItemKeys())->call($migration);
        $this->assertSame($expected, MasteryEvidence::orderBy('id')->pluck('item_key', 'id')->all());
        Queue::fake();
        $this->useModel('weighted-decay-v2');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }
}
