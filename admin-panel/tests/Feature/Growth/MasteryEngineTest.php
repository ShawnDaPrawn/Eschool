<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AssessmentTask;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryTopic;
use App\Models\Growth\TopicLink;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Models\Term;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\MasteryScore;
use App\Services\Growth\TopicGraph;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class MasteryEngineTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
    }

    public function test_canonical_question_evidence_scores_unknown_topics_and_explains_root_gaps(): void
    {
        $this->capture();
        $this->graph();
        app(MasteryRecompute::class)->group($this->group);
        $view = $this->masteryView();
        $topics = array_column($view['topics'], null, 'topic_key');
        $this->assertNull($topics[$this->keys[0]]['level']);
        $this->assertSame(1, $topics[$this->keys[1]]['level']);
        $this->assertSame(4, $topics[$this->keys[2]]['level']);
        $this->assertSame(1, $topics[$this->keys[1]]['evidence_count']);
        $this->assertSame($this->nodes[$this->keys[0]], $view['priorities'][0]['topic_id']);
        $this->assertLessThanOrEqual(3, count($view['priorities']));
        $count = MasteryEvidence::count();
        app(MasteryRecompute::class)->group($this->group);
        $this->assertSame($count, MasteryEvidence::count());
    }

    public function test_partial_question_capture_never_invents_a_zero_for_missing_work(): void
    {
        $this->capture('7.00', null);
        app(MasteryRecompute::class)->group($this->group);
        $rows = array_column($this->masteryView()['topics'], null, 'topic_key');
        $this->assertSame(3, $rows[$this->keys[1]]['level']);
        $this->assertNull($rows[$this->keys[2]]['level']);
        $this->assertSame(1, MasteryEvidence::count());
    }

    public function test_family_requires_opt_in_and_frozen_release_evidence_not_later_raw_marks(): void
    {
        $this->capture();
        $this->graph();
        app(MasteryRecompute::class)->group($this->group);
        foreach ($this->masteryView('farid', staff: false)['topics'] as $topic) {
            $this->assertNull($topic['level']);
        }
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        $family = array_column($this->masteryView('farid', staff: false)['topics'], null, 'topic_key');
        $this->assertSame(4, $family[$this->keys[2]]['level']);
        $this->capture('4.00', '0.00', 1);
        app(MasteryRecompute::class)->group($this->group);
        $this->assertSame(0, array_column($this->masteryView()['topics'], null, 'topic_key')[$this->keys[2]]['level']);
        $this->assertSame(4, array_column($this->masteryView('farid', staff: false)['topics'], null, 'topic_key')[$this->keys[2]]['level']);
        $this->graph(false, 1);
        foreach ($this->masteryView('farid', staff: false)['topics'] as $topic) {
            $this->assertNull($topic['level']);
        }
    }

    public function test_former_group_member_keeps_released_question_only_topic_targets(): void
    {
        AssessmentTask::query()->findOrFail($this->task)->update(['topic_keys' => []]);
        $this->capture();
        $this->graph();
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        TeachingGroupMembership::query()->where('learner_id', $this->schoolA->ids['asha'])->delete();
        $family = $this->masteryView('farid', staff: false);
        $this->assertContains($this->nodes[$this->keys[1]], $family['target_topic_ids']);
        $this->assertContains($this->nodes[$this->keys[2]], $family['target_topic_ids']);
        $this->assertSame(4, array_column($family['topics'], 'level', 'topic_key')[$this->keys[2]]);
    }

    public function test_family_visibility_revocation_during_read_suppresses_the_response(): void
    {
        $this->capture();
        $this->graph();
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        $changed = false;
        DB::listen(function ($query) use (&$changed) {
            if (! $changed && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'mastery_records')) {
                $changed = true;
                DB::table('mastery_settings')->where('school_id', 1)->update(['family_marks_enabled' => false]);
            }
        });
        $this->expectException(HttpException::class);
        $this->masteryView('farid', staff: false);
    }

    public function test_wrong_teacher_owner_school_and_revoked_allocation_are_refused(): void
    {
        foreach ([['theo', $this->schoolA->ids['asha']], ['tara', $this->schoolA->ids['ben']], ['tara', $this->schoolB->ids['asha']]] as [$actor, $learner]) {
            try {
                $this->masteryView($actor, $learner);
                $this->fail('Expected scope refusal.');
            } catch (HttpException|ModelNotFoundException $e) {
                $this->assertTrue($e instanceof ModelNotFoundException || in_array($e->getStatusCode(), [403, 404], true));
            }
        }
        TeachingAllocation::query()->where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->expectException(HttpException::class);
        $this->masteryView();
    }

    public function test_unrelated_or_revoked_guardians_and_unapproved_learners_are_refused(): void
    {
        try {
            $this->masteryView('gina', staff: false);
            $this->fail('Unrelated guardian must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
        try {
            $this->masteryView('asha', staff: false);
            $this->fail('Unapproved learner must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id], ['relationship' => 'father', 'status' => 'ended']);
        $this->expectException(HttpException::class);
        $this->masteryView('farid', staff: false);
    }

    public function test_cycles_duplicates_cross_subject_edges_and_stale_revision_do_not_change_graph(): void
    {
        $this->graph();
        foreach ([
            [['topic_id' => $this->nodes[$this->keys[0]], 'prerequisite_id' => $this->nodes[$this->keys[1]], 'source' => 'Synthetic'], ['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic']],
            [['topic_id' => $this->nodes[$this->keys[0]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic']],
            [['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic'], ['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic']],
        ] as $links) {
            try {
                $this->graph(true, 1, $links);
                $this->fail('Invalid dependency graph must be refused.');
            } catch (ValidationException) {
                $this->assertSame(1, TopicLink::count());
                $this->assertSame(1, app(TopicGraph::class)->settings()['revision']);
            }
        }
        $this->expectException(HttpException::class);
        $this->graph(true, 0);
    }

    public function test_cross_subject_and_draft_sources_cannot_enter_the_published_graph(): void
    {
        $existing = CurriculumPlan::query()->firstOrFail();
        $science = $existing->replicate();
        $science->subject_id = $this->schoolA->ids['science'];
        $science->save();
        $scienceAssessment = $existing->assessments()->firstOrFail()->replicate();
        $scienceAssessment->curriculum_plan_id = $science->id;
        $scienceAssessment->save();
        $source = CurriculumTopic::query()->firstOrFail()->replicate();
        $source->curriculum_plan_id = $science->id;
        $source->key = (string) Str::uuid();
        $source->name = 'Science reference';
        $source->save();
        app(TopicGraph::class)->sync((int) $science->subject_id);
        $foreign = MasteryTopic::query()->where('subject_id', $science->subject_id)->sole();
        try {
            $this->graph(links: [['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $foreign->id, 'source' => 'Synthetic']]);
            $this->fail('Cross-subject prerequisite must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
        $draft = $existing->replicate();
        $draft->version = 2;
        $draft->state = 'draft';
        $draft->save();
        $private = $source->replicate();
        $private->curriculum_plan_id = $draft->id;
        $private->key = (string) Str::uuid();
        $private->name = 'PRIVATE DRAFT TOPIC';
        $private->save();
        MasteryTopic::create(['subject_id' => $this->subject, 'topic_key' => $private->key, 'curriculum_topic_id' => $private->id,
            'grade_level_id' => $draft->grade_level_id, 'name' => $private->name]);
        $this->assertStringNotContainsString('PRIVATE DRAFT TOPIC', json_encode(app(TopicGraph::class)->data($this->subject)));
        $this->assertStringNotContainsString('PRIVATE DRAFT TOPIC', json_encode($this->masteryView()));
    }

    public function test_independent_term_publication_controls_new_and_retained_graph_references(): void
    {
        $plan = CurriculumPlan::query()->firstOrFail();
        $term = Term::create(['name' => 'Term two', 'session_year_id' => $this->year, 'start_date' => '2026-04-01', 'end_date' => '2026-06-30']);
        $topic = CurriculumTopic::query()->firstOrFail()->replicate();
        $topic->term_id = $term->id;
        $topic->key = (string) Str::uuid();
        $topic->name = 'Unreviewed term-two topic';
        $topic->save();
        app(TopicGraph::class)->sync($this->subject);
        $this->assertFalse(MasteryTopic::where('topic_key', $topic->key)->exists());
        $assessment = $plan->assessments()->firstOrFail()->replicate();
        $assessment->term_id = $term->id;
        $assessment->state = 'published';
        $assessment->save();
        app(TopicGraph::class)->sync($this->subject);
        $node = MasteryTopic::where('topic_key', $topic->key)->firstOrFail();
        $this->graph(links: [['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $node->id, 'source' => 'Synthetic published term prerequisite']]);
        $this->assertStringContainsString($topic->name, json_encode(app(TopicGraph::class)->data($this->subject)));
        $assessment->update(['state' => 'draft']);
        $graph = app(TopicGraph::class)->data($this->subject);
        $this->assertStringNotContainsString($topic->name, json_encode($graph));
        $this->assertSame([], $graph['links']);
        $this->assertStringNotContainsString($topic->name, json_encode($this->masteryView()));
        $this->assertSame(1, TopicLink::count());
        $this->assertTrue(MasteryTopic::whereKey($node->id)->exists());
    }

    public function test_approved_learner_can_read_and_withdrawal_immediately_refuses_access(): void
    {
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        $approval = LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->assertSame($this->schoolA->ids['asha'], $this->masteryView('asha', staff: false)['learner_id']);
        $approval->update(['withdrawn_at' => now()]);
        $this->expectException(HttpException::class);
        $this->masteryView('asha', staff: false);
    }

    public function test_module_disable_is_fresh_and_future_grade_topics_are_not_current_priorities(): void
    {
        $existing = CurriculumPlan::query()->firstOrFail();
        $future = $existing->replicate();
        $future->grade_level_id = GradeLevel::where('code', '9')->value('id');
        $future->save();
        $source = CurriculumTopic::query()->firstOrFail()->replicate();
        $source->curriculum_plan_id = $future->id;
        $source->key = (string) Str::uuid();
        $source->name = 'Unrelated future grade topic';
        $source->save();
        app(TopicGraph::class)->sync($this->subject);
        $this->assertStringNotContainsString($source->name, json_encode($this->masteryView()));
        SchoolModule::query()->where('module', 'mastery')->update(['enabled' => false]);
        $this->expectException(HttpException::class);
        $this->masteryView();
    }

    public function test_empty_projection_migration_rolls_back_and_reapplies(): void
    {
        DB::rollBack();
        try {
            $migration = require database_path('migrations/2026_10_25_020000_create_mastery_graph_and_evidence.php');
            $migration->down();
            $this->assertFalse(Schema::hasTable('mastery_topics'));
            $migration->up();
            $this->assertTrue(Schema::hasTable('mastery_topics'));
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_rebuild_command_owns_its_scope_and_refuses_disabled_or_foreign_groups(): void
    {
        $this->capture();
        $this->artisan('mastery:rebuild', ['school' => 1, '--group' => $this->group])->assertSuccessful();
        $this->assertSame(2, MasteryEvidence::count());
        $this->artisan('mastery:rebuild', ['school' => 1, '--group' => 999999])->assertFailed();
        $this->artisan('mastery:rebuild', ['school' => 999999])->assertFailed();
        SchoolModule::query()->where('module', 'mastery')->update(['enabled' => false]);
        $this->artisan('mastery:rebuild', ['school' => 1, '--group' => $this->group])->assertFailed();
    }

    public function test_role_cannot_edit_graph_and_retained_evidence_blocks_rollback(): void
    {
        try {
            app(TopicGraph::class)->save($this->actor(), $this->subject, ['expected_revision' => 0, 'family_marks_enabled' => true, 'links' => []]);
            $this->fail('Teacher may not publish source dependencies.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->capture();
        app(MasteryRecompute::class)->group($this->group);
        $migration = require database_path('migrations/2026_10_25_020000_create_mastery_graph_and_evidence.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_exact_integer_levels_and_confidence_distinguish_unknown_and_zero(): void
    {
        $score = app(MasteryScore::class);
        $this->assertNull($score->calculate([])['level']);
        foreach ([0 => 0, 2499 => 0, 2500 => 1, 4999 => 1, 5000 => 2, 6999 => 2, 7000 => 3, 8499 => 3, 8500 => 4, 10000 => 4] as $mark => $level) {
            $this->assertSame($level, $score->calculate([['achieved_units' => $mark, 'maximum_units' => 10000]])['level']);
        }
        $this->assertSame(10000, $score->calculate(array_fill(0, 5, ['achieved_units' => 1, 'maximum_units' => 2]))['confidence_bp']);
        $this->expectException(\InvalidArgumentException::class);
        $score->calculate([['achieved_units' => 3, 'maximum_units' => 2]]);
    }
}
