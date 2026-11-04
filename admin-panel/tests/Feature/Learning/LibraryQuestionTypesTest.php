<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Library\LibraryDiagram;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\Library\LibraryContent;
use App\Services\Learning\PracticeMedia;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Support\SyntheticCleanScanner;
use Tests\TestCase;

/**
 * Library questions of the new types (Action Plan 3.11) are written at HQ
 * with the same checks as a school's own, and reach learners without answers.
 */
class LibraryQuestionTypesTest extends TestCase
{
    use UsesContentLibrary, UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        Storage::fake(PracticeMedia::SCHOOL_DISK);
        Storage::fake(PracticeMedia::LIBRARY_DISK);
        $this->app->instance(UploadScanner::class, new SyntheticCleanScanner);
        $standard = $this->standard('MATH-SP');
        DB::table('curriculum_plans')->where('school_id', 1)->update(['standard_id' => $standard->id,
            'standard_snapshot' => json_encode(['id' => $standard->id, 'framework' => 'CAPS', 'code' => 'MATH-SP', 'name' => $standard->name])]);
    }

    public function test_hq_writes_labelling_and_step_questions_that_reach_learners_without_answers(): void
    {
        $topic = $this->libraryTopic('Shapes');
        $this->publishedItem($topic, ['title' => 'Label the triangle', 'question_kind' => 'diagram_label', 'prompt' => 'Label the triangle.', 'explanation' => 'LIBRARY-EXPLAIN-diagram',
            'answers' => null, 'diagram' => UploadedFile::fake()->image('triangle.png', 300, 200), 'regions' => "p1: 10, 90\np2: 50, 5",
            'labels' => "base: Base\napex: Apex\nside: Side", 'mapping' => "p1: base\np2: apex"]);
        $this->publishedItem($topic, ['title' => 'Two steps', 'question_kind' => 'steps', 'prompt' => 'Work out 2 x (3 + 4).', 'explanation' => 'LIBRARY-EXPLAIN-steps',
            'answers' => null, 'steps' => "Brackets | 7\nThen multiply | 14"]);
        $this->assertSame(1, LibraryDiagram::count());
        $this->switchLibrary(1, true);

        $set = $this->issue();
        $raw = json_encode($set);
        foreach (['LIBRARY-EXPLAIN', '"answers"', '"mapping"', '"14"'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
        $items = collect($set['items'])->keyBy(fn ($item) => $item['question']['kind']);
        $this->assertSame(['width' => 300, 'height' => 200], $items['diagram_label']['question']['diagram']);
        $bytes = app(DailyPractice::class)->diagram($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $items['diagram_label']['id']);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($bytes)[2]);
        $this->refuses(fn () => app(DailyPractice::class)->diagram($this->actor('asha'), $this->schoolA->ids['ben'], $set['id'], $items['diagram_label']['id']), 404);

        $result = app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $items['steps']['id'],
            ['operation_id' => (string) Str::uuid(), 'parts' => ['s1' => '7', 's2' => '15'], 'rating' => 3]);
        $this->assertSame([false, 1, 2, 'LIBRARY-EXPLAIN-steps'], [$result['correct'], $result['achieved_units'], $result['maximum_units'], $result['solution']['explanation']]);
        $evidence = MasteryEvidence::sole();
        $this->assertSame(['library', 1, 2], [$evidence->content_source, $evidence->achieved_units, $evidence->maximum_units]);
    }

    public function test_library_questions_are_checked_like_school_ones_and_library_pictures_are_written_only_from_hq(): void
    {
        $topic = $this->libraryTopic('Shapes');
        try {
            $this->draftItem($topic, null, ['question_kind' => 'diagram_label', 'answers' => null, 'regions' => 'p1: 10, 10', 'labels' => 'a: A', 'mapping' => 'p1: a',
                'diagram_id' => (string) Str::uuid()]);
            $this->fail('A labelling question needs a stored library picture.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        try {
            $this->draftItem($topic, null, ['question_kind' => 'audio', 'answers' => null, 'max_seconds' => 5]);
            $this->fail('A recording limit below ten seconds is refused.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        $this->assertSame('A spoken answer', LibraryContent::QUESTION_KINDS['audio']);
        app(CurrentSchool::class)->run(1, fn () => $this->refuses(fn () => app(PracticeMedia::class)->storeLibraryDiagram($this->actor(), UploadedFile::fake()->image('x.png', 10, 10)), 403));
        $this->assertSame(0, LibraryDiagram::count());
    }
}
