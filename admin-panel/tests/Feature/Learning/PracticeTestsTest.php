<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\Tests\PracticeTest;
use App\Models\Learning\Tests\PracticeTestBlueprint;
use App\Models\Learning\Tests\PracticeTestQuestion;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Learning\PastPaperLibrary;
use App\Services\Learning\Tests\TestKinds;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Practice tests (Action Plan 3.17): teachers set cycle tests from their
 * question bank, learners sit quick quizzes and cycle tests against a
 * server-held deadline, and nothing about the answers reaches the device
 * before hand-in. Specification: engineering/specs/learning/practice-tests.md.
 */
class PracticeTestsTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    private const BASE = '/api/v2/learning/practice-tests';

    /** Worked explanations: none may reach the device before hand-in. */
    private const EXPLANATION = 'CANARY-EXPLANATION';

    /** @var array<string, list<int>> question item ids per topic name */
    private array $items = ['fractions' => [], 'decimals' => []];

    private int $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        $this->learner = $this->schoolA->ids['asha'];
        // Four fraction questions (numbers) and three decimal questions (choices).
        foreach (['0.5' => 'One half', '0.25' => 'One quarter', '0.75' => 'Three quarters', '0.2' => 'One fifth'] as $answer => $words) {
            $this->items['fractions'][] = $this->approve(['definition' => ['kind' => 'number', 'prompt' => "$words as a decimal?",
                'explanation' => self::EXPLANATION.' divide to get the decimal.', 'options' => [], 'answers' => [$answer]]])['id'];
        }
        foreach (['0.3', '0.7', '0.9'] as $bigger) {
            $this->items['decimals'][] = $this->approve(['topic_id' => $this->nodes[$this->keys[2]], 'definition' => ['kind' => 'choice',
                'prompt' => "Which is larger, $bigger or 0.25?", 'explanation' => self::EXPLANATION.' compare the tenths first.',
                'options' => [['id' => 'big', 'text' => $bigger], ['id' => 'small', 'text' => '0.25']], 'answers' => ['big']]])['id'];
        }
    }

    private function signIn(string $name, string $school = 'A'): void
    {
        $fixture = $school === 'A' ? $this->schoolA : $this->schoolB;
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($fixture->tokens[$name])->withHeaders(['X-School-Id' => $school === 'A' ? '1' : '2', 'X-Account-Id' => (string) $fixture->ids[$name.'_user']]);
    }

    /** An approved learner with a sign-in ready for the API, in the current school. */
    private function approveLearner(DemoSchool $school, string $learner, string $guardian): int
    {
        User::whereKey($school->ids[$learner.'_user'])->update(['password_change_required' => false]);
        User::whereKey($school->ids[$guardian.'_user'])->update(['email_verified_at' => now(), 'status' => 1]);
        GuardianLink::updateOrCreate(['learner_id' => $school->ids[$learner], 'guardian_user_id' => $school->ids[$guardian.'_user']],
            ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $school->ids[$learner], 'guardian_user_id' => $school->ids[$guardian.'_user'],
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);

        return (int) $school->ids[$learner];
    }

    private function fractions(): int
    {
        return $this->nodes[$this->keys[1]];
    }

    private function decimals(): int
    {
        return $this->nodes[$this->keys[2]];
    }

    private function cycleInput(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'title' => 'Fractions and decimals cycle test', 'topic_ids' => [$this->decimals(), $this->fractions()],
            'question_count' => 6, 'time_limit_minutes' => 20, 'opens_at' => now('UTC')->subHour()->toIso8601ZuluString(),
            'closes_at' => now('UTC')->addDays(2)->toIso8601ZuluString(), 'weighting' => 'even'];
    }

    private function setCycleTest(array $changes = []): array
    {
        $this->signIn('tara');

        return $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput($changes))->assertOk()->json('data');
    }

    private function correctAnswer(array $question): string
    {
        if ($question['kind'] === 'choice') {
            return 'big';
        }
        $prompts = ['One half' => '0.5', 'One quarter' => '.25', 'Three quarters' => '0.750', 'One fifth' => '0.2'];

        return $prompts[trim(explode(' as ', $question['prompt'])[0])];
    }

    private function assertNoAnswersOnDevice(array $sitting, string $raw): void
    {
        $this->assertStringNotContainsString(self::EXPLANATION, $raw);
        $this->assertStringNotContainsString('accepted_answers', $raw);
        $this->assertStringNotContainsString('"answers"', $raw);
        $this->assertStringNotContainsString('"correct"', $raw);
        foreach ($sitting['questions'] as $question) {
            $this->assertSame(['id', 'position', 'topic_id', 'kind', 'prompt', 'options', 'answer'], array_keys($question));
        }
    }

    public function test_a_teacher_sets_a_cycle_test_and_a_learner_sits_it_with_no_answers_on_the_device_until_it_closes(): void
    {
        $this->signIn('tara');
        $options = $this->getJson(self::BASE."/groups/{$this->group}/options")->assertOk()->json('data');
        $this->assertEquals([$this->nodes[$this->keys[0]] => 0, $this->fractions() => 4, $this->decimals() => 3], array_column($options['topics'], 'available', 'id'));
        $this->assertSame(['school'], array_values(array_unique(array_column($options['topics'], 'source'))));
        $blueprint = $this->setCycleTest();
        $this->assertEquals([$this->fractions() => 3, $this->decimals() => 3], array_column(PracticeTestBlueprint::findOrFail($blueprint['id'])->topic_shares, 'questions', 'topic_id'));
        $this->getJson(self::BASE."/groups/{$this->group}/blueprints")->assertOk()->assertJsonPath('data.tests.0.id', $blueprint['id'])->assertJsonPath('data.tests.0.handed_in', 0);

        $this->signIn('asha');
        $this->getJson(self::BASE."/learners/{$this->learner}/tests")->assertOk()->assertJsonPath('data.tests.0.blueprint_id', $blueprint['id'])
            ->assertJsonPath('data.tests.0.window', 'open')->assertJsonPath('data.tests.0.test', null);
        $start = ['operation_id' => (string) Str::uuid()];
        $response = $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", $start)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.state', 'open')->assertJsonPath('data.time_limit_seconds', 1200);
        $sitting = $response->json('data');
        $this->assertCount(6, $sitting['questions']);
        $this->assertSame(1200, $sitting['seconds_left']);
        $this->assertNoAnswersOnDevice($sitting, $response->getContent());
        // Starting again, with the same or a new operation, resumes the same sitting.
        $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", $start)->assertOk()->assertJsonPath('data.id', $sitting['id']);
        $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.id', $sitting['id']);

        $answers = array_map(fn ($q) => ['question_id' => $q['id'], 'answer' => $this->correctAnswer($q)], $sitting['questions']);
        $answers[0]['answer'] = $sitting['questions'][0]['kind'] === 'choice' ? 'small' : '0.1';
        $saved = $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}/answers", ['answers' => $answers])->assertOk();
        $this->assertSame(6, $saved->json('data.saved'));
        $this->assertStringNotContainsString('correct', $saved->getContent());
        $read = $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}")->assertOk()->assertJsonPath('data.questions.1.answer', $answers[1]['answer']);
        $this->assertNoAnswersOnDevice($read->json('data'), $read->getContent());
        // An unreadable answer is refused kindly, nothing is marked.
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}/answers", ['answers' => [['question_id' => $sitting['questions'][0]['id'], 'answer' => 'not an option']]])
            ->assertUnprocessable();

        $handedIn = $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}/submit", ['operation_id' => (string) Str::uuid(), 'answers' => []])->assertOk()
            ->assertJsonPath('data.state', 'handed_in')->assertJsonPath('data.closed_by', 'learner')
            ->assertJsonPath('data.result.correct_count', 5)->assertJsonPath('data.result.marked_count', 6)->assertJsonPath('data.result.memo_available', false);
        // Classmates may still be sitting it: right or not, but not the memo, until it closes.
        $this->assertStringNotContainsString(self::EXPLANATION, $handedIn->getContent());
        $this->assertFalse($handedIn->json('data.questions.0.correct'));
        $this->assertSame([$sitting['questions'][0]['topic_id']], $handedIn->json('data.result.review.topic_ids'));

        $evidence = MasteryEvidence::where('source_type', 'practice_test')->get();
        $this->assertCount(6, $evidence);
        $this->assertSame(['practice'], $evidence->pluck('audience')->unique()->values()->all());
        $this->assertSame(5, (int) $evidence->sum('achieved_units'));
        $this->assertSame(0, DB::table('question_marks')->count(), 'never an official mark');

        $this->signIn('tara');
        $results = $this->getJson(self::BASE."/groups/{$this->group}/blueprints/{$blueprint['id']}/results")->assertOk()->json('data');
        $this->assertSame([['learner_id' => $this->learner, 'name' => trim(User::findOrFail($this->schoolA->ids['asha_user'])->first_name.' '.User::findOrFail($this->schoolA->ids['asha_user'])->last_name),
            'state' => 'handed_in', 'correct_count' => 5, 'marked_count' => 6, 'closed_by' => 'learner']], $results['learners']);
        $this->assertSame(6, array_sum(array_column($results['topics'], 'answered')));

        // Once the window closes the learner sees the accepted answers and explanations.
        $this->travelTo(now()->addDays(3));
        $this->signIn('asha');
        $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}")->assertOk()->assertJsonPath('data.result.memo_available', true)
            ->assertSee(self::EXPLANATION)->assertJsonPath('data.questions.1.correct', true);
        $this->getJson(self::BASE."/learners/{$this->learner}/tests")->assertOk()->assertJsonPath('data.tests.0.window', 'closed')->assertJsonPath('data.tests.0.test.state', 'handed_in');
    }

    public function test_a_quick_quiz_hand_in_is_idempotent_shows_the_memo_and_hands_a_review_to_the_tutor(): void
    {
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->signIn('asha');
        $topics = $this->getJson(self::BASE."/learners/{$this->learner}/groups/{$this->group}/quiz-topics")->assertOk()->assertJsonPath('data.questions_max', 10)->json('data.topics');
        $this->assertEquals([$this->nodes[$this->keys[0]] => 0, $this->fractions() => 4, $this->decimals() => 3], array_column($topics, 'available', 'id'));
        $path = self::BASE."/learners/{$this->learner}/groups/{$this->group}/quick-quizzes";
        $quiz = $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'topic_ids' => [$this->fractions()], 'question_count' => 3])->assertOk()
            ->assertJsonPath('data.kind', 'quick_quiz')->assertJsonPath('data.time_limit_seconds', 360)->json('data');
        $this->assertCount(3, $quiz['questions']);
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'topic_ids' => [$this->decimals()], 'question_count' => 3])->assertConflict();

        $answers = array_map(fn ($q) => ['question_id' => $q['id'], 'answer' => $this->correctAnswer($q)], $quiz['questions']);
        $answers[2]['answer'] = '9';
        $submit = ['operation_id' => (string) Str::uuid(), 'answers' => $answers];
        $first = $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/submit", $submit)->assertOk()
            ->assertJsonPath('data.result.correct_count', 2)->assertJsonPath('data.result.memo_available', true)->assertSee(self::EXPLANATION)->json('data');
        $this->assertSame(['group_id' => $this->group, 'topic_id' => $this->fractions(), 'source_version_ids' => [$this->source]], $first['result']['review']['tutor']);
        $this->assertNotEmpty($first['questions'][0]['accepted_answers']);

        // The same hand-in again returns the same result; nothing is marked or counted twice.
        $again = $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/submit", $submit)->assertOk()->json('data');
        $this->assertSame(array_diff_key($first, ['server_time' => true]), array_diff_key($again, ['server_time' => true]));
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/submit", array_replace($submit, ['answers' => []]))->assertConflict();
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/submit", ['operation_id' => (string) Str::uuid(), 'answers' => []])->assertConflict();
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/answers", ['answers' => [['question_id' => $quiz['questions'][0]['id'], 'answer' => '1']]])->assertConflict();
        $this->assertSame(3, MasteryEvidence::where('source_type', 'practice_test')->count());
        $this->assertSame(2, (int) MasteryEvidence::where('source_type', 'practice_test')->sum('achieved_units'));
        // The next quick quiz may start now that this one is handed in.
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'topic_ids' => [$this->decimals()], 'question_count' => 3])->assertOk();
    }

    public function test_the_server_deadline_refuses_late_answers_and_a_late_hand_in_counts_only_answers_saved_in_time(): void
    {
        $this->signIn('asha');
        $quiz = $this->postJson(self::BASE."/learners/{$this->learner}/groups/{$this->group}/quick-quizzes", ['operation_id' => (string) Str::uuid(),
            'topic_ids' => [$this->fractions()], 'question_count' => 3])->assertOk()->json('data');
        $answers = self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/answers";
        $first = $quiz['questions'][0];
        $this->postJson($answers, ['answers' => [['question_id' => $first['id'], 'answer' => $this->correctAnswer($first)]]])->assertOk();
        // Within the short network grace after the deadline a save still counts.
        $this->travelTo(now()->addSeconds(360 + 20));
        $this->postJson($answers, ['answers' => [['question_id' => $quiz['questions'][1]['id'], 'answer' => $this->correctAnswer($quiz['questions'][1])]]])->assertOk();
        $this->travelTo(now()->addSeconds(15));
        $this->postJson($answers, ['answers' => [['question_id' => $quiz['questions'][2]['id'], 'answer' => $this->correctAnswer($quiz['questions'][2])]]])->assertConflict();
        $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}")->assertOk()->assertJsonPath('data.state', 'time_up')
            ->assertJsonPath('data.seconds_left', 0)->assertJsonPath('data.result', null)->assertDontSee(self::EXPLANATION);
        $all = array_map(fn ($q) => ['question_id' => $q['id'], 'answer' => $this->correctAnswer($q)], $quiz['questions']);
        $late = $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$quiz['id']}/submit", ['operation_id' => (string) Str::uuid(), 'answers' => $all])->assertOk()
            ->assertJsonPath('data.closed_by', 'time')->assertJsonPath('data.result.marked_count', 2)->assertJsonPath('data.result.unanswered_count', 1)->json('data');
        $this->assertNull($late['questions'][2]['answer']);
        $this->assertNull($late['questions'][2]['marking']);
        $this->assertSame(2, MasteryEvidence::where('source_type', 'practice_test')->count(), 'an unanswered question is not evidence');

        // A cycle test's deadline never runs past its window.
        $blueprint = $this->setCycleTest(['time_limit_minutes' => 60, 'closes_at' => now('UTC')->addMinutes(10)->toIso8601ZuluString()]);
        $this->signIn('asha');
        $sitting = $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertOk()->json('data');
        $this->assertLessThanOrEqual(600, $sitting['seconds_left']);
        $this->travelTo(now()->addMinutes(11));
        $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.state', 'time_up');
    }

    public function test_wrong_role_another_learner_and_a_guardian_are_refused_and_nothing_is_written(): void
    {
        $blueprint = $this->setCycleTest();
        $this->signIn('asha');
        $sitting = $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertOk()->json('data');
        $before = [PracticeTestBlueprint::count(), PracticeTest::count(), PracticeTestQuestion::whereNotNull('answer')->count()];

        // A learner cannot set, list, see results of or withdraw a test.
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput())->assertForbidden();
        $this->getJson(self::BASE."/groups/{$this->group}/blueprints/{$blueprint['id']}/results")->assertForbidden();
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints/{$blueprint['id']}/withdraw", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1])->assertForbidden();
        // A teacher cannot sit the test as the learner.
        $this->signIn('tara');
        $this->postJson(self::BASE."/learners/{$this->learner}/groups/{$this->group}/quick-quizzes", ['operation_id' => (string) Str::uuid(), 'topic_ids' => [$this->fractions()], 'question_count' => 3])->assertNotFound();
        $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}")->assertNotFound();
        // The learner's own approving guardian cannot see, answer or hand it in.
        $this->signIn('farid');
        $this->getJson(self::BASE."/learners/{$this->learner}/tests")->assertNotFound();
        $this->getJson(self::BASE."/learners/{$this->learner}/groups/{$this->group}/quiz-topics")->assertNotFound();
        $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}")->assertNotFound();
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}/answers", ['answers' => [['question_id' => $sitting['questions'][0]['id'], 'answer' => '1']]])->assertNotFound();
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}/submit", ['operation_id' => (string) Str::uuid()])->assertNotFound();
        $this->getJson(self::BASE."/groups/{$this->group}/options")->assertForbidden();
        // Another approved learner gets nothing of Asha's, by her id or by theirs.
        $ben = $this->approveLearner($this->schoolA, 'ben', 'gina');
        $this->signIn('ben');
        $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}")->assertNotFound();
        $this->getJson(self::BASE."/learners/$ben/tests/{$sitting['id']}")->assertNotFound();
        $this->postJson(self::BASE."/learners/$ben/tests/{$sitting['id']}/submit", ['operation_id' => (string) Str::uuid()])->assertNotFound();
        $this->postJson(self::BASE."/learners/$ben/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertNotFound();
        // Withdrawn guardian approval stops the learner too.
        $this->signIn('asha');
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->getJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}")->assertForbidden();
        $this->postJson(self::BASE."/learners/{$this->learner}/tests/{$sitting['id']}/submit", ['operation_id' => (string) Str::uuid()])->assertForbidden();

        $this->assertSame($before, [PracticeTestBlueprint::count(), PracticeTest::count(), PracticeTestQuestion::whereNotNull('answer')->count()]);
        $this->assertNull(PracticeTest::findOrFail($sitting['id'])->submitted_at);
        $this->assertSame(0, MasteryEvidence::where('source_type', 'practice_test')->count());
    }

    public function test_another_school_never_reads_or_writes_this_schools_tests(): void
    {
        $blueprint = $this->setCycleTest();
        $this->signIn('asha');
        $sitting = $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertOk()->json('data');
        $baobab = app(CurrentSchool::class)->run(2, function () {
            SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]);
            User::whereKey($this->schoolB->ids['tara_user'])->update(['password_change_required' => false]);

            return $this->approveLearner($this->schoolB, 'asha', 'farid');
        });
        app(ModuleGate::class)->forget();

        $this->signIn('tara', 'B');
        $this->getJson(self::BASE."/groups/{$this->group}/options")->assertNotFound();
        $this->getJson(self::BASE."/groups/{$this->group}/blueprints")->assertNotFound();
        $this->getJson(self::BASE."/groups/{$this->group}/blueprints/{$blueprint['id']}/results")->assertNotFound();
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput())->assertNotFound();
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints/{$blueprint['id']}/withdraw", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1])->assertNotFound();
        $this->signIn('asha', 'B');
        foreach ([$this->learner, $baobab] as $learner) {
            $this->getJson(self::BASE."/learners/$learner/tests/{$sitting['id']}")->assertNotFound();
            $this->postJson(self::BASE."/learners/$learner/tests/{$sitting['id']}/submit", ['operation_id' => (string) Str::uuid()])->assertNotFound();
            $this->postJson(self::BASE."/learners/$learner/blueprints/{$blueprint['id']}/start", ['operation_id' => (string) Str::uuid()])->assertNotFound();
        }
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => PracticeTestBlueprint::count() + PracticeTest::count()));
        $this->assertSame(1, PracticeTest::count());
        $this->assertNull(PracticeTest::findOrFail($sitting['id'])->submitted_at);
    }

    public function test_past_paper_topic_shares_weight_a_cycle_test_and_bounds_are_kind_and_firm(): void
    {
        SchoolModule::updateOrCreate(['module' => 'exam_coach'], ['enabled' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'title' => 'Synthetic school paper', 'framework' => 'school', 'paper_year' => 2025, 'paper_code' => 'P1', 'group_ids' => [$this->group],
            'licence' => ['kind' => 'school_owned', 'reference' => 'Synthetic original school paper', 'statement' => 'Synthetic school-owned rights confirmed for testing only.',
                'confirmed' => true, 'expires_on' => '2026-12-31'],
            'questions' => [['number' => '1', 'prompt' => 'Fractions.', 'memo' => 'Memo.', 'max_units' => 3000, 'topic_ids' => [$this->fractions()]],
                ['number' => '2', 'prompt' => 'Decimals.', 'memo' => 'Memo.', 'max_units' => 1000, 'topic_ids' => [$this->decimals()]]]]);
        $paper = app(PastPaperLibrary::class)->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => $draft['revision']]);

        $this->signIn('tara');
        $this->getJson(self::BASE."/groups/{$this->group}/options")->assertOk()->assertJsonPath('data.papers.0.version_id', $paper['version_id']);
        $weighted = $this->setCycleTest(['question_count' => 5, 'weighting' => 'past_papers', 'paper_version_ids' => [$paper['version_id']]]);
        $shares = array_column(PracticeTestBlueprint::findOrFail($weighted['id'])->topic_shares, 'questions', 'topic_id');
        $this->assertEquals([$this->fractions() => 4, $this->decimals() => 1], $shares, 'three times the marks, four of five questions');

        $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput(['question_count' => 8]))->assertUnprocessable()
            ->assertSee('Only 7 questions are ready for these topics');
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput(['weighting' => 'past_papers']))->assertUnprocessable();
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput(['closes_at' => now('UTC')->subMinute()->toIso8601ZuluString()]))->assertUnprocessable();
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints", $this->cycleInput(['topic_ids' => [999999]]))->assertUnprocessable();
        try {
            TestKinds::limits(TestKinds::MOCK_EXAM);
            $this->fail('Mock exams are a later blueprint type.');
        } catch (HttpException $error) {
            $this->assertSame(422, $error->getStatusCode());
        }

        // Withdrawn: no new sittings start, and the list says so.
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints/{$weighted['id']}/withdraw", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 2])->assertConflict();
        $this->postJson(self::BASE."/groups/{$this->group}/blueprints/{$weighted['id']}/withdraw", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1])->assertOk()
            ->assertJsonPath('data.active', false);
        $this->signIn('asha');
        $this->postJson(self::BASE."/learners/{$this->learner}/blueprints/{$weighted['id']}/start", ['operation_id' => (string) Str::uuid()])->assertNotFound();
        $this->assertSame(0, PracticeTest::count());
    }

    public function test_the_panel_page_sets_lists_shows_and_withdraws_a_cycle_test_for_the_teachers_group_only(): void
    {
        $tara = $this->actor();
        $this->actingAs($tara)->get(route('practice-tests.index'))->assertOk()->assertSee('Maths learning group')->assertSee('Set a cycle test')->assertSee('Fractions (4 questions from your question bank)');
        $zone = app(CurrentSchool::class)->school()->timezone;
        $form = ['operation_id' => (string) Str::uuid(), 'title' => 'Panel cycle test', 'topic_ids' => [$this->fractions(), $this->decimals()], 'question_count' => 5,
            'time_limit_minutes' => 30, 'opens_at' => now($zone)->subHour()->format('Y-m-d\TH:i'), 'closes_at' => now($zone)->addDay()->format('Y-m-d\TH:i'), 'weighting' => 'even'];
        $this->actingAs($tara)->post(route('practice-tests.create', ['group' => $this->group]), array_replace($form, ['question_count' => 40]))->assertRedirect()->assertSessionHasErrors('question_count');
        $this->assertSame(0, PracticeTestBlueprint::count());
        $this->actingAs($tara)->post(route('practice-tests.create', ['group' => $this->group]), $form)->assertRedirect(route('practice-tests.index', ['group' => $this->group]))->assertSessionHasNoErrors();
        $this->actingAs($tara)->post(route('practice-tests.create', ['group' => $this->group]), $form)->assertRedirect()->assertSessionHasNoErrors();
        $blueprint = PracticeTestBlueprint::sole();
        $this->assertSame(['Panel cycle test', 5, 1800], [$blueprint->title, $blueprint->question_count, $blueprint->time_limit_seconds]);
        $this->assertSame(now($zone)->subHour()->startOfMinute()->utc()->toIso8601ZuluString(), $blueprint->opens_at->toIso8601ZuluString(), 'entered in the school\'s time zone');
        $this->actingAs($tara)->get(route('practice-tests.index', ['group' => $this->group]))->assertOk()->assertSee('Panel cycle test')->assertSee('0 of 0 started');
        $this->actingAs($tara)->get(route('practice-tests.show', ['group' => $this->group, 'blueprint' => $blueprint->id]))->assertOk()
            ->assertSee(User::findOrFail($this->schoolA->ids['asha_user'])->first_name)->assertSee('Not started')->assertSee('Withdraw this test');

        // Not this teacher's group, an unknown test, or a guardian: refused, nothing written.
        $this->actingAs($tara)->get(route('practice-tests.index', ['group' => 999999]))->assertNotFound();
        $this->actingAs($tara)->get(route('practice-tests.show', ['group' => $this->group, 'blueprint' => 999999]))->assertNotFound();
        $this->actingAs($this->actor('theo'))->post(route('practice-tests.create', ['group' => $this->group]), array_replace($form, ['operation_id' => (string) Str::uuid()]))->assertForbidden();
        $this->actingAs($this->actor('theo'))->get(route('practice-tests.show', ['group' => $this->group, 'blueprint' => $blueprint->id]))->assertForbidden();
        $this->actingAs($this->actor('farid'))->get(route('practice-tests.index'))->assertRedirect()->assertDontSee('Panel cycle test');
        // School B's teacher, with practice switched on in School B, finds none of it.
        app(CurrentSchool::class)->run(2, fn () => SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]));
        $baobab = app(CurrentSchool::class)->run(2, function () {
            $user = User::findOrFail($this->schoolB->ids['tara_user']);
            $user->forceFill(['password_change_required' => false])->save();

            return $user;
        });
        $this->actingAs($baobab)->withSession(['school_id' => 2])->get(route('practice-tests.show', ['group' => $this->group, 'blueprint' => $blueprint->id]))->assertNotFound();
        $this->actingAs($baobab)->withSession(['school_id' => 2])->post(route('practice-tests.withdraw', ['group' => $this->group, 'blueprint' => $blueprint->id]), ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1])->assertNotFound();
        $this->assertSame(1, PracticeTestBlueprint::count());
        $this->assertTrue($blueprint->fresh()->active);

        $this->actingAs($tara)->post(route('practice-tests.withdraw', ['group' => $this->group, 'blueprint' => $blueprint->id]), ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($blueprint->fresh()->active);
        $this->actingAs($tara)->post(route('practice-tests.withdraw', ['group' => $this->group, 'blueprint' => $blueprint->id]), ['operation_id' => (string) Str::uuid(), 'expected_revision' => 2])
            ->assertRedirect()->assertSessionHasErrors('practice_test');
    }
}
