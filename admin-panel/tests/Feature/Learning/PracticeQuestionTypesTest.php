<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\PracticeAudioAnswer;
use App\Models\Learning\PracticeDiagram;
use App\Models\Learning\PracticeMemory;
use App\Models\Learning\PracticeReview;
use App\Models\Learning\PracticeSetEntry;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\PracticeContract;
use App\Services\Learning\PracticeMarking;
use App\Services\Learning\PracticeMedia;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\SyntheticCleanScanner;
use Tests\TestCase;

/**
 * Question types and the offline answer loophole (Action Plan 3.11):
 * engineering/specs/learning/question-types.md.
 */
class PracticeQuestionTypesTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    private const BASE = '/api/v2/learning/practice';

    /** @var array<string, int> entry id by question marker */
    private array $entries = [];

    private array $set = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        Storage::fake(PracticeMedia::SCHOOL_DISK);
        Storage::fake(PracticeMedia::LIBRARY_DISK);
        $this->app->instance(UploadScanner::class, new SyntheticCleanScanner);
    }

    private function signIn(string $name): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id,
            'X-Practice-Contract' => (string) $this->contract]);
    }

    /** These tests read the answer-free contract unless a test says otherwise. */
    private int $contract = PracticeContract::ANSWER_FREE;

    private function base(string $kind, string $marker): array
    {
        return ['kind' => $kind, 'prompt' => "QUESTION-$marker", 'explanation' => "EXPLAIN-$marker", 'options' => [], 'answers' => []];
    }

    /** One approved question of every type, each 60 seconds, then the learner's set. */
    private function everyType(): void
    {
        $this->signIn('tara');
        $diagram = $this->post(self::BASE."/groups/{$this->group}/diagrams", ['photo' => UploadedFile::fake()->image('plant.png', 400, 300)], ['Accept' => 'application/json'])
            ->assertOk()->json('data');
        $definitions = [
            'number' => ['answers' => ['7311']] + $this->base('number', 'number'),
            'steps' => ['steps' => [['kind' => 'number', 'prompt' => 'Brackets first.', 'options' => [], 'answers' => ['4217']],
                ['kind' => 'choice', 'prompt' => 'Then?', 'options' => [['id' => 'a', 'text' => 'Multiply'], ['id' => 'b', 'text' => 'Add']], 'answers' => ['a']],
                ['kind' => 'number', 'prompt' => 'Answer.', 'options' => [], 'answers' => ['8434']]]] + $this->base('steps', 'steps'),
            'matched' => ['answers' => ['Photosynthesis secret']] + $this->base('short_text', 'matched'),
            'unmatched' => ['answers' => ['Evaporation secret']] + $this->base('short_text', 'unmatched'),
            'diagram' => ['diagram' => ['id' => $diagram['id']], 'regions' => [['id' => 'p1', 'x' => 50, 'y' => 10], ['id' => 'p2', 'x' => 50, 'y' => 90]],
                'labels' => [['id' => 'leaf', 'text' => 'Leaf label secret'], ['id' => 'root', 'text' => 'Root label secret'], ['id' => 'stem', 'text' => 'Stem']],
                'mapping' => ['p1' => 'leaf', 'p2' => 'root']] + $this->base('diagram_label', 'diagram'),
            'audio' => ['max_seconds' => 60] + $this->base('audio', 'audio'),
        ];
        foreach ($definitions as $definition) {
            $this->postJson(self::BASE."/groups/{$this->group}/bank", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'topic_id' => $this->nodes[$this->keys[1]],
                'content_version_id' => $this->source, 'estimated_seconds' => 60, 'active' => true, 'definition' => $definition])->assertOk();
        }
        $this->signIn('asha');
        $learner = $this->schoolA->ids['asha'];
        $this->set = $this->postJson(self::BASE."/learners/$learner/groups/{$this->group}/daily-sets", ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10'])
            ->assertOk()->json('data');
        foreach ($this->set['items'] as $item) {
            $this->entries[substr($item['question']['prompt'], 9)] = $item['id'];
        }
        $this->assertCount(6, $this->entries);
    }

    private function answer(string $marker, array $body, int $status = 200): array
    {
        $learner = $this->schoolA->ids['asha'];

        return $this->postJson(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries/{$this->entries[$marker]}/review", $body + ['operation_id' => (string) Str::uuid(), 'rating' => 3])
            ->assertStatus($status)->json('data') ?? [];
    }

    private function audioFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('answer.m4a', "\x00\x00\x00\x20ftypM4A \x00\x00\x00\x00M4A mp42isom".str_repeat("\x00", 512));
    }

    public function test_the_downloaded_set_carries_no_answers_or_explanations_until_each_answer_is_recorded(): void
    {
        $this->everyType();
        $learner = $this->schoolA->ids['asha'];
        $raw = $this->getJson(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}")->assertOk()->getContent();
        foreach (['EXPLAIN-', '7311', '4217', '8434', 'Photosynthesis secret', 'Evaporation secret', '"answers"', '"mapping"', '"explanation"', '"solution"'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw, "The offline set must not carry $secret");
        }
        // Labels are part of the question; which label goes where is not.
        $this->assertStringContainsString('Leaf label secret', $raw);
        $diagram = collect($this->set['items'])->firstWhere('id', $this->entries['diagram'])['question'];
        $this->assertSame(['width' => 400, 'height' => 300], $diagram['diagram']);
        $this->assertSame(60, collect($this->set['items'])->firstWhere('id', $this->entries['audio'])['question']['max_seconds']);

        $result = $this->answer('number', ['answer' => '7311']);
        $this->assertSame(['explanation' => 'EXPLAIN-number', 'answers' => ['7311'], 'steps' => [], 'mapping' => []], $result['solution']);
        $raw = $this->getJson(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}")->assertOk()->getContent();
        $this->assertStringContainsString('EXPLAIN-number', $raw, 'shown once the answer is recorded');
        foreach (['EXPLAIN-steps', 'EXPLAIN-matched', 'EXPLAIN-diagram', 'EXPLAIN-audio', '4217', 'Evaporation secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw, 'still hidden for unanswered questions');
        }
    }

    public function test_each_type_is_marked_on_sync_and_steps_count_their_share_of_correct_steps(): void
    {
        $this->everyType();
        $steps = $this->answer('steps', ['parts' => ['s1' => '4217', 's2' => 'b', 's3' => '8434']]);
        $this->assertSame(['marked', false, 2, 3, ['s1' => true, 's2' => false, 's3' => true]], [$steps['state'], $steps['correct'], $steps['achieved_units'], $steps['maximum_units'], $steps['parts']]);
        $this->assertSame([['id' => 's1', 'answers' => ['4217']], ['id' => 's2', 'answers' => ['a']], ['id' => 's3', 'answers' => ['8434']]], $steps['solution']['steps']);
        $diagram = $this->answer('diagram', ['parts' => ['p1' => 'leaf', 'p2' => 'root']]);
        $this->assertSame([true, 1, 1], [$diagram['correct'], $diagram['achieved_units'], $diagram['maximum_units']]);
        $this->assertSame([['region' => 'p1', 'label' => 'leaf'], ['region' => 'p2', 'label' => 'root']], $diagram['solution']['mapping']);
        $matched = $this->answer('matched', ['answer' => '  photosynthesis, SECRET! ']);
        $this->assertSame(['marked', true, 'automatic'], [$matched['state'], $matched['correct'], $matched['marked_how']]);
        $waiting = $this->answer('unmatched', ['answer' => 'water goes up']);
        $this->assertSame(['awaiting_teacher', null, null, null], [$waiting['state'], $waiting['correct'], $waiting['achieved_units'], $waiting['schedule']]);
        $this->assertSame('EXPLAIN-unmatched', $waiting['solution']['explanation'], 'the explanation follows the recorded answer even while it waits');
        // Each kind is answered its own way.
        $this->answer('audio', ['answer' => 'typed instead'], 422);
        $this->answer('number', ['parts' => ['s1' => '1']], 422);

        $evidence = MasteryEvidence::where('source_type', 'practice_review')->orderBy('id')->get(['source_key', 'achieved_units', 'maximum_units']);
        $this->assertSame([[2, 3], [1, 1], [1, 1]], $evidence->map(fn ($e) => [$e->achieved_units, $e->maximum_units])->all(), 'waiting answers write no evidence yet');
        $this->assertSame(3, PracticeMemory::count());
    }

    public function test_a_replayed_answer_is_marked_once_and_a_second_answer_is_refused(): void
    {
        $this->everyType();
        $learner = $this->schoolA->ids['asha'];
        $path = self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries/{$this->entries['steps']}/review";
        $body = ['operation_id' => (string) Str::uuid(), 'parts' => ['s1' => '4217', 's2' => 'a', 's3' => '8434'], 'rating' => 3];
        $first = $this->postJson($path, $body)->assertOk()->json('data');
        $this->postJson($path, $body)->assertOk()->assertJsonPath('data', $first);
        $this->postJson($path, array_replace($body, ['parts' => ['s1' => '1', 's2' => 'b', 's3' => '2']]))->assertConflict();
        $this->postJson($path, array_replace($body, ['operation_id' => (string) Str::uuid()]))->assertConflict();
        $this->assertSame(1, PracticeReview::count());
        $this->assertSame(1, MasteryEvidence::where('source_type', 'practice_review')->count());
    }

    public function test_a_spoken_answer_waits_privately_for_the_learners_own_teacher_who_marks_it_once(): void
    {
        $this->everyType();
        $learner = $this->schoolA->ids['asha'];
        $path = self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries/{$this->entries['audio']}/review";
        $operation = (string) Str::uuid();
        $saved = $this->post($path, ['operation_id' => $operation, 'rating' => 3, 'audio' => $this->audioFile()], ['Accept' => 'application/json'])->assertOk()->json('data');
        $this->assertSame(['awaiting_teacher', null], [$saved['state'], $saved['correct']]);
        $recording = PracticeAudioAnswer::sole();
        $this->assertSame('audio/mp4', $recording->mime);
        $this->assertSame('2026-05-11', $recording->purge_after->toDateString());
        $this->assertStringStartsWith('schools/1/practice-audio/', $recording->path);
        $this->assertStringNotContainsString('ftypM4A', Storage::disk(PracticeMedia::SCHOOL_DISK)->get($recording->path), 'encrypted at rest');
        $this->answer('unmatched', ['answer' => 'water goes up']);

        // The learner, a parent and another teacher of the school can neither see nor hear it.
        foreach (['asha', 'farid', 'theo'] as $outsider) {
            $this->signIn($outsider);
            $this->getJson(self::BASE."/groups/{$this->group}/marking")->assertForbidden();
            $this->get(self::BASE."/groups/{$this->group}/marking/{$saved['id']}/audio")->assertForbidden();
            $this->postJson(self::BASE."/groups/{$this->group}/marking/{$saved['id']}", ['operation_id' => (string) Str::uuid(), 'correct' => true])->assertForbidden();
        }
        // Another school's teacher is refused: the group is not found in their school.
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolB->tokens['tara'])->withHeaders(['X-School-Id' => '2', 'X-Account-Id' => (string) $this->schoolB->ids['tara_user']]);
        $this->assertContains($this->getJson(self::BASE."/groups/{$this->group}/marking")->status(), [403, 404]);
        $this->assertContains($this->get(self::BASE."/groups/{$this->group}/marking/{$saved['id']}/audio")->status(), [403, 404]);
        app(CurrentSchool::class)->run(2, function () use ($saved) {
            $this->refuses(fn () => app(PracticeMarking::class)->queue($this->actor(), $this->group), 404);
            $this->assertNull(PracticeReview::find($saved['id']));
        });

        // The learner's own teacher sees both waiting answers, hears the recording and marks it once.
        $this->signIn('tara');
        $queue = $this->getJson(self::BASE."/groups/{$this->group}/marking")->assertOk()->json('data');
        $this->assertSame(2, $queue['total']);
        $row = collect($queue['items'])->firstWhere('id', $saved['id']);
        $this->assertSame(['audio', 'QUESTION-audio', null, true], [$row['kind'], $row['prompt'], $row['answer'], $row['audio']['kept']]);
        $this->assertNull($row['suggestion'], 'AI-assisted marking stays switched off');
        $typed = collect($queue['items'])->firstWhere('kind', 'short_text');
        $this->assertSame(['water goes up', ['Evaporation secret']], [$typed['answer'], $typed['accepted']]);
        $bytes = $this->get(self::BASE."/groups/{$this->group}/marking/{$saved['id']}/audio")->assertOk()->assertHeader('Content-Type', 'audio/mp4')->getContent();
        $this->assertStringContainsString('ftypM4A', $bytes);

        $mark = ['operation_id' => (string) Str::uuid(), 'correct' => true];
        $receipt = $this->postJson(self::BASE."/groups/{$this->group}/marking/{$saved['id']}", $mark)->assertOk()->json('data');
        $this->assertSame(['id' => $saved['id'], 'state' => 'marked', 'correct' => true, 'marked_how' => 'teacher'], $receipt);
        $this->postJson(self::BASE."/groups/{$this->group}/marking/{$saved['id']}", $mark)->assertOk()->assertJsonPath('data', $receipt);
        $this->postJson(self::BASE."/groups/{$this->group}/marking/{$saved['id']}", ['operation_id' => (string) Str::uuid(), 'correct' => false])->assertConflict();
        $this->assertSame(1, MasteryEvidence::where('source_key', 'practice:'.$saved['id'])->count());
        $this->assertSame([1, 1], MasteryEvidence::where('source_key', 'practice:'.$saved['id'])->get(['achieved_units', 'maximum_units'])->map(fn ($e) => [$e->achieved_units, $e->maximum_units])->sole());
        $this->assertSame('2026-03-12', PracticeAudioAnswer::sole()->purge_after->toDateString(), 'kept 30 days after marking');
        $this->assertSame(1, $this->getJson(self::BASE."/groups/{$this->group}/marking")->assertOk()->json('data.total'));

        // The learner sees the teacher's mark on the next read, with the memory schedule written once.
        $this->signIn('asha');
        $review = collect($this->getJson(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}")->assertOk()->json('data.items'))->firstWhere('id', $this->entries['audio'])['review'];
        $this->assertSame(['marked', true, 'teacher'], [$review['state'], $review['correct'], $review['marked_how']]);
        $this->assertNotNull($review['schedule']);
        $this->assertSame(1, PracticeMemory::count());
        // A learner's replay of the recording upload returns its receipt; it is not stored twice.
        $this->post($path, ['operation_id' => $operation, 'rating' => 3, 'audio' => $this->audioFile()], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(1, PracticeAudioAnswer::count());
    }

    public function test_recordings_are_deleted_after_their_retention_date_and_the_mark_stays(): void
    {
        $this->everyType();
        $learner = $this->schoolA->ids['asha'];
        $this->post(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries/{$this->entries['audio']}/review",
            ['operation_id' => (string) Str::uuid(), 'rating' => 3, 'audio' => $this->audioFile()], ['Accept' => 'application/json'])->assertOk();
        $recording = PracticeAudioAnswer::sole();
        $this->travelTo(now()->addDays(91));
        $this->artisan('learning:prune-practice-audio')->assertSuccessful();
        $this->assertNotNull($recording->fresh()->purged_at);
        $this->assertFalse(Storage::disk(PracticeMedia::SCHOOL_DISK)->exists($recording->path));
        $this->assertSame(1, PracticeReview::count());
    }

    public function test_the_diagram_picture_reaches_only_the_learner_whose_set_it_is(): void
    {
        $this->everyType();
        $learner = $this->schoolA->ids['asha'];
        $path = self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries/{$this->entries['diagram']}/diagram";
        $this->get($path)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $this->get(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries/{$this->entries['number']}/diagram")->assertNotFound();
        $this->signIn('farid');
        $this->get($path)->assertNotFound();
        $this->signIn('tara');
        $this->get($path)->assertNotFound();
        app(CurrentSchool::class)->run(2, function () use ($learner) {
            $this->refuses(fn () => app(DailyPractice::class)->diagram($this->actor('asha'), $learner, $this->set['id'], $this->entries['diagram']), 404);
        });
    }

    public function test_a_teacher_cannot_use_another_schools_diagram_and_only_a_current_teacher_uploads_one(): void
    {
        $other = app(CurrentSchool::class)->run(2, fn () => PracticeDiagram::create(['id' => (string) Str::uuid(), 'uploaded_by' => $this->schoolB->ids['tara_user'],
            'path' => 'schools/2/practice-diagrams/x.enc', 'sha256' => str_repeat('c', 64), 'bytes' => 1, 'width' => 10, 'height' => 10]));
        $this->signIn('tara');
        $this->postJson(self::BASE."/groups/{$this->group}/bank", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'topic_id' => $this->nodes[$this->keys[1]],
            'content_version_id' => $this->source, 'estimated_seconds' => 60, 'active' => true,
            'definition' => ['diagram' => ['id' => $other->id], 'regions' => [['id' => 'p1', 'x' => 1, 'y' => 1]], 'labels' => [['id' => 'a', 'text' => 'A']], 'mapping' => ['p1' => 'a']]
                + $this->base('diagram_label', 'x')])->assertUnprocessable();
        $this->signIn('theo');
        $this->post(self::BASE."/groups/{$this->group}/diagrams", ['photo' => UploadedFile::fake()->image('d.png', 20, 20)], ['Accept' => 'application/json'])->assertForbidden();
        $this->signIn('asha');
        $this->post(self::BASE."/groups/{$this->group}/diagrams", ['photo' => UploadedFile::fake()->image('d.png', 20, 20)], ['Accept' => 'application/json'])->assertForbidden();
        $this->signIn('tara');
        $this->post(self::BASE."/groups/{$this->group}/diagrams", ['photo' => UploadedFile::fake()->createWithContent('d.png', 'not a picture')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame(0, PracticeDiagram::count());
    }

    public function test_the_panel_writes_any_type_and_marks_waiting_answers(): void
    {
        $this->actingAs($this->actor('tara'));
        $this->get(route('question-banks.write'))->assertOk()->assertSee(__('Write a practice question'));
        $this->get(route('question-banks.write', ['group' => $this->group]))->assertOk()->assertSee(__('Worked steps'));
        $this->post(route('question-banks.create'), ['operation_id' => (string) Str::uuid(), 'group_id' => $this->group, 'topic_id' => $this->nodes[$this->keys[1]],
            'content_version_id' => $this->source, 'estimated_seconds' => 60, 'question_kind' => 'short_text', 'prompt' => 'Name the process.',
            'explanation' => 'Plants make food from light.', 'answers' => "photosynthesis\nfood making"])->assertRedirect();
        $this->post(route('question-banks.create'), ['operation_id' => (string) Str::uuid(), 'group_id' => $this->group, 'topic_id' => $this->nodes[$this->keys[1]],
            'content_version_id' => $this->source, 'estimated_seconds' => 60, 'question_kind' => 'steps', 'prompt' => 'Work it out.',
            'explanation' => 'One step at a time.', 'steps' => 'Only one step | 4'])->assertSessionHasErrors('question');
        $set = $this->issue();
        app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $set['items'][0]['id'],
            ['operation_id' => (string) Str::uuid(), 'answer' => 'the sun helps', 'rating' => 3]);
        $this->actingAs($this->actor('tara'));
        $page = $this->get(route('question-banks.marking'))->assertOk()->assertSee('the sun helps')->assertSee(__('Correct'));
        $page->assertDontSee('btn-');
        $review = PracticeReview::sole();
        $this->post(route('question-banks.marking.mark', ['group' => $this->group, 'review' => $review->id]), ['operation_id' => (string) Str::uuid(), 'correct' => 0])
            ->assertRedirect(route('question-banks.marking', ['group' => $this->group]));
        $this->assertSame(['marked', false, 'teacher'], [$review->fresh()->state, $review->fresh()->correct, $review->fresh()->marked_how]);
        $this->actingAs($this->actor('theo'));
        $this->get(route('question-banks.marking', ['group' => $this->group]))->assertNotFound();
    }

    public function test_a_legacy_client_gets_todays_exact_set_shape_with_only_choice_and_number_questions(): void
    {
        $this->everyType();
        $learner = $this->schoolA->ids['asha'];
        // The set was issued to an updated app with every kind; an app that has not opted in sees only the number question, as before.
        $this->contract = PracticeContract::LEGACY;
        $this->signIn('asha');
        $legacy = $this->getJson(self::BASE."/learners/$learner/daily-sets/{$this->set['id']}")->assertOk()->json('data');
        $this->assertCount(1, $legacy['items']);
        $this->assertSame(['id', 'item_id', 'topic_id', 'source_version_id', 'estimated_seconds', 'definition', 'review'], array_keys($legacy['items'][0]));
        $this->assertSame(['kind' => 'number', 'prompt' => 'QUESTION-number', 'explanation' => 'EXPLAIN-number', 'answers' => ['7311'], 'options' => []],
            array_intersect_key($legacy['items'][0]['definition'], array_flip(['kind', 'prompt', 'explanation', 'options', 'answers'])));
        $this->assertSame(60, $legacy['estimated_seconds']);
        $path = self::BASE."/learners/$learner/daily-sets/{$this->set['id']}/entries";
        $result = $this->postJson("$path/{$this->entries['number']}/review", ['operation_id' => (string) Str::uuid(), 'answer' => '7311', 'rating' => 3])->assertOk()->json('data');
        $this->assertSame(['id', 'entry_id', 'correct', 'schedule', 'reviewed_on', 'streak_days'], array_keys($result));
        $this->assertTrue($result['correct']);
        // New kinds are not there for a legacy client, even by id.
        $this->postJson("$path/{$this->entries['matched']}/review", ['operation_id' => (string) Str::uuid(), 'answer' => 'Photosynthesis secret', 'rating' => 3])->assertNotFound();
        $this->post("$path/{$this->entries['audio']}/review", ['operation_id' => (string) Str::uuid(), 'rating' => 3, 'audio' => $this->audioFile()], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->assertSame(1, PracticeReview::count());
    }

    public function test_a_legacy_client_is_issued_only_choice_and_number_questions(): void
    {
        $this->approve();
        $this->signIn('tara');
        $this->postJson(self::BASE."/groups/{$this->group}/bank", ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'topic_id' => $this->nodes[$this->keys[1]],
            'content_version_id' => $this->source, 'estimated_seconds' => 60, 'active' => true, 'definition' => ['answers' => ['sun']] + $this->base('short_text', 'text')])->assertOk();
        // The teacher app on the legacy contract sees the questions it can edit; an updated one sees every kind.
        $this->contract = PracticeContract::LEGACY;
        $this->signIn('tara');
        $this->assertSame(['number'], array_column(array_column($this->getJson(self::BASE."/groups/{$this->group}/bank")->assertOk()->json('data.items'), 'definition'), 'kind'));
        $this->signIn('asha');
        $learner = $this->schoolA->ids['asha'];
        $set = $this->postJson(self::BASE."/learners/$learner/groups/{$this->group}/daily-sets", ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10'])->assertOk()->json('data');
        $this->assertSame(['number'], array_column(array_column($set['items'], 'definition'), 'kind'));
        $this->assertSame(1, PracticeSetEntry::count(), 'the short written question is not even in the stored set');
        $this->contract = PracticeContract::ANSWER_FREE;
        $this->signIn('tara');
        $this->assertSame(['short_text', 'number'], array_column(array_column($this->getJson(self::BASE."/groups/{$this->group}/bank")->assertOk()->json('data.items'), 'definition'), 'kind'));
    }

    public function test_switching_off_the_legacy_contract_refuses_legacy_practice_requests(): void
    {
        $this->approve();
        config(['practice.legacy_set_contract' => false]);
        $learner = $this->schoolA->ids['asha'];
        $this->contract = PracticeContract::LEGACY;
        $this->signIn('asha');
        $issue = ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10'];
        $this->postJson(self::BASE."/learners/$learner/groups/{$this->group}/daily-sets", $issue)->assertStatus(426);
        $this->contract = PracticeContract::ANSWER_FREE;
        $this->signIn('asha');
        $set = $this->postJson(self::BASE."/learners/$learner/groups/{$this->group}/daily-sets", $issue)->assertOk()->json('data');
        $this->contract = PracticeContract::LEGACY;
        $this->signIn('asha');
        $this->getJson(self::BASE."/learners/$learner/daily-sets/{$set['id']}")->assertStatus(426);
        $this->postJson(self::BASE."/learners/$learner/daily-sets/{$set['id']}/entries/{$set['items'][0]['id']}/review", ['operation_id' => (string) Str::uuid(), 'answer' => '0.5', 'rating' => 3])
            ->assertStatus(426);
        $this->assertSame(0, PracticeReview::count());
    }
}
