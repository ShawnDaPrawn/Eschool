<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\GradeLevel;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperMemo;
use App\Models\Learning\PastPaperVersion;
use App\Models\Platform\SchoolModule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Learning\Marking\MarkingEngine;
use App\Services\Learning\Marking\PaperMarking;
use App\Services\Learning\PastPaperLibrary;
use App\Services\Learning\PastPaperMemos;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * Marking answers against exam coach memos (Action Plan 3.16): the teacher
 * preview in the panel, the learner service call behind the accuracy gate,
 * and the memo rule kinds the engine evaluates.
 * engineering/specs/learning/marking-engine.md.
 */
class MarkingPreviewTest extends TestCase
{
    use UsesPracticeScenario;

    private array $paper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'exam_coach'], ['enabled' => true, 'in_package' => true]);
        Subject::whereKey($this->subject)->update(['name' => 'Physical Sciences']);
        $this->paper = $this->publishedPaper();
    }

    private function publishedPaper(): array
    {
        $library = app(PastPaperLibrary::class);
        $topic = [$this->nodes[$this->keys[1]]];
        $draft = $library->draft($this->actor(), $this->group, null, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'title' => 'Synthetic licensed motion paper', 'framework' => 'school', 'paper_year' => 2025, 'paper_code' => 'P1', 'group_ids' => [$this->group],
            'licence' => ['kind' => 'school_owned', 'reference' => 'Synthetic original school paper',
                'statement' => 'Synthetic school-owned rights confirmed for testing only.', 'confirmed' => true, 'expires_on' => '2026-12-31'],
            'questions' => [
                ['number' => '1.1', 'prompt' => 'A car travels 100 m in 8 s. Find its speed.', 'memo' => 'v = d/t, 12.5 m/s.', 'max_units' => 300, 'topic_ids' => $topic],
                ['number' => '1.2', 'prompt' => 'Explain why speed is a scalar.', 'memo' => 'It has magnitude only.', 'max_units' => 200, 'topic_ids' => $topic],
            ]]);

        return $library->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => $draft['revision']]);
    }

    private function point(string $earns, string $type, string $marks, string $rule = 'none', ?string $value = null, ?string $tolerance = null, ?string $unit = null): array
    {
        return ['earns' => $earns, 'type' => $type, 'marks' => $marks, 'rule_kind' => $rule, 'rule_value' => $value, 'rule_tolerance' => $tolerance, 'rule_unit' => $unit];
    }

    private function memo(int $revision = 0, ?array $second = null): array
    {
        return app(PastPaperMemos::class)->save($this->actor(), $this->group, $this->paper['version_id'], ['expected_revision' => $revision, 'questions' => [
            ['number' => '1.1', 'points' => [
                $this->point('Uses v = d/t', 'method', '1', 'expression', 'v = d/t'),
                $this->point('SECRET-MEMO-POINT 12,5 m/s', 'answer', '1', 'exact_value', '12.5', '0.05', 'm/s'),
                $this->point('Gives the unit', 'accuracy', '1', 'units_required', null, null, 'm/s'),
            ]],
            ['number' => '1.2', 'points' => $second ?? [
                $this->point('Magnitude only', 'explanation', '1', 'keywords', 'magnitude, direction'),
                $this->point('Names it a scalar', 'answer', '1', 'keywords', 'scalar/skalaar'),
            ]],
        ]]);
    }

    private function path(string $suffix = '/marking', ?string $version = null): string
    {
        return '/exam-memos/groups/'.$this->group.'/versions/'.($version ?? $this->paper['version_id']).$suffix;
    }

    private function answer(string $question = '1.1', string $answer = "v = d/t\nv = 100/8 = 12,5 m·s⁻¹"): array
    {
        return ['question' => $question, 'answer' => $answer];
    }

    private function reader(): User
    {
        $reader = $this->actor('theo');
        $reader->forceFill(['password_change_required' => false])->save();
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $reader->id)->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic additional reader'], $this->group);

        return $reader;
    }

    private function snapshot(): array
    {
        return [PastPaperMemo::count(), (array) DB::table('past_paper_memos')->first(), DB::table('audit_log')->count()];
    }

    public function test_memos_store_expression_terms_and_significant_figure_rules(): void
    {
        $this->memo(0, [
            $this->point('Magnitude only', 'explanation', '0.5', 'keywords', 'magnitude/grootte; no direction'),
            $this->point('Three significant figures', 'accuracy', '0.5', 'significant_figures', '3'),
            $this->point('States the speed', 'answer', '1', 'expression', '2(x + 3)'),
        ]);
        $points = PastPaperMemo::sole()->mark_points[1]['points'];
        $this->assertSame(['kind' => 'keywords', 'value' => 'magnitude/grootte; no direction', 'terms' => [['magnitude', 'grootte'], ['no direction']]], $points[0]['rule']);
        $this->assertSame(['kind' => 'significant_figures', 'value' => '3', 'figures' => 3], $points[1]['rule']);
        $this->assertSame(['kind' => 'expression', 'value' => '2(x + 3)'], $points[2]['rule']);
        $this->assertSame(['kind' => 'expression', 'value' => 'v = d/t'], PastPaperMemo::sole()->mark_points[0]['points'][0]['rule']);

        foreach ([
            [$this->point('Bad rule', 'answer', '1', 'expression', '2x +'), 'unreadable expression'],
            [$this->point('Bad rule', 'answer', '1', 'expression', '2x', null, 'm'), 'expression with a unit'],
            [$this->point('Bad rule', 'answer', '1', 'keywords', ' , ; '), 'no terms'],
            [$this->point('Bad rule', 'answer', '1', 'keywords', implode(',', range(1, 11))), 'too many terms'],
            [$this->point('Bad rule', 'answer', '1', 'significant_figures', '0'), 'zero figures'],
            [$this->point('Bad rule', 'answer', '1', 'significant_figures', '3.5'), 'not whole'],
            [$this->point('Bad rule', 'answer', '1', 'exact_value', 'twelve'), 'an exact value must be a number'],
        ] as [$bad, $why]) {
            try {
                $this->memo(1, [$bad, $this->point('Rest', 'answer', '1')]);
                $this->fail('Expected a refused rule: '.$why);
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('questions.1.points.0.rule_kind', $error->errors(), $why.' '.json_encode($error->errors()));
            }
        }
        $this->assertSame(1, (int) PastPaperMemo::sole()->revision, 'refused rules change nothing');
    }

    public function test_teacher_previews_marking_against_the_memo_and_nothing_is_stored(): void
    {
        $this->memo();
        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->get('/exam-memos/groups/'.$this->group.'/versions/'.$this->paper['version_id'])->assertOk()->assertSee('Try marking an answer against this memo');
        $this->get($this->path())->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Try marking an answer')->assertSee('Physical Sciences')->assertSee('This memo is not reviewed yet')
            ->assertSee('Learner marking is off for Physical Sciences')->assertSee('Question 1.1 (3.00 marks)');
        $before = $this->snapshot();

        $page = $this->post($this->path(), $this->answer())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $page->assertSee('Awarded 3.00 of 3.00 marks.')->assertSee('Uses v = d/t')->assertSee('The formula from the memo is written on line 1.')
            ->assertSee('The answer matches the memo.')->assertSee('The answer has the right unit.');
        $this->assertSame($before, $this->snapshot(), 'a preview writes nothing, not even the audit log');

        $this->post($this->path(), $this->answer('1.1', 'v = 100/8 = 12,5'))->assertOk()
            ->assertSee('Awarded 0.00 of 3.00 marks.')->assertSee('In Physical Sciences the unit is part of the answer.');
        $this->post($this->path(), $this->answer('1.2', "It has magnitude and no direction.\nIt is a scalar."))->assertOk()
            ->assertSee('Awarded 1.00 of 2.00 marks.')->assertSee('1.00 marks need a teacher.')->assertSee('Automatic judgement is switched off.');

        $service = app(PaperMarking::class)->preview($this->actor(), $this->group, $this->paper['version_id'], $this->answer());
        $this->assertSame(['draft', 'physical_sciences', false], [$service['memo_state'], $service['profile']['key'], $service['profile']['learner_marking']]);
        $this->assertSame(['awarded', 'awarded', 'awarded'], array_column($service['result']['points'], 'status'));
        $this->assertSame($before, $this->snapshot());
    }

    public function test_preview_input_is_bounded_and_needs_complete_mark_points(): void
    {
        $this->memo(0, []);
        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->get($this->path())->assertOk()->assertDontSee('Question 1.2 (2.00 marks)');
        $this->post($this->path(), $this->answer('1.2', 'scalar'))->assertRedirect($this->path())->assertSessionHasErrors('question');
        $this->post($this->path(), $this->answer('9.9', 'x'))->assertNotFound();
        $this->post($this->path(), $this->answer('1.1', str_repeat("1\n", 41)))->assertSessionHasErrors('answer');
        $this->post($this->path(), $this->answer('1.1', str_repeat('x', 4001)))->assertSessionHasErrors('answer');
        $this->post($this->path(), ['question' => '<script>', 'answer' => 'x'])->assertSessionHasErrors('question');
        $this->post($this->path(), $this->answer('1.1', ''))->assertOk()->assertSee('No answer was given.');
    }

    public function test_wrong_role_learner_and_parent_cannot_use_the_preview(): void
    {
        $this->memo();
        $this->refuses(fn () => app(PaperMarking::class)->preview($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer()), 403);
        $this->refuses(fn () => app(PaperMarking::class)->preview($this->actor('farid'), $this->group, $this->paper['version_id'], $this->answer()), 403);
        $this->actingAs($this->actor('asha'), 'web')->withSession(['school_id' => 1]);
        $this->get($this->path())->assertForbidden();
        $this->post($this->path(), $this->answer())->assertForbidden();
        $this->actingAs($this->actor('farid'), 'web')->withSession(['school_id' => 1]);
        $this->get($this->path())->assertRedirect('/login');
        $this->post($this->path(), $this->answer())->assertRedirect('/login');
    }

    public function test_wrong_owner_a_teacher_outside_the_group_cannot_preview_until_allocated(): void
    {
        $this->memo();
        $theo = $this->actor('theo');
        $theo->forceFill(['password_change_required' => false])->save();
        $this->actingAs($theo, 'web')->withSession(['school_id' => 1]);
        $outside = $this->get($this->path())->getStatusCode();
        $this->assertContains($outside, [403, 404], 'a teacher of another group');
        $this->assertContains($this->post($this->path(), $this->answer())->getStatusCode(), [403, 404]);
        $this->assertNull(app(PaperMarking::class)->learner($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer()));

        $reader = $this->reader();
        $this->actingAs($reader, 'web')->withSession(['school_id' => 1]);
        $this->post($this->path(), $this->answer())->assertOk()->assertSee('Awarded 3.00 of 3.00 marks.');
    }

    public function test_wrong_school_school_b_memos_cannot_be_used_and_are_unchanged(): void
    {
        $other = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            $paper = PastPaper::create(['subject_id' => $this->schoolB->ids['maths'], 'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'),
                'framework' => 'school', 'created_by' => $this->schoolB->ids['tara_user']]);
            $version = PastPaperVersion::create(['id' => (string) Str::uuid(), 'past_paper_id' => $paper->id, 'number' => 1, 'paper_year' => 2025, 'paper_code' => 'Baobab P1',
                'group_ids' => [$this->group], 'created_by' => $this->schoolB->ids['tara_user'], 'fingerprint' => str_repeat('c', 64), 'state' => 'published',
                'definition' => ['title' => 'Baobab paper', 'licence' => ['kind' => 'school_owned', 'confirmed' => true],
                    'questions' => [['number' => '1.1', 'prompt' => 'Baobab prompt', 'memo' => 'Baobab memo', 'max_units' => 100, 'topic_ids' => [1]]]]]);
            $paper->update(['published_version_id' => $version->id]);
            $memo = PastPaperMemo::create(['past_paper_version_id' => $version->id, 'state' => 'reviewed', 'mark_points' => [['number' => '1.1', 'points' => [
                ['earns' => 'BAOBAB-SECRET', 'type' => 'answer', 'mark_units' => 100, 'rule' => ['kind' => 'exact_value', 'value' => '4', 'tolerance' => '0', 'unit' => null]]]]],
                'updated_by' => $this->schoolB->ids['tara_user']]);

            return ['version' => $version->id, 'memo' => $memo->id];
        });
        $raw = fn () => (array) DB::table('past_paper_memos')->where('id', $other['memo'])->first();
        $before = $raw();
        config(['marking.learner_marking.mathematics' => true, 'marking.learner_marking.generic' => true, 'marking.learner_marking.physical_sciences' => true]);
        $this->refuses(fn () => app(PaperMarking::class)->preview($this->actor(), $this->group, $other['version'], $this->answer('1.1', 'x = 4')), 404);
        $this->refuses(fn () => app(PaperMarking::class)->page($this->actor(), $this->group, $other['version']), 404);
        $this->refuses(fn () => app(PaperMarking::class)->learner($this->actor('asha'), $this->group, $other['version'], $this->answer('1.1', 'x = 4')), 404);
        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->get($this->path('/marking', $other['version']))->assertNotFound();
        $this->post($this->path('/marking', $other['version']), $this->answer('1.1', 'x = 4'))->assertNotFound()->assertDontSee('BAOBAB-SECRET');
        $this->assertEquals($before, $raw());
        $this->assertSame(0, PastPaperMemo::count(), 'nothing written in School A either');
    }

    public function test_learner_marking_is_off_until_the_subject_passes_the_accuracy_gate(): void
    {
        $this->memo();
        app(PastPaperMemos::class)->review($this->actor(), $this->group, $this->paper['version_id'], ['expected_revision' => 1, 'confirmed' => true]);
        $engine = $this->mock(MarkingEngine::class);
        $engine->shouldNotReceive('mark');
        $this->assertNull(app(PaperMarking::class)->learner($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer()),
            'off by default: no marking at all, not even run and hidden');
    }

    public function test_learner_marking_once_switched_on_uses_only_the_reviewed_memo_and_hides_its_words(): void
    {
        config(['marking.learner_marking.physical_sciences' => true]);
        $this->memo();
        $marking = app(PaperMarking::class);
        $this->assertNull($marking->learner($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer()), 'a draft memo is never used for learners');
        app(PastPaperMemos::class)->review($this->actor(), $this->group, $this->paper['version_id'], ['expected_revision' => 1, 'confirmed' => true]);
        $result = $marking->learner($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer());
        $this->assertSame([300, 'complete'], [$result['awarded_units'], $result['status']]);
        $this->assertStringNotContainsString('SECRET-MEMO-POINT', json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('earns', $result['points'][0]);
        $this->assertArrayNotHasKey('rule', $result['points'][0]);
        $this->refuses(fn () => $marking->learner($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer('9.9', 'x')), 404);
        // Wrong owner and wrong role for the learner call: another learner outside the group, a parent, a teacher.
        $ben = $this->actor('ben');
        $ben->forceFill(['password_change_required' => false])->save();
        foreach (['another learner outside the group' => $ben, 'a parent' => $this->actor('farid'), 'a teacher' => $this->actor()] as $who => $user) {
            try {
                $marking->learner($user, $this->group, $this->paper['version_id'], $this->answer());
                $this->fail('Refuse '.$who);
            } catch (HttpExceptionInterface $error) {
                $this->assertContains($error->getStatusCode(), [403, 404], $who);
            } catch (ModelNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
        config(['marking.learner_marking.physical_sciences' => false]);
        $this->assertNull($marking->learner($this->actor('asha'), $this->group, $this->paper['version_id'], $this->answer()));
    }
}
