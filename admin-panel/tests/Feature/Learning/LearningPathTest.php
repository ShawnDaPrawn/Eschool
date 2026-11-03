<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\LearningPath;
use App\Models\Learning\LearningPathItem;
use App\Models\Learning\LearningPathReview;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeMemory;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Learning\LearningPathQueries;
use App\Services\Learning\LearningPathService;
use App\Services\Learning\TutorAccess;
use App\Services\Learning\TutorContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * The guided learning path (Action Plan 3.9): gap, teach, practise, a
 * server-graded check and a review scheduled with FSRS, for the learner only.
 * engineering/specs/learning/learning-path.md.
 */
class LearningPathTest extends TestCase
{
    use UsesContentLibrary, UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        Http::preventStrayRequests();
        Http::fake();
        // Six approved questions on Fractions (answer 0.5); nothing approved on the other topics yet.
        for ($i = 1; $i <= 6; $i++) {
            $this->approve(['definition' => ['kind' => 'number', 'prompt' => "Fraction question $i: one half as a decimal?", 'explanation' => 'Divide one by two.', 'options' => [], 'answers' => ['0.5']]]);
        }
    }

    private function path(): LearningPathService
    {
        return app(LearningPathService::class);
    }

    private function learner(): int
    {
        return $this->schoolA->ids['asha'];
    }

    private function act(string $action, ?int $revision = null, ?string $operation = null, string $name = 'asha'): array
    {
        $revision ??= (int) (LearningPath::where('learner_id', $this->learner())->value('revision') ?? 0);

        return $this->path()->act($this->actor($name), $this->learner(), $this->group, ['operation_id' => $operation ?? (string) Str::uuid(), 'expected_revision' => $revision, 'action' => $action]);
    }

    private function answer(int $item, string $answer = '0.5', ?string $operation = null): array
    {
        return $this->path()->answer($this->actor('asha'), $this->learner(), $this->group, $item, ['operation_id' => $operation ?? (string) Str::uuid(), 'answer' => $answer]);
    }

    private function answerAll(array $state, string $answer = '0.5'): array
    {
        foreach ($state['items'] as $item) {
            $state = $this->answer($item['id'], $answer);
        }

        return $state;
    }

    private function read(): array
    {
        return $this->path()->read($this->actor('asha'), $this->learner(), $this->group);
    }

    public function test_full_loop_finds_the_gap_teaches_practises_checks_and_schedules_review_without_answers_on_the_device(): void
    {
        $start = $this->read();
        $this->assertSame(['gap', null, ['next']], [$start['step'], $start['path_id'], $start['actions']]);

        $teach = $this->act('next', 0);
        $this->assertSame('teach', $teach['step']);
        $this->assertSame(['id' => $this->nodes[$this->keys[1]], 'name' => 'Fractions'], $teach['topic']);
        $this->assertSame('not_yet_assessed', $teach['reason']);
        $this->assertSame(1, $teach['revision']);
        // The tutor is off (the AI provider is not qualified): the school's own published material teaches.
        $this->assertSame(['source' => 'school', 'content_version_id' => $this->source, 'library_item_version_id' => null, 'title' => 'Synthetic fractions', 'body' => null], $teach['teach']['lessons'][0]);
        $this->assertSame(['available' => false, 'session_id' => null, 'reason' => 'not_ready'], $teach['teach']['tutor']);
        $this->assertSame(['practise'], $teach['actions']);

        $practise = $this->act('practise');
        $this->assertSame('practise', $practise['step']);
        $this->assertCount(LearningPathService::PRACTICE_SIZE, $practise['items']);
        $this->assertSame(['answer'], $practise['actions']);
        $encoded = json_encode($practise, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('answers', $encoded);
        $this->assertStringNotContainsString('Divide one by two', $encoded, 'No explanation before an answer.');
        $this->assertNull($practise['items'][0]['explanation']);
        $this->refuses(fn () => $this->act('check'), 409);

        $official = DB::table('question_marks')->count();
        $afterOne = $this->answer($practise['items'][0]['id'], '.50');
        $this->assertTrue($afterOne['items'][0]['correct']);
        $this->assertSame('Divide one by two.', $afterOne['items'][0]['explanation']);
        $this->assertNull($afterOne['items'][1]['explanation']);
        $state = $this->answerAll(['items' => array_slice($practise['items'], 1)]);
        $this->assertSame(['check'], $state['actions']);

        $check = $this->act('check');
        $this->assertSame('check', $check['step']);
        $this->assertCount(LearningPathService::CHECK_SIZE, $check['items']);
        $this->assertStringNotContainsString('answers', json_encode($check, JSON_THROW_ON_ERROR));
        // The check starts with the question not practised in this cycle.
        $practisedVersions = LearningPathItem::where('purpose', 'practice')->pluck('practice_item_version_id')->all();
        $this->assertNotContains(LearningPathItem::where('purpose', 'check')->where('position', 1)->value('practice_item_version_id'), $practisedVersions);

        $done = $this->answerAll($check);
        $this->assertSame('review', $done['step']);
        $this->assertSame('passed', $done['notice']);
        $this->assertSame(['correct' => 3, 'total' => 3, 'needed' => 2, 'passed' => true], $done['check']);
        // A first good FSRS review: stability 2.4 days, so the topic comes back two days later.
        $this->assertSame('2026-02-12', $done['review_due_on']);
        $this->assertStringContainsString('2026-02-12', $done['message']);
        $this->assertSame(['next'], $done['actions']);
        $review = LearningPathReview::sole();
        $this->assertSame([$this->nodes[$this->keys[1]], 3], [$review->topic_id, $review->state['rating']]);
        $this->assertSame(['path_check', 'path_practice'], MasteryEvidence::orderBy('source_type')->pluck('source_type')->unique()->values()->all());
        $this->assertSame(8, MasteryEvidence::count());
        $this->assertSame(6, PracticeMemory::count(), 'Each question practised or checked keeps its own FSRS memory.');
        $this->assertSame($official, DB::table('question_marks')->count(), 'A path never writes official marks.');
        $this->assertStringNotContainsString('.50', (string) LearningPathItem::whereNotNull('answer')->first()->getRawOriginal('answer'));

        // Nothing else has approved practice, and Fractions waits for its review.
        $caught = $this->act('next');
        $this->assertSame(['gap', 'caught_up', null], [$caught['step'], $caught['notice'], $caught['topic']]);
        $this->assertSame([['topic_id' => $this->nodes[$this->keys[1]], 'name' => 'Fractions', 'due_on' => '2026-02-12']], $caught['upcoming_reviews']);

        // On the review day the path starts straight at the check.
        $this->travelTo(now()->addDays(2));
        $due = $this->act('next');
        $this->assertSame(['check', 'review', 'review_due'], [$due['step'], $due['mode'], $due['reason']]);
        $this->assertCount(3, $due['items']);
        $again = $this->answerAll($due);
        $this->assertSame('review', $again['step']);
        $this->assertGreaterThan('2026-02-14', $again['review_due_on'], 'A second good review waits longer.');
    }

    public function test_a_failed_check_loops_back_to_teach_gently_and_rests_the_topic_after_three_tries(): void
    {
        $this->act('next', 0);
        foreach ([1, 2] as $try) {
            $this->answerAll($this->act('practise'));
            $failed = $this->answerAll($this->act('check'), '0.25');
            $this->assertSame(['teach', 'not_yet', $try + 1], [$failed['step'], $failed['notice'], $failed['attempt']]);
            $this->assertSame(['correct' => 0, 'total' => 3, 'needed' => 2, 'passed' => false], $failed['check']);
            $this->assertStringContainsString('Not quite yet', $failed['message']);
            $this->assertSame(['practise'], $failed['actions']);
        }
        $this->assertSame(0, LearningPathReview::count(), 'Nothing to review until the topic is passed.');
        $this->answerAll($this->act('practise'));
        $rest = $this->answerAll($this->act('check'), '0.25');
        $this->assertSame(['gap', 'rest', null], [$rest['step'], $rest['notice'], $rest['topic']]);
        $this->assertStringContainsString('Fractions', $rest['message']);
        $this->assertSame(['next'], $rest['actions']);
        // The topic rests until tomorrow; nothing else is ready today.
        $this->assertSame(['gap', 'rest'], array_values(array_intersect_key($this->act('next'), array_flip(['step', 'notice']))));
        $this->travelTo(now()->addDay());
        $back = $this->act('next');
        $this->assertSame(['teach', 'Fractions', 1], [$back['step'], $back['topic']['name'], $back['attempt']]);
    }

    public function test_step_actions_are_idempotent_and_a_double_submit_advances_once(): void
    {
        $operation = (string) Str::uuid();
        $first = $this->act('next', 0, $operation);
        $this->assertSame($first, $this->act('next', 0, $operation));
        $this->assertSame(1, LearningPath::sole()->revision);
        // The same tap sent again with a new operation id is refused: the path already moved on.
        $this->refuses(fn () => $this->act('next', 0), 409);
        $this->refuses(fn () => $this->act('next', 1, $operation), 409);
        $this->refuses(fn () => $this->act('check', 1), 409);

        $practise = $this->act('practise', 1);
        $item = $practise['items'][0]['id'];
        $answerOp = (string) Str::uuid();
        $answered = $this->answer($item, '0.25', $answerOp);
        $this->assertSame($answered, $this->answer($item, '0.25', $answerOp));
        $this->refuses(fn () => $this->answer($item, '0.5'), 409);
        $this->refuses(fn () => $this->answer($item, '0.5', $answerOp), 409);
        $this->assertSame(1, MasteryEvidence::count());
        $this->assertSame(1, PracticeMemory::sole()->state['rating']);
        $this->assertSame(3, LearningPath::sole()->revision);
        try {
            $this->answer($practise['items'][1]['id'], 'half');
            $this->fail('A malformed answer is a validation error.');
        } catch (ValidationException) {
            $this->assertNull(LearningPathItem::findOrFail($practise['items'][1]['id'])->answered_at);
        }
        // A check item cannot be answered while practising.
        $this->refuses(fn () => $this->answer(999999), 404);
    }

    public function test_module_switches_and_stage_refuse_the_path(): void
    {
        $this->act('next', 0);
        foreach (['daily_practice', 'mastery'] as $module) {
            SchoolModule::where('module', $module)->update(['enabled' => false]);
            app(ModuleGate::class)->forget();
            $this->refuses(fn () => $this->read(), 403);
            $this->refuses(fn () => $this->act('practise', 1), 403);
            $this->refuses(fn () => app(LearningPathQueries::class)->group($this->actor('tara'), $this->group), 403);
            SchoolModule::where('module', $module)->update(['enabled' => true]);
            app(ModuleGate::class)->forget();
        }
        // Grade 7 set to the early years stage: daily practice is not for that stage.
        DB::table('school_stages')->updateOrInsert(['school_id' => 1, 'grade_level_id' => (int) DB::table('grade_levels')->where('code', '7')->value('id')], ['stage' => 'early_years']);
        app(SchoolStages::class)->forget();
        $this->refuses(fn () => $this->read(), 403);
    }

    public function test_wrong_role_other_learner_and_other_guardian_cannot_read_or_act_on_the_path(): void
    {
        $this->act('next', 0);
        $learner = $this->learner();
        // A teacher, a guardian and another learner cannot use the learner's own endpoints.
        foreach (['tara', 'farid', 'ben'] as $name) {
            $this->refuses(fn () => $this->path()->read($this->actor($name), $learner, $this->group), 404);
            $this->refuses(fn () => $this->path()->act($this->actor($name), $learner, $this->group, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'action' => 'practise']), 404);
        }
        $this->assertSame('teach', LearningPath::sole()->step);
        // A learner is not a teacher of the group and not a guardian.
        $this->refuses(fn () => app(LearningPathQueries::class)->group($this->actor('asha'), $this->group), 403);
        $this->refuses(fn () => app(LearningPathQueries::class)->family($this->actor('asha'), $learner), 404);
        // Another child's guardian cannot read this child's summary.
        $this->refuses(fn () => app(LearningPathQueries::class)->family($this->actor('gina'), $learner), 404);
    }

    public function test_teacher_sees_steps_without_scores_and_guardian_sees_a_plain_summary_of_their_own_child(): void
    {
        $this->act('next', 0);
        $roster = app(LearningPathQueries::class)->group($this->actor('tara'), $this->group);
        $row = collect($roster['learners'])->firstWhere('learner_id', $this->learner());
        $this->assertSame(['teach', 'Learning something new', 'Fractions', false], [$row['step'], $row['step_label'], $row['topic']['name'], $row['needs_support']]);
        $encoded = json_encode($roster, JSON_THROW_ON_ERROR);
        foreach (['level', 'confidence', 'correct', 'rank', 'score'] as $field) {
            $this->assertStringNotContainsString('"'.$field, $encoded);
        }
        $summary = app(LearningPathQueries::class)->family($this->actor('farid'), $this->learner());
        $this->assertSame([['subject_id' => $this->subject, 'subject_name' => DB::table('subjects')->where('id', $this->subject)->value('name'), 'step' => 'teach',
            'step_label' => 'Learning something new', 'topic_name' => 'Fractions', 'next_review_on' => null, 'updated_on' => '2026-02-10']], $summary['subjects']);
        $this->assertStringNotContainsString('items', json_encode($summary, JSON_THROW_ON_ERROR));
    }

    public function test_school_b_can_never_read_or_advance_school_a_paths(): void
    {
        $this->act('next', 0);
        $before = LearningPath::sole()->only(['step', 'revision']);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            $baobabLearner = User::findOrFail($this->schoolB->ids['asha_user']);
            $baobabTeacher = User::findOrFail($this->schoolB->ids['tara_user']);
            $this->assertSame(0, LearningPath::count(), 'School A paths are invisible in School B.');
            // School B has the modules on too, so only the school boundary refuses.
            foreach (['mastery', 'daily_practice'] as $module) {
                SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
            }
            app(ModuleGate::class)->forget();
            $this->refuses(fn () => $this->path()->read($baobabLearner, $this->learner(), $this->group), 404);
            $this->refuses(fn () => $this->path()->act($baobabLearner, $this->learner(), $this->group, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'action' => 'practise']), 404);
            $this->refuses(fn () => $this->path()->answer($baobabLearner, $this->learner(), $this->group, 1, ['operation_id' => (string) Str::uuid(), 'answer' => '0.5']), 404);
            $this->refuses(fn () => app(LearningPathQueries::class)->group($baobabTeacher, $this->group), 404);
            $this->refuses(fn () => app(LearningPathQueries::class)->family(User::findOrFail($this->schoolB->ids['farid_user']), $this->learner()), 404);
        });
        $this->assertSame($before, LearningPath::sole()->only(['step', 'revision']));
    }

    public function test_the_tutor_leads_the_teach_step_when_it_is_on_and_allowed(): void
    {
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true]);
        $teach = $this->act('next', 0);
        $this->assertSame(['available' => true, 'reason' => null, 'session_id' => null], $teach['teach']['tutor']);
        $this->assertSame(['open_tutor', 'practise'], $teach['actions']);
        $opened = $this->act('open_tutor');
        $session = $opened['teach']['tutor']['session_id'];
        $this->assertIsString($session);
        $this->assertSame(['practise'], $opened['actions']);
        $row = app(TutorAccess::class)->session($this->actor('asha'), $session);
        $this->assertSame([$this->nodes[$this->keys[1]], [$this->source]], [(int) $row->topic_id, $row->source_version_ids]);
        $context = app(TutorContext::class)->capture($this->actor('asha'), $row);
        $this->assertSame(['step' => 'teach', 'lead' => true, 'attempt' => 1], json_decode($context['text'], true)['learning_path']);
        $this->assertSame(LearningPath::sole()->id, $context['manifest']['path']['id']);
        // The step moves on; the session's earlier context still reauthorizes.
        $this->act('practise');
        app(TutorContext::class)->reauthorize($this->actor('asha'), $row, $context['manifest']);
        $this->assertSame('practise', json_decode(app(TutorContext::class)->capture($this->actor('asha'), $row)['text'], true)['learning_path']['step']);
        Http::assertNothingSent();
    }

    public function test_library_lessons_and_questions_follow_the_schools_own_only_while_the_switch_is_on(): void
    {
        $standard = $this->standard('MATH-SP');
        DB::table('curriculum_plans')->where('school_id', 1)->update(['standard_id' => $standard->id,
            'standard_snapshot' => json_encode(['id' => $standard->id, 'framework' => 'CAPS', 'code' => 'MATH-SP', 'name' => $standard->name])]);
        $decimals = $this->libraryTopic('Decimals');
        $lesson = $this->publishedItem($decimals, ['kind' => 'lesson', 'title' => 'Tenths and hundredths', 'body' => 'A decimal point separates whole numbers from tenths.']);
        $this->publishedItem($decimals, ['title' => 'Tenths', 'prompt' => 'Write three tenths as a decimal.', 'answers' => '0.3', 'explanation' => 'Three tenths is 0.3.']);
        // The school's own Fractions questions are mastered, so only Decimals is left to learn.
        PracticeItem::query()->update(['active' => false]);
        $this->assertSame('nothing_ready', $this->act('next', 0)['notice']);

        $this->switchLibrary(1, true);
        $teach = $this->act('next');
        $this->assertSame('Decimals', $teach['topic']['name']);
        $this->assertSame([['source' => 'library', 'content_version_id' => null, 'library_item_version_id' => (int) $lesson->published_version_id,
            'title' => 'Tenths and hundredths', 'body' => 'A decimal point separates whole numbers from tenths.']], $teach['teach']['lessons']);
        $practise = $this->act('practise');
        $this->assertSame('Write three tenths as a decimal.', $practise['items'][0]['question']['prompt']);
        $this->assertStringNotContainsString('0.3', json_encode($practise, JSON_THROW_ON_ERROR));
        $this->answer($practise['items'][0]['id'], '0.3');
        $evidence = MasteryEvidence::sole();
        $this->assertSame(['library', $this->nodes[$this->keys[2]]], [$evidence->content_source, (int) $evidence->topic_id]);
        // Switching the library off withdraws its questions: the learner chooses again.
        $this->switchLibrary(1, false);
        $this->assertSame(['gap', 'withdrawn', ['next']], array_values(array_intersect_key($this->read(), array_flip(['step', 'notice', 'actions']))));
    }
}
