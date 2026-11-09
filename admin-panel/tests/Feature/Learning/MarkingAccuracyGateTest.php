<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Learning\Marking\MarkingEvaluationPaper;
use App\Models\Learning\Marking\MarkingEvaluationRun;
use App\Models\Learning\Marking\MarkingEvaluationScript;
use App\Models\Learning\Marking\MarkingSubjectGate;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Learning\Marking\Accuracy\AccuracyEvaluation;
use App\Services\Learning\Marking\Accuracy\EvaluationImports;
use App\Services\Learning\Marking\Accuracy\MarkingGate;
use App\Services\Learning\Marking\Accuracy\MarkingVersions;
use App\Services\Learning\Marking\Profiles\MathematicsProfile;
use App\Services\Learning\Marking\SubjectProfiles;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\AssertsSameJson;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The marking accuracy gate (Action Plan 3.18): HQ imports de-identified,
 * teacher-marked scripts, runs the engine over them, and an approver switches
 * a subject's learner marking on only against the latest passing run on the
 * current engine and profile versions. Every script here is synthetic.
 * engineering/specs/learning/marking-accuracy-gate.md.
 */
class MarkingAccuracyGateTest extends TestCase
{
    use AssertsSameJson, PassesPlatformTwoFactor, UsesTwoSchools;

    private array $staff = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00:00', 'Africa/Johannesburg'));
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        // Small synthetic sets: the minimum is lowered here and its default checked on its own.
        config(['marking.gate.min_scripts' => 20]);
    }

    private function hqStaff(string $role): User
    {
        return $this->staff[$role] ??= $this->asHq(function () use ($role) {
            $user = User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Marker', 'email' => $role.'@accuracy.example', 'mobile' => '',
                'password' => 'Synthetic-accuracy-password-318!', 'status' => 1]);
            PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
            PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHours(4)]);

            return $user;
        });
    }

    private function asHq(callable $callback): mixed
    {
        return app(CurrentSchool::class)->acrossSchools($callback);
    }

    private function paper(string $code = 'synthetic-algebra-1', array $override = []): array
    {
        $point = fn (string $earns, string $type, string $marks, string $rule, ?string $value) => ['earns' => $earns, 'type' => $type, 'marks' => $marks,
            'rule_kind' => $rule, 'rule_value' => $value, 'rule_tolerance' => null, 'rule_unit' => null];

        return $override + ['subject' => 'mathematics', 'code' => $code, 'title' => 'Synthetic algebra paper', 'source' => 'Synthetic test data written for this test',
            'questions' => [
                ['number' => '1.1', 'max_marks' => '1', 'points' => [$point('x = 4', 'answer', '1', 'exact_value', '4')]],
                ['number' => '1.2', 'max_marks' => '2', 'points' => [$point('Gives 2x + 6', 'answer', '2', 'expression', '2x + 6')]],
                ['number' => '1.3', 'max_marks' => '1', 'points' => [$point('Explains why the answer is reasonable', 'explanation', '1', 'none', null)]],
            ]];
    }

    private function importPaper(?array $paper = null): MarkingEvaluationPaper
    {
        return $this->asHq(function () use ($paper) {
            $report = app(EvaluationImports::class)->paper($this->hqStaff('content'), json_encode($paper ?? $this->paper(), JSON_THROW_ON_ERROR), false);
            $this->assertTrue($report['ok'], implode(' ', $report['errors']));

            return MarkingEvaluationPaper::query()->findOrFail($report['summary']['id']);
        });
    }

    /**
     * Synthetic scripts: each answers 1.1 and 1.2. $agree scripts where the teacher marks as the
     * engine would; $teacherKinder scripts where the learner wrote x = 5 and the teacher still gave the mark.
     */
    private function scripts(int $agree, int $teacherKinder = 0, int $offset = 0, bool $explanation = false): array
    {
        $scripts = [];
        for ($i = 0; $i < $agree + $teacherKinder; $i++) {
            $right = $i % 2 === 0;
            $kinder = $i >= $agree;
            $questions = [
                ['number' => '1.1', 'answer' => $right && ! $kinder ? "2x = 8\nx = 4" : 'x = 5', 'marks' => [$right || $kinder ? '1' : '0']],
                ['number' => '1.2', 'answer' => $right ? '2x + 6' : '2x + 3', 'marks' => [$right ? '2' : '0']],
            ];
            if ($explanation) {
                $questions[] = ['number' => '1.3', 'answer' => 'Substituting x = 4 gives 11.', 'marks' => ['1']];
            }
            $scripts[] = ['reference' => sprintf('SYN-%04d', $offset + $i + 1), 'marker' => 'Marker A', 'source' => 'Synthetic moderation sample', 'questions' => $questions];
        }

        return ['scripts' => $scripts];
    }

    private function importScripts(MarkingEvaluationPaper $paper, array $scripts): array
    {
        return $this->asHq(fn () => app(EvaluationImports::class)->scripts($this->hqStaff('content'), $paper->id, 'json', json_encode($scripts, JSON_THROW_ON_ERROR), false));
    }

    private function evaluate(string $subject = 'mathematics'): MarkingEvaluationRun
    {
        return $this->asHq(fn () => app(AccuracyEvaluation::class)->run($this->hqStaff('content'), $subject));
    }

    private function file(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function csv(array $rows): string
    {
        $lines = ['reference,marker,source,question,answer,marks'];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row));
        }

        return implode("\n", $lines)."\n";
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected a refusal with '.$status);
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    private function gateCount(): array
    {
        return [MarkingEvaluationPaper::count(), MarkingEvaluationScript::count(), MarkingEvaluationRun::count(), MarkingSubjectGate::count()];
    }

    public function test_the_placeholders_are_the_plans_and_no_subject_is_on_by_default(): void
    {
        $defaults = require config_path('marking.php');
        $this->assertSame(200, $defaults['gate']['min_scripts'], 'the plan: at least 200 scripts per subject');
        $this->assertFalse($defaults['gate']['owner_confirmed'], 'the figures are placeholders until the owner decides');
        $this->assertSame(['mathematics', 'physical_sciences'], $defaults['gate']['subjects']);
        $this->assertArrayNotHasKey('learner_marking', $defaults, 'switching on is HQ\'s audited decision, not a config flag');
        foreach (['mathematics', 'physical_sciences', 'generic', 'life_sciences'] as $subject) {
            $this->assertFalse(app(MarkingGate::class)->open($subject), $subject);
        }
        $this->assertFalse((new MathematicsProfile)->learnerMarkingEnabled());
    }

    public function test_content_staff_import_a_paper_and_scripts_after_a_dry_run(): void
    {
        $this->signInAs($this->hqStaff('content'));
        $this->get('/saas/marking-accuracy')->assertOk()->assertSee('Marking accuracy')->assertSee('Placeholders')->assertSee('No evaluation papers yet.');

        $json = json_encode($this->paper(), JSON_THROW_ON_ERROR);
        $this->post('/saas/marking-accuracy/papers', ['file' => $this->file('paper.json', $json), 'dry_run' => '1'])->assertRedirect('/saas/marking-accuracy');
        $this->assertSame(0, MarkingEvaluationPaper::count(), 'a dry run saves nothing');
        $this->get('/saas/marking-accuracy')->assertSee('Dry run result')->assertSee('The paper synthetic-algebra-1 is ready to import: 3 questions, 3 mark points, 4.00 marks. Nothing was saved.');
        $this->post('/saas/marking-accuracy/papers', ['file' => $this->file('paper.json', $json), 'dry_run' => '0'])->assertRedirect();
        $paper = MarkingEvaluationPaper::sole();
        $this->assertSame(['mathematics', 400, 3], [$paper->subject, $paper->max_units, count($paper->questions)]);
        $this->assertSameJson(['kind' => 'exact_value', 'value' => '4', 'tolerance' => '0', 'unit' => null], $paper->questions[0]['points'][0]['rule']);
        $this->post('/saas/marking-accuracy/papers', ['file' => $this->file('paper.json', $json), 'dry_run' => '0']);
        $this->assertSame(1, MarkingEvaluationPaper::count(), 'papers do not change once imported');
        $this->get('/saas/marking-accuracy')->assertSee('A paper with this code already exists for the subject.');

        $csv = $this->csv([
            ['SYN-0001', 'Marker A', 'Synthetic moderation sample', '1.1', "2x = 8\nx = 4", '1'],
            ['SYN-0001', 'Marker A', 'Synthetic moderation sample', '1.2', '2x + 6', '2'],
            ['SYN-0002', 'Marker B', 'Synthetic moderation sample', '1.1', 'x = 5', '0'],
        ]);
        $this->post('/saas/marking-accuracy/scripts', ['paper_id' => $paper->id, 'format' => 'csv', 'file' => $this->file('scripts.csv', $csv), 'dry_run' => '1'])->assertRedirect();
        $this->assertSame(0, MarkingEvaluationScript::count());
        $this->get('/saas/marking-accuracy')->assertSee('2 scripts for synthetic-algebra-1 are ready to import. Nothing was saved.');
        $this->post('/saas/marking-accuracy/scripts', ['paper_id' => $paper->id, 'format' => 'csv', 'file' => $this->file('scripts.csv', $csv), 'dry_run' => '0'])->assertRedirect();
        $scripts = MarkingEvaluationScript::orderBy('id')->get();
        $this->assertSame([[300, 300, 'Marker A'], [0, 100, 'Marker B']], $scripts->map(fn ($s) => [$s->teacher_units, $s->max_units, $s->marker])->all());
        $this->assertSameJson([['number' => '1.1', 'answer' => "2x = 8\nx = 4", 'marks' => [100]], ['number' => '1.2', 'answer' => '2x + 6', 'marks' => [200]]], $scripts[0]->questions);
        // The source's own reference is never stored: only a keyed hash.
        foreach (DB::table('marking_evaluation_scripts')->get() as $row) {
            $this->assertStringNotContainsString('SYN-000', json_encode($row, JSON_THROW_ON_ERROR));
        }
        $this->assertSame(1, AuditLog::where('action', 'marking_evaluation_scripts_imported')->count());
        $this->assertSame(1, AuditLog::where('action', 'marking_evaluation_paper_imported')->count());
        // The same file again: every script was imported before, and nothing changes.
        $this->post('/saas/marking-accuracy/scripts', ['paper_id' => $paper->id, 'format' => 'csv', 'file' => $this->file('scripts.csv', $csv), 'dry_run' => '0']);
        $this->assertSame(2, MarkingEvaluationScript::count());
        $this->get('/saas/marking-accuracy')->assertSee('Script 1: this script was imported before.');
    }

    public function test_bad_scripts_are_refused_and_an_import_is_all_or_nothing(): void
    {
        $paper = $this->importPaper();
        $bad = function (array $scripts, string $message) use ($paper) {
            $report = $this->importScripts($paper, $scripts);
            $this->assertFalse($report['ok']);
            $this->assertTrue(collect($report['errors'])->contains(fn ($e) => str_contains($e, $message)), $message.' in '.implode(' | ', $report['errors']));
        };
        $good = $this->scripts(2)['scripts'][0];
        $bad(['scripts' => [$good, ['questions' => [['number' => '9.9', 'answer' => 'x', 'marks' => ['1']]]] + $good]], 'the paper has no such question');
        $bad(['scripts' => [['questions' => [['number' => '1.1', 'answer' => 'x = 4', 'marks' => ['1', '0']]]] + $good]], 'give the teacher\'s mark for each of the 1 mark points');
        $bad(['scripts' => [['questions' => [['number' => '1.1', 'answer' => 'x = 4', 'marks' => ['2']]]] + $good]], 'mark point 1: give a mark from 0 to 1.00');
        $bad(['scripts' => [['questions' => [['number' => '1.1', 'answer' => 'x = 4', 'marks' => ['1']], ['number' => '1.1', 'answer' => 'x', 'marks' => ['0']]]] + $good]], 'the question appears twice');
        $bad(['scripts' => [['total' => '2'] + $good]], 'the total does not match');
        $bad(['scripts' => [$good, $good]], 'this script appears twice in the file');
        $bad(['scripts' => [['reference' => 'has spaces in it'] + $good]], 'the reference is the source\'s own script code');
        $bad(['scripts' => [['questions' => [['number' => '1.1', 'answer' => str_repeat("x = 4\n", 41), 'marks' => ['1']]]] + $good]], 'use at most 40 lines');
        $bad(['scripts' => []], 'The file has no scripts.');
        $report = $this->asHq(fn () => app(EvaluationImports::class)->scripts($this->hqStaff('content'), $paper->id, 'json', '{not json', false));
        $this->assertSame(['The file is not valid JSON.'], $report['errors']);
        $report = $this->asHq(fn () => app(EvaluationImports::class)->scripts($this->hqStaff('content'), $paper->id, 'csv', "reference,marker,source,question,answer\n", false));
        $this->assertContains('The file needs a column "marks".', $report['errors']);
        $this->assertSame(0, MarkingEvaluationScript::count(), 'one bad script and nothing is imported');
        $this->assertSame(0, AuditLog::where('action', 'marking_evaluation_scripts_imported')->count());

        // A bad paper: a memo rule the editor would refuse, points that do not add up, an unknown subject.
        foreach ([
            [['code' => 'bad-rule', 'questions' => [['number' => '1', 'max_marks' => '1', 'points' => [['earns' => 'Gives x', 'type' => 'answer', 'marks' => '1', 'rule_kind' => 'exact_value', 'rule_value' => 'four']]]]], 'an exact value is a number'],
            [['code' => 'bad-sum', 'questions' => [['number' => '1', 'max_marks' => '3', 'points' => [['earns' => 'Gives x', 'type' => 'answer', 'marks' => '1', 'rule_kind' => 'none']]]]], 'add up to 1.00'],
            [['code' => 'bad-subject', 'subject' => 'generic'], 'Choose a subject the accuracy gate covers'],
        ] as [$override, $message]) {
            $report = $this->asHq(fn () => app(EvaluationImports::class)->paper($this->hqStaff('content'), json_encode($override + $this->paper(), JSON_THROW_ON_ERROR), false));
            $this->assertFalse($report['ok']);
            $this->assertTrue(collect($report['errors'])->contains(fn ($e) => str_contains($e, $message)), $message.' in '.implode(' | ', $report['errors']));
        }
        $this->assertSame(1, MarkingEvaluationPaper::count());
    }

    public function test_no_identifying_data_is_accepted(): void
    {
        $paper = $this->importPaper();
        $good = $this->scripts(1)['scripts'][0];
        $refused = function (string $format, string $content, string $message) use ($paper) {
            $report = $this->asHq(fn () => app(EvaluationImports::class)->scripts($this->hqStaff('content'), $paper->id, $format, $content, true));
            $this->assertFalse($report['ok'], $message);
            $this->assertTrue(collect($report['errors'])->contains(fn ($e) => str_contains($e, $message)), $message.' in '.implode(' | ', $report['errors']));
        };
        // Identifying fields refuse the whole file, in JSON at any level and as CSV columns.
        $refused('json', json_encode(['scripts' => [$good + ['learner_name' => 'Synthetic Learner']]]), 'the field "learner_name" identifies a person or a school');
        $refused('json', json_encode(['scripts' => [['questions' => [$good['questions'][0] + ['student_number' => 'A123']]] + $good]]), 'the field "student_number" identifies');
        $refused('json', json_encode(['school' => 'Synthetic School', 'scripts' => [$good]]), 'the field "school" identifies');
        $refused('json', json_encode(['scripts' => [$good + ['notes' => 'x']]]), 'the field "notes" is not part of the format');
        $refused('csv', "reference,marker,source,question,answer,marks,learner_name\nSYN-1,Marker A,Synthetic sample,1.1,x = 4,1,Synthetic Learner\n", 'the field "learner_name" identifies');
        $refused('csv', "reference,marker,source,question,answer,marks,email\nSYN-1,Marker A,Synthetic sample,1.1,x = 4,1,a@b.example\n", 'the field "email" identifies');
        // Identifying data written into the text refuses the script.
        foreach ([
            ['answer', 'x = 4 learner@school.example', 'an e-mail address'],
            ['answer', "x = 4\nID 9002021234080", 'an identity number'],
            ['answer', 'x = 4, call 082 555 0101', 'a phone number'],
            ['reference', '9002021234080', 'an identity number'],
            ['marker', 'Marker 0825550101', 'a phone number'],
            ['source', 'Sent by teacher@school.example', 'an e-mail address'],
        ] as [$field, $value, $what]) {
            $script = $good;
            if ($field === 'answer') {
                $script['questions'][0]['answer'] = $value;
            } else {
                $script[$field] = $value;
            }
            $refused('json', json_encode(['scripts' => [$script]]), 'contains '.$what);
        }
        // Ordinary maths is not mistaken for a phone or identity number.
        $script = $good;
        $script['questions'][0]['answer'] = "x = 4\n3 × 10^8 = 300000000\n0,0123456789";
        $report = $this->importScripts($paper, ['scripts' => [$script]]);
        $this->assertTrue($report['ok'], implode(' ', $report['errors']));
        $this->assertSame(1, MarkingEvaluationScript::count());

        // The tables have no column that could point at a learner, a school or a school record.
        foreach (['marking_evaluation_papers', 'marking_evaluation_scripts', 'marking_evaluation_runs', 'marking_subject_gates'] as $table) {
            $columns = Schema::getColumnListing($table);
            $this->assertSame([], array_values(array_filter($columns, fn ($c) => preg_match('/school|learner|student|guardian|name|email|phone|admission|lurits|class/', $c))), $table);
            $this->assertArrayHasKey($table, SchoolOwnedTables::GLOBAL, $table.' is platform-owned');
        }
    }

    public function test_a_run_passes_only_with_enough_scripts_that_agree(): void
    {
        $paper = $this->importPaper();
        $this->importScripts($paper, $this->scripts(19));
        $run = $this->evaluate();
        $this->assertFalse($run->passed);
        $this->assertSame(['too_few_scripts'], $run->failures, 'perfect agreement on 19 scripts is still below the minimum of 20');
        $this->importScripts($paper, $this->scripts(1, 0, 19));
        $run = $this->evaluate();
        $this->assertTrue($run->passed, json_encode($run->failures));
        $this->assertSame([20, 40, (new MathematicsProfile)->version(), 'rules-v1'], [$run->script_count, $run->point_count, $run->profile_version, $run->engine_version]);
        // Stored as JSON, so whole numbers come back as integers: compared by value.
        $this->assertEquals(['count' => 40, 'exact_percent' => 100.0, 'within_percent' => 100.0, 'needs_teacher_percent' => 0.0, 'mean_abs_marks' => 0.0, 'bias_marks' => 0.0], $run->metrics['points']);
        $this->assertEquals([100.0, 100.0, 0.0, 0.0, 0.0], [$run->metrics['scripts']['exact_percent'], $run->metrics['scripts']['within_percent'],
            $run->metrics['scripts']['mean_abs_marks'], $run->metrics['scripts']['bias_marks'], $run->metrics['scripts']['bias_percent']]);
        $this->assertSame(['answer'], array_keys($run->metrics['by_type']));
        $this->assertSame(['exact_value', 'expression'], array_keys($run->metrics['by_rule']));
        $this->assertEquals(['min_scripts' => 20, 'point_marks' => 0.0, 'script_percent' => 3.0, 'point_agreement_percent' => 95.0, 'script_agreement_percent' => 90.0,
            'max_bias_percent' => 1.0, 'owner_confirmed' => false], $run->settings);
        $this->assertSame(2, AuditLog::where('action', 'marking_evaluation_run')->count());

        config(['marking.gate.min_scripts' => 21]);
        $this->assertSame(['too_few_scripts'], $this->evaluate()->failures, 'the minimum is configurable');
    }

    public function test_a_run_below_the_threshold_fails_and_shows_the_worst_mark_points(): void
    {
        $paper = $this->importPaper();
        // 20 agreeing scripts and 5 where the teacher gave the mark for x = 5 and the engine did not.
        $this->importScripts($paper, $this->scripts(20, 5));
        $run = $this->evaluate();
        $this->assertFalse($run->passed);
        $this->assertSame(['point_agreement', 'script_agreement', 'bias'], $run->failures);
        $this->assertEquals(90.0, $run->metrics['points']['within_percent'], '45 of 50 mark points');
        $this->assertEquals(80.0, $run->metrics['scripts']['within_percent'], '20 of 25 scripts');
        $this->assertEquals(-0.2, $run->metrics['scripts']['bias_marks'], 'the engine is lower: 5 marks over 25 scripts');
        $this->assertEquals(0.2, $run->metrics['scripts']['mean_abs_marks']);
        $worst = $run->point_stats[0];
        $this->assertSame(['synthetic-algebra-1', '1.1', 1, 'answer', 'exact_value', 25, 20, 0, 5, 0], [$worst['paper'], $worst['question'], $worst['index'], $worst['type'],
            $worst['rule'], $worst['count'], $worst['within'], $worst['over'], $worst['under'], $worst['pending']]);
        $this->assertNotNull($worst['reason']);

        // A wider tolerance and lower thresholds (the owner's to decide) change the verdict for the same scripts.
        config(['marking.gate.tolerance.script_percent' => 34, 'marking.gate.thresholds.point_agreement_percent' => 90,
            'marking.gate.thresholds.script_agreement_percent' => 80, 'marking.gate.thresholds.max_bias_percent' => 10]);
        $this->assertTrue($this->evaluate()->passed);

        // A mark point the engine sends to a teacher counts as a disagreement.
        $other = $this->importPaper($this->paper('synthetic-algebra-2'));
        $this->importScripts($other, $this->scripts(20, 0, 100, true));
        $run = $this->evaluate();
        $this->assertEquals(100.0, $run->metrics['by_type']['explanation']['needs_teacher_percent']);
        $this->assertEquals(0.0, $run->metrics['by_type']['explanation']['within_percent']);
        $this->assertSame('explanation', $run->point_stats[0]['type'], 'the worst mark point first');

        $this->signInAs($this->hqStaff('content'));
        $this->get('/saas/marking-accuracy/runs/'.$run->id)->assertOk()->assertSee('Mark points that disagree most')->assertSee('Explains why the answer is reasonable')
            ->assertSee('By mark point type')->assertSee('Measured against placeholder figures the owner had not confirmed.');
        $this->get('/saas/marking-accuracy')->assertOk()->assertSee('synthetic-algebra-2')->assertSee('Learner marking off');
    }

    public function test_switching_on_needs_an_approver_a_passing_run_and_the_owners_confirmation_and_is_audited(): void
    {
        $paper = $this->importPaper();
        $this->importScripts($paper, $this->scripts(20));
        $run = $this->evaluate();
        $owner = $this->hqStaff('owner');
        $input = fn (array $extra = []) => $extra + ['run_id' => $run->id, 'expected_revision' => 0, 'reason' => 'Synthetic run reviewed with the teacher panel.', 'confirmed' => '1'];

        // Content staff evaluate but cannot switch on.
        $this->signInAs($this->hqStaff('content'));
        $this->get('/saas/marking-accuracy')->assertOk()->assertDontSee('I reviewed run');
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input())->assertForbidden();

        // The owner has not confirmed the placeholders yet.
        $this->signInAs($owner);
        $this->get('/saas/marking-accuracy')->assertOk()->assertSee('The owner has not yet confirmed the threshold, tolerance and minimum number of scripts.')->assertDontSee('I reviewed run');
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input())->assertSessionHasErrors('run_id');
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'));

        config(['marking.gate.owner_confirmed' => true]);
        $this->get('/saas/marking-accuracy')->assertOk()->assertSee('I reviewed run '.$run->id.' and its worst mark points.');
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input(['confirmed' => '0']))->assertSessionHasErrors('confirmed');
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input(['reason' => 'short']))->assertSessionHasErrors('reason');
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input(['expected_revision' => 3]))->assertSessionHasErrors('expected_revision');
        $this->put('/saas/marking-accuracy/subjects/generic/gate', $input())->assertNotFound();
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'));
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input())->assertRedirect('/saas/marking-accuracy')->assertSessionHasNoErrors();
        $this->assertTrue(app(MarkingGate::class)->open('mathematics'));
        $this->assertTrue(SubjectProfiles::key('mathematics')->learnerMarkingEnabled());
        $this->assertFalse(app(MarkingGate::class)->open('physical_sciences'), 'one subject at a time');
        $gate = MarkingSubjectGate::where('subject', 'mathematics')->sole();
        $this->assertSame([$run->id, 'rules-v1', 'mathematics-v1', $owner->id, 1], [$gate->marking_evaluation_run_id, $gate->engine_version, $gate->profile_version, $gate->approved_by, $gate->revision]);
        $audit = AuditLog::where('action', 'marking_subject_switched_on')->sole();
        $this->assertSame([$owner->id, $run->id, 'Synthetic run reviewed with the teacher panel.'], [$audit->actor_id, $audit->after['run_id'], $audit->after['reason']]);
        $this->get('/saas/marking-accuracy')->assertSee('Learner marking on');

        // A failing run: never with an older or failing run.
        $this->importScripts($paper, $this->scripts(0, 10, 500));
        $failed = $this->evaluate();
        $this->assertFalse($failed->passed);
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'), 'the latest run on the current versions failed, so the gate closes');
        $this->get('/saas/marking-accuracy')->assertSee('Switched on, but closed: the latest run did not pass');

        // Switching off: an evaluator may, with a reason, audited.
        $this->signInAs($this->hqStaff('content'));
        $this->delete('/saas/marking-accuracy/subjects/mathematics/gate', ['expected_revision' => 1, 'reason' => 'Synthetic: more scripts disagree now.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse(MarkingSubjectGate::where('subject', 'mathematics')->value('enabled'));
        $this->assertSame(1, AuditLog::where('action', 'marking_subject_switched_off')->count());
        $this->signInAs($owner);
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input(['expected_revision' => 2]))->assertSessionHasErrors('run_id');
        $this->put('/saas/marking-accuracy/subjects/mathematics/gate', $input(['expected_revision' => 2, 'run_id' => $failed->id]))->assertSessionHasErrors('run_id');
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'));
    }

    public function test_a_new_engine_or_profile_version_needs_a_new_passing_run(): void
    {
        config(['marking.gate.owner_confirmed' => true]);
        $paper = $this->importPaper();
        $this->importScripts($paper, $this->scripts(20));
        $owner = $this->hqStaff('owner');
        $switchOn = fn (int $revision) => $this->asHq(fn () => app(MarkingGate::class)->switchOn($owner, 'mathematics', ['run_id' => app(MarkingGate::class)->latestRun('mathematics')?->id ?? 0,
            'expected_revision' => $revision, 'reason' => 'Synthetic run reviewed with the teacher panel.', 'confirmed' => true]));
        $this->evaluate();
        $switchOn(0);
        $this->assertTrue(app(MarkingGate::class)->open('mathematics'));

        foreach ([['rules-v2', 'mathematics-v1'], ['rules-v1', 'mathematics-v2']] as $i => [$engine, $profile]) {
            $versions = $this->mock(MarkingVersions::class);
            $versions->shouldReceive('engine')->andReturn($engine);
            $versions->shouldReceive('profile')->andReturnUsing(fn (string $subject) => $subject === 'mathematics' ? $profile : SubjectProfiles::key($subject)->version());
            $this->assertFalse(app(MarkingGate::class)->open('mathematics'), $engine.' '.$profile.': the pass was for other versions');
            $this->assertFalse(SubjectProfiles::key('mathematics')->learnerMarkingEnabled());
            $state = app(MarkingGate::class)->state('mathematics');
            $this->assertTrue($state['stale']);
            $this->assertNull($state['eligible_run_id'], 'no run on the new versions yet');
            try {
                $switchOn(1 + $i);
                $this->fail('switching on without a run on the new versions');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
            $run = $this->evaluate();
            $this->assertSame([$engine, $profile, true], [$run->engine_version, $run->profile_version, $run->passed]);
            $this->assertFalse(app(MarkingGate::class)->open('mathematics'), 'a new passing run still needs an approver');
            $this->signInAs($owner);
            $this->get('/saas/marking-accuracy')->assertOk()->assertSee('Switched on, but closed: the versions changed')->assertSee('I reviewed run '.$run->id.' and its worst mark points.');
            $switchOn(1 + $i);
            $this->assertTrue(app(MarkingGate::class)->open('mathematics'));
        }
        $this->assertSame(3, AuditLog::where('action', 'marking_subject_switched_on')->count());
    }

    public function test_json_that_is_not_an_object_is_refused_and_saves_nothing(): void
    {
        $paper = $this->importPaper();
        $before = $this->gateCount();
        $this->signInAs($this->hqStaff('content'));
        foreach (['0', '1'] as $dryRun) {
            $this->post('/saas/marking-accuracy/papers', ['file' => $this->file('paper.json', 'null'), 'dry_run' => $dryRun])->assertRedirect('/saas/marking-accuracy');
            $this->get('/saas/marking-accuracy')->assertOk()->assertSee('Nothing was imported. Fix these problems and try again:')->assertSee('The file must be one JSON object.');
            $this->post('/saas/marking-accuracy/scripts', ['paper_id' => $paper->id, 'format' => 'json', 'file' => $this->file('scripts.json', 'null'), 'dry_run' => $dryRun])
                ->assertRedirect('/saas/marking-accuracy');
            $this->get('/saas/marking-accuracy')->assertOk()->assertSee('Nothing was imported. Fix these problems and try again:')->assertSee('The file must be one JSON object.');
        }
        foreach (['null', '42', '"a paper"', 'true', '[]', '{}', '[{"subject": "mathematics"}]'] as $content) {
            $report = $this->asHq(fn () => app(EvaluationImports::class)->paper($this->hqStaff('content'), $content, false));
            $this->assertFalse($report['ok'], 'paper '.$content);
            $this->assertNotSame([], $report['errors'], 'paper '.$content);
            $report = $this->asHq(fn () => app(EvaluationImports::class)->scripts($this->hqStaff('content'), $paper->id, 'json', $content, false));
            $this->assertFalse($report['ok'], 'scripts '.$content);
            $this->assertNotSame([], $report['errors'], 'scripts '.$content);
        }
        $this->assertSame($before, $this->gateCount(), 'nothing saved');
    }

    public function test_changed_acceptance_settings_close_the_gate_until_a_new_run_passes_and_is_approved(): void
    {
        config(['marking.gate.owner_confirmed' => true]);
        $paper = $this->importPaper();
        $this->importScripts($paper, $this->scripts(20));
        $owner = $this->hqStaff('owner');
        $switchOn = fn (int $runId, int $revision) => $this->asHq(fn () => app(MarkingGate::class)->switchOn($owner, 'mathematics', ['run_id' => $runId,
            'expected_revision' => $revision, 'reason' => 'Synthetic run reviewed with the teacher panel.', 'confirmed' => true]));
        $first = $this->evaluate();
        $switchOn($first->id, 0);
        $this->assertTrue(app(MarkingGate::class)->open('mathematics'));

        // A threshold change closes the open gate; the old passing run can no longer be approved.
        config(['marking.gate.thresholds.point_agreement_percent' => 96]);
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'), 'a changed threshold closes the gate');
        $state = app(MarkingGate::class)->state('mathematics');
        $this->assertTrue($state['settings_changed']);
        $this->assertNull($state['eligible_run_id']);
        $this->signInAs($owner);
        $this->get('/saas/marking-accuracy')->assertOk()->assertSee('Switched on, but closed: the acceptance settings changed')
            ->assertSee('The latest run was measured against other acceptance settings')->assertDontSee('I reviewed run');
        try {
            $switchOn($first->id, 1);
            $this->fail('an old passing run cannot be approved after the settings change');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        // A fresh run on the new settings can be approved, and only then does the gate reopen.
        $second = $this->evaluate();
        $this->assertTrue($second->passed);
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'), 'a new passing run still needs an approver');
        $this->get('/saas/marking-accuracy')->assertSee('I reviewed run '.$second->id.' and its worst mark points.');
        $switchOn($second->id, 1);
        $this->assertTrue(app(MarkingGate::class)->open('mathematics'));

        // Raising the minimum count closes it again; the old run is refused; a run on the new minimum that passes is approved.
        config(['marking.gate.min_scripts' => 21]);
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'), 'a changed minimum closes the gate');
        try {
            $switchOn($second->id, 2);
            $this->fail('a run measured on the old minimum cannot be approved');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        $this->assertSame(['too_few_scripts'], $this->evaluate()->failures, '20 scripts are below the new minimum');
        $this->importScripts($paper, $this->scripts(1, 0, 20));
        $third = $this->evaluate();
        $this->assertTrue($third->passed);
        $switchOn($third->id, 2);
        $this->assertTrue(app(MarkingGate::class)->open('mathematics'));
        $this->assertSame(3, AuditLog::where('action', 'marking_subject_switched_on')->count());
    }

    public function test_wrong_roles_and_school_staff_cannot_reach_the_gate(): void
    {
        $paper = $this->importPaper();
        $this->importScripts($paper, $this->scripts(20));
        $run = $this->evaluate();
        config(['marking.gate.owner_confirmed' => true]);
        $before = $this->gateCount();
        $requests = fn () => [
            $this->get('/saas/marking-accuracy'),
            $this->get('/saas/marking-accuracy/runs/'.$run->id),
            $this->post('/saas/marking-accuracy/papers', ['file' => $this->file('paper.json', json_encode($this->paper('other-code'))), 'dry_run' => '0']),
            $this->post('/saas/marking-accuracy/scripts', ['paper_id' => $paper->id, 'format' => 'json', 'file' => $this->file('s.json', json_encode($this->scripts(1, 0, 900))), 'dry_run' => '0']),
            $this->post('/saas/marking-accuracy/subjects/mathematics/runs'),
            $this->put('/saas/marking-accuracy/subjects/mathematics/gate', ['run_id' => $run->id, 'expected_revision' => 0, 'reason' => 'Trying to switch it on.', 'confirmed' => '1']),
            $this->delete('/saas/marking-accuracy/subjects/mathematics/gate', ['expected_revision' => 0, 'reason' => 'Trying to switch it off.']),
        ];
        foreach (['finance', 'support', 'reviewer', 'sales', 'operations'] as $role) {
            $this->signInAs($this->hqStaff($role));
            foreach ($requests() as $i => $response) {
                $this->assertSame(403, $response->getStatusCode(), $role.' request '.$i);
            }
        }
        // School staff of either school, even a Super Admin, never reach HQ.
        foreach ([1 => $this->schoolA, self::SCHOOL_B_ID => $this->schoolB] as $schoolId => $school) {
            $admin = app(CurrentSchool::class)->run($schoolId, function () use ($schoolId) {
                $user = User::query()->create(['first_name' => 'School', 'last_name' => 'Head '.$schoolId, 'email' => 'head'.$schoolId.'@accuracy.example', 'mobile' => '',
                    'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
                $user->assignRole('Super Admin');
                app(MembershipService::class)->grant($user, $schoolId, 'Super Admin');

                return $user;
            });
            $this->signInAs($admin);
            foreach ($requests() as $i => $response) {
                $this->assertContains($response->getStatusCode(), [302, 403], 'school '.$schoolId.' request '.$i);
            }
        }
        // A guest is sent to sign in.
        $this->newBrowserSession();
        app('auth')->forgetGuards();
        $this->get('/saas/marking-accuracy')->assertRedirect();
        $this->assertSame($before, $this->gateCount(), 'nothing changed');
        $this->assertFalse(app(MarkingGate::class)->open('mathematics'));
    }

    public function test_evaluation_data_is_never_read_or_written_from_inside_a_school(): void
    {
        $paper = $this->importPaper();
        $owner = $this->hqStaff('owner');
        foreach ([1, self::SCHOOL_B_ID] as $schoolId) {
            app(CurrentSchool::class)->run($schoolId, function () use ($paper, $owner) {
                // Even HQ staff, acting inside a school, are refused by every service.
                $this->refuses(fn () => app(EvaluationImports::class)->scripts($owner, $paper->id, 'json', json_encode($this->scripts(1)), false), 403);
                $this->refuses(fn () => app(AccuracyEvaluation::class)->run($owner, 'mathematics'), 403);
                $this->refuses(fn () => app(MarkingGate::class)->switchOn($owner, 'mathematics', []), 403);
                $this->refuses(fn () => app(MarkingGate::class)->switchOff($owner, 'mathematics', []), 403);
                // And the rows refuse any write while a school is current.
                $this->refuses(fn () => MarkingEvaluationScript::create(['marking_evaluation_paper_id' => $paper->id, 'reference_hash' => str_repeat('a', 64), 'marker' => 'Marker A',
                    'source' => 'Inside a school', 'questions' => [], 'teacher_units' => 0, 'max_units' => 100, 'import_batch' => str_repeat('b', 36), 'imported_by' => $owner->id]), 403);
                $this->refuses(fn () => MarkingSubjectGate::create(['subject' => 'mathematics', 'enabled' => true]), 403);
            });
        }
        $this->assertSame([1, 0, 0, 0], $this->gateCount());
    }

    public function test_the_engine_reaches_learners_only_through_the_gated_paper_marking(): void
    {
        $uses = [];
        foreach (File::allFiles(app_path()) as $file) {
            if (str_contains($file->getContents(), 'MarkingEngine::class') || str_contains($file->getContents(), 'MarkingEngine $')) {
                $uses[] = str_replace(app_path().'/', '', $file->getPathname());
            }
        }
        sort($uses);
        // PaperMarking::learner asks SubjectProfile::learnerMarkingEnabled (the gate) before marking; preview is the teacher's own memo check.
        $this->assertSame(['Services/Learning/Marking/Accuracy/AccuracyEvaluation.php', 'Services/Learning/Marking/PaperMarking.php'], $uses,
            'a new learner-facing use of the engine must check MarkingGate::open first, and be added here');
        $this->assertStringContainsString('learnerMarkingEnabled()', File::get(app_path('Services/Learning/Marking/PaperMarking.php')));
        // Practice tests (3.17) mark only choice and number questions; typed answers wait for a marker and never reach the engine.
        $this->assertStringNotContainsString('Marking\\MarkingEngine', File::get(app_path('Services/Learning/Tests/Marking/TestMarkers.php')));
    }
}
