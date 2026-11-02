<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\GradeLevel;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperMemo;
use App\Models\Learning\PastPaperVersion;
use App\Models\Platform\SchoolModule;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Learning\PastPaperLibrary;
use App\Services\Learning\PastPaperMemos;
use App\Services\Panel\Navigation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * Structured memos (Action Plan 3.15): mark points per question, a reviewed
 * state, and only reviewed memos usable downstream.
 * engineering/specs/ai/structured-memos.md.
 */
class StructuredMemosTest extends TestCase
{
    use UsesPracticeScenario;

    private array $paper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'exam_coach'], ['enabled' => true, 'in_package' => true]);
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
                ['number' => '1.1', 'prompt' => 'A car travels 100 m in 8 s. Find its speed.', 'memo' => 'v = d/t (method), 12.5 m/s (answer with units).', 'max_units' => 300, 'topic_ids' => $topic],
                ['number' => '1.2', 'prompt' => 'Explain why speed is a scalar.', 'memo' => 'It has magnitude only.', 'max_units' => 200, 'topic_ids' => $topic],
            ]]);

        return $library->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => $draft['revision']]);
    }

    private function point(string $earns, string $type, string $marks, string $rule = 'none', ?string $value = null, ?string $tolerance = null, ?string $unit = null): array
    {
        return ['earns' => $earns, 'type' => $type, 'marks' => $marks, 'rule_kind' => $rule, 'rule_value' => $value, 'rule_tolerance' => $tolerance, 'rule_unit' => $unit];
    }

    private function input(int $revision = 0, ?array $first = null, ?array $second = null): array
    {
        return ['expected_revision' => $revision, 'questions' => [
            ['number' => '1.1', 'points' => $first ?? [
                $this->point('Uses v = d / t', 'method', '1'),
                $this->point('SECRET-MARK-POINT 12.5', 'answer', '1', 'exact_value', '12.5', '0.05', 'm/s'),
                $this->point('Gives the unit m/s', 'accuracy', '1', 'units_required', null, null, 'm/s'),
            ]],
            ['number' => '1.2', 'points' => $second ?? [
                $this->point('Magnitude only', 'explanation', '1.5'),
                $this->point('No direction', 'consistent_accuracy', '0.5'),
            ]],
        ]];
    }

    private function save(?User $actor = null, array $input = [], ?string $version = null): array
    {
        return app(PastPaperMemos::class)->save($actor ?? $this->actor(), $this->group, $version ?? $this->paper['version_id'], $input === [] ? $this->input() : $input);
    }

    private function review(?User $actor = null, int $revision = 1): array
    {
        return app(PastPaperMemos::class)->review($actor ?? $this->actor(), $this->group, $this->paper['version_id'], ['expected_revision' => $revision, 'confirmed' => true]);
    }

    private function path(string $suffix = '', ?string $version = null): string
    {
        return '/exam-memos/groups/'.$this->group.'/versions/'.($version ?? $this->paper['version_id']).$suffix;
    }

    private function reader(): User
    {
        $reader = $this->actor('theo');
        $reader->forceFill(['password_change_required' => false])->save();
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $reader->id)->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic additional reader'], $this->group);

        return $reader;
    }

    private function validationFails(callable $call, string $key): void
    {
        try {
            $call();
            $this->fail('Expected the mark points to be refused.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($key, $error->errors());
        }
    }

    public function test_teacher_saves_mark_points_reviews_the_memo_and_only_then_it_is_usable(): void
    {
        $memos = app(PastPaperMemos::class);
        $this->assertSame(['version_id' => $this->paper['version_id'], 'state' => 'draft', 'revision' => 1], array_diff_key($this->save(), ['id' => true]));
        $this->assertSame(2, $this->save($this->actor(), $this->input(1))['revision']);
        $memo = PastPaperMemo::sole();
        $this->assertSame([2, 'draft', (int) $this->actor()->id], [(int) $memo->revision, $memo->state, (int) $memo->updated_by]);
        $this->assertStringNotContainsString('SECRET-MARK-POINT', $memo->getRawOriginal('mark_points'), 'mark points are encrypted like the memo');
        $this->assertSame(['kind' => 'exact_value', 'value' => '12.5', 'tolerance' => '0.05', 'unit' => 'm/s'], $memo->mark_points[0]['points'][1]['rule']);
        $this->assertSame([100, 100, 100], array_column($memo->mark_points[0]['points'], 'mark_units'));
        $this->assertNull($memos->reviewed($this->actor(), $this->group, $this->paper['version_id'], true), 'a draft memo is not usable downstream');

        $this->assertSame('reviewed', $this->review(revision: 2)['state']);
        $usable = $memos->reviewed($this->actor(), $this->group, $this->paper['version_id'], true);
        $this->assertSame([['1.1', 300, 3], ['1.2', 200, 2]], array_map(fn ($q) => [$q['number'], $q['max_units'], count($q['points'])], $usable['questions']));
        $this->assertSame(['1.1', '1.2'], array_column($memos->reviewed($this->actor('asha'), $this->group, $this->paper['version_id'])['questions'], 'number'), 'marking a learner answer reads the reviewed points on the server');
        $this->assertSame((int) $this->actor()->id, (int) PastPaperMemo::sole()->reviewed_by);

        $this->save($this->actor(), $this->input(3));
        $this->assertSame(['draft', null], [PastPaperMemo::sole()->state, PastPaperMemo::sole()->reviewed_by], 'changing a reviewed memo needs a fresh review');
        $this->assertNull($memos->reviewed($this->actor(), $this->group, $this->paper['version_id'], true));
    }

    public function test_mark_points_must_add_up_to_the_question_total_in_the_service_and_the_form(): void
    {
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, [$this->point('Uses v = d / t', 'method', '2')])), 'questions.0.points');
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, null, [$this->point('Magnitude only', 'explanation', '3')])), 'questions.1.points');
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, [$this->point('Nothing', 'method', '0'), $this->point('All', 'answer', '3')])), 'questions.0.points.0.marks');
        $this->assertSame(0, PastPaperMemo::count());

        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->post($this->path(), $this->input(0, [$this->point('Uses v = d / t', 'method', '2.5')]))->assertSessionHasErrors('questions.0.points');
        $this->assertSame(0, PastPaperMemo::count(), 'the form refuses before the service is reached');
    }

    public function test_each_rule_needs_its_own_values_and_the_paper_questions_must_match(): void
    {
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, [$this->point('Answer', 'answer', '3', 'exact_value')])), 'questions.0.points.0.rule_kind');
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, [$this->point('Unit', 'accuracy', '3', 'units_required', '4')])), 'questions.0.points.0.rule_kind');
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, [$this->point('Unit', 'accuracy', '3', 'none', null, null, 'm')])), 'questions.0.points.0.rule_kind');
        $this->validationFails(fn () => $this->save($this->actor(), $this->input(0, [$this->point('Answer', 'guess', '3')])), 'questions.0.points.0.type');
        $input = $this->input();
        $input['questions'][1]['number'] = '9';
        $this->validationFails(fn () => $this->save($this->actor(), $input), 'questions');
        $this->assertSame(0, PastPaperMemo::count());
    }

    public function test_a_memo_is_reviewed_only_when_every_question_is_complete_and_revisions_match(): void
    {
        $this->save($this->actor(), $this->input(0, null, []));
        $this->validationFails(fn () => $this->review(), 'memo');
        $this->refuses(fn () => $this->review(revision: 7), 409);
        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->post($this->path('/review'), ['expected_revision' => 1, 'confirmed' => '1'])->assertSessionHasErrors('memo');
        $this->assertSame(['draft', 1], [PastPaperMemo::sole()->state, (int) PastPaperMemo::sole()->revision]);
        $this->refuses(fn () => $this->save($this->actor(), $this->input(0)), 409);
        $this->assertSame(1, (int) PastPaperMemo::sole()->revision, 'a stale save changes nothing');
    }

    public function test_wrong_role_learner_and_parent_cannot_change_or_review_a_memo(): void
    {
        $this->save();
        $before = PastPaperMemo::sole()->only(['state', 'revision', 'updated_at']);
        foreach (['asha', 'farid'] as $name) {
            $this->refuses(fn () => $this->save($this->actor($name), $this->input(1)), 403);
            $this->refuses(fn () => $this->review($this->actor($name)), 403);
            $this->actingAs($this->actor($name), 'web')->withSession(['school_id' => 1]);
            if ($name === 'asha') {
                $this->post($this->path(), $this->input(1))->assertForbidden();
                $this->post($this->path('/review'), ['expected_revision' => 1, 'confirmed' => '1'])->assertForbidden();
                $this->get($this->path())->assertForbidden();
            } else {
                // A parent account is not a panel account: the panel sends it to sign in.
                $this->post($this->path(), $this->input(1))->assertRedirect('/login');
                $this->get($this->path())->assertRedirect('/login');
            }
        }
        $this->assertSame(1, PastPaperMemo::count());
        $this->assertEquals($before, PastPaperMemo::sole()->only(['state', 'revision', 'updated_at']));
    }

    public function test_wrong_owner_another_teacher_of_the_group_reads_but_cannot_change_the_memo(): void
    {
        $this->save();
        $reader = $this->reader();
        $view = app(PastPaperMemos::class)->view($reader, $this->group, $this->paper['version_id']);
        $this->assertFalse($view['can_edit']);
        $this->assertSame(3, count($view['questions'][0]['points']));
        $this->refuses(fn () => $this->save($reader, $this->input(1)), 403);
        $this->refuses(fn () => $this->review($reader), 403);
        $this->actingAs($reader, 'web')->withSession(['school_id' => 1]);
        $this->get($this->path())->assertOk()->assertSee('Uses v = d / t')->assertDontSee('Save the mark points');
        $this->post($this->path('/review'), ['expected_revision' => 1, 'confirmed' => '1'])->assertForbidden();
        $this->assertSame(['draft', 1], [PastPaperMemo::sole()->state, (int) PastPaperMemo::sole()->revision]);
    }

    public function test_wrong_school_version_ids_are_not_found_and_school_b_memo_is_unchanged(): void
    {
        $other = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            $paper = PastPaper::create(['subject_id' => $this->schoolB->ids['maths'], 'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'),
                'framework' => 'school', 'created_by' => $this->schoolB->ids['tara_user']]);
            $version = PastPaperVersion::create(['id' => (string) Str::uuid(), 'past_paper_id' => $paper->id, 'number' => 1, 'paper_year' => 2025, 'paper_code' => 'Baobab P1',
                'group_ids' => [$this->group], 'created_by' => $this->schoolB->ids['tara_user'], 'fingerprint' => str_repeat('c', 64),
                'definition' => ['title' => 'Baobab paper', 'licence' => ['kind' => 'school_owned', 'confirmed' => true], 'questions' => $this->paperQuestions()]]);
            $memo = PastPaperMemo::create(['past_paper_version_id' => $version->id, 'mark_points' => [['number' => '1.1', 'points' => []]], 'updated_by' => $this->schoolB->ids['tara_user']]);

            return ['version' => $version->id, 'memo' => $memo->id];
        });
        $raw = fn () => (array) DB::table('past_paper_memos')->where('id', $other['memo'])->first();
        $before = $raw();
        $this->refuses(fn () => $this->save($this->actor(), $this->input(1), $other['version']), 404);
        $this->refuses(fn () => app(PastPaperMemos::class)->view($this->actor(), $this->group, $other['version']), 404);
        $this->refuses(fn () => app(PastPaperMemos::class)->reviewed($this->actor(), $this->group, $other['version'], true), 404);
        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->get($this->path('', $other['version']))->assertNotFound();
        $this->post($this->path('', $other['version']), $this->input(1))->assertNotFound();
        $this->post($this->path('/review', $other['version']), ['expected_revision' => 1, 'confirmed' => '1'])->assertNotFound();
        $this->assertEquals($before, $raw());
        $this->assertSame(0, PastPaperMemo::count(), 'nothing written in School A either');
    }

    private function paperQuestions(): array
    {
        return [['number' => '1.1', 'prompt' => 'Baobab prompt', 'memo' => 'Baobab memo', 'max_units' => 300, 'topic_ids' => [1]]];
    }

    public function test_the_licence_attestation_and_a_withdrawn_paper_stop_memo_changes(): void
    {
        $this->save();
        PastPaperVersion::findOrFail($this->paper['version_id'])->update(['licence_expires_on' => '2026-02-09']);
        $this->refuses(fn () => $this->save($this->actor(), $this->input(1)), 409);
        $this->refuses(fn () => app(PastPaperMemos::class)->reviewed($this->actor(), $this->group, $this->paper['version_id'], true), 409);
        PastPaperVersion::findOrFail($this->paper['version_id'])->update(['licence_expires_on' => '2026-12-31']);
        app(PastPaperLibrary::class)->withdraw($this->actor(), $this->group, $this->paper['id'], ['operation_id' => (string) Str::uuid(),
            'expected_revision' => $this->paper['revision'], 'reason' => 'Synthetic rights withdrawal']);
        $this->refuses(fn () => $this->save($this->actor(), $this->input(1)), 409);
        $this->refuses(fn () => $this->review(), 409);
        $this->assertSame(['draft', 1], [PastPaperMemo::sole()->state, (int) PastPaperMemo::sole()->revision]);
    }

    public function test_panel_lists_papers_saves_rows_and_reviews_the_memo(): void
    {
        $item = collect(app(Navigation::class)->definitions())->firstWhere('key', 'exam-memos');
        $this->assertTrue(app(Navigation::class)->visible($item, $this->actor()));
        $this->assertFalse(app(Navigation::class)->visible($item, $this->actor('asha')));
        $this->actingAs($this->actor(), 'web')->withSession(['school_id' => 1]);
        $this->get('/exam-memos')->assertOk()->assertSee('Synthetic licensed motion paper')->assertSee('No mark points yet')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get($this->path())->assertOk()->assertSee('A car travels 100 m in 8 s.')->assertSee('Save the mark points')->assertDontSee('Mark the memo as reviewed');

        $input = $this->input();
        $input['questions'][0]['points'][] = $this->point('', 'method', '');
        $input['questions'][1]['points'][] = ['earns' => ' ', 'type' => 'method', 'marks' => '', 'rule_kind' => 'none', 'rule_value' => '', 'rule_tolerance' => '', 'rule_unit' => ''];
        $this->post($this->path(), $input)->assertRedirect($this->path())->assertSessionHasNoErrors();
        $this->assertSame([3, 2], array_map(fn ($q) => count($q['points']), PastPaperMemo::sole()->mark_points), 'blank rows are not mark points');

        $this->get($this->path())->assertOk()->assertSee('Mark the memo as reviewed');
        $this->post($this->path('/review'), ['expected_revision' => 1])->assertSessionHasErrors('confirmed');
        $this->post($this->path('/review'), ['expected_revision' => 1, 'confirmed' => '1'])->assertRedirect($this->path());
        $this->assertSame('reviewed', PastPaperMemo::sole()->state);
        $this->get('/exam-memos')->assertOk()->assertSee('Reviewed');
        $this->post($this->path(), $this->input(1))->assertRedirect($this->path())->assertSessionHasErrors('memo');
        $this->assertSame(['reviewed', 2], [PastPaperMemo::sole()->state, (int) PastPaperMemo::sole()->revision], 'a stale form changes nothing');
    }
}
