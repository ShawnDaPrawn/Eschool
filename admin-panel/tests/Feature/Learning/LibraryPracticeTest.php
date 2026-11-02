<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeMemory;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibrarySchoolSetting;
use App\Models\Library\LibraryTopic;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * Daily practice reads the platform library after the school's own material,
 * only where the school's switch is on (Action Plan 3.3), and the mastery
 * evidence says where each question came from.
 * engineering/specs/learning/content-library.md.
 */
class LibraryPracticeTest extends TestCase
{
    use UsesContentLibrary, UsesPracticeScenario;

    private LibraryTopic $fractions;

    private LibraryTopic $decimals;

    /** @var array<string, LibraryItem> */
    private array $items = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        // School A teaches the CAPS Senior Phase maths subject: the exam coach reads `framework` from this snapshot.
        $standard = $this->standard('MATH-SP');
        DB::table('curriculum_plans')->where('school_id', 1)->update(['standard_id' => $standard->id,
            'standard_snapshot' => json_encode(['id' => $standard->id, 'framework' => 'CAPS', 'code' => 'MATH-SP', 'name' => $standard->name])]);
        $this->fractions = $this->libraryTopic('Fractions');
        $this->decimals = $this->libraryTopic('Decimals');
        $this->asHq(fn () => app(LibraryCatalogue::class)->setPrerequisites($this->libraryStaff('content'), $this->decimals->id, ['prerequisite_ids' => [$this->fractions->id]]));
        // Created in id order Decimals, Fractions: the prerequisite graph, not the id, puts Fractions first.
        $this->items['decimals'] = $this->publishedItem($this->decimals, ['title' => 'Tenths', 'prompt' => 'Write three tenths as a decimal.', 'answers' => '0.3']);
        $this->items['fractions'] = $this->publishedItem($this->fractions);
        // A draft is never offered.
        $this->draftItem($this->fractions, null, ['title' => 'Unreviewed draft']);
    }

    public function test_with_the_switch_off_the_library_is_never_offered(): void
    {
        $this->refuses(fn () => $this->issue(), 409);
        $this->assertSame(0, PracticeSetEntry::count());
        $this->assertSame(0, MasteryTopic::whereNotNull('library_topic_id')->count());
        $this->assertFalse((bool) LibrarySchoolSetting::query()->value('enabled'), 'off by default');
    }

    public function test_with_the_switch_on_a_school_with_no_items_of_its_own_gets_library_candidates_and_school_b_does_not_affect_school_a(): void
    {
        // School B's switch does nothing for School A.
        $this->switchLibrary(2, true);
        $this->refuses(fn () => $this->issue(), 409);
        $this->assertSame(0, PracticeSetEntry::count());

        $this->switchLibrary(1, true);
        $set = $this->issue();
        $this->assertCount(2, $set['items']);
        $mirrors = MasteryTopic::whereNotNull('library_topic_id')->pluck('id', 'library_topic_id');
        $this->assertCount(2, $mirrors);
        $this->assertSame([$this->items['fractions']->id, $this->items['decimals']->id], array_column($set['items'], 'item_id'), 'prerequisites first');
        $this->assertSame([$this->items['fractions']->published_version_id, $this->items['decimals']->published_version_id], array_column($set['items'], 'source_version_id'));
        $this->assertSame([$mirrors[$this->fractions->id], $mirrors[$this->decimals->id]], array_column($set['items'], 'topic_id'));
        $this->assertSame(['0.5'], $set['items'][0]['definition']['answers']);
        $this->assertSame(240, $set['estimated_seconds']);
        $this->assertSame(2, PracticeSetEntry::whereNotNull('library_item_version_id')->whereNull('practice_item_version_id')->count());
        // Mirror nodes stay out of the school's own graph.
        $this->assertSame([], array_values(array_intersect(app(TopicGraph::class)->nodes($this->subject)->pluck('id')->all(), $mirrors->all())));
        // Each school's switch is its own row; switching B off leaves A's set readable.
        $this->switchLibrary(2, false);
        $this->assertSame([1 => 1, 2 => 0], DB::table('library_school_settings')->orderBy('school_id')->pluck('enabled', 'school_id')->map(fn ($v) => (int) $v)->all());
        $this->assertCount(2, app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'])['items']);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => MasteryTopic::whereNotNull('library_topic_id')->count()), 'nothing mirrored into School B');
    }

    public function test_school_material_comes_first_then_the_library(): void
    {
        $this->switchLibrary(1, true);
        $this->approve();
        $set = $this->issue();
        $this->assertCount(3, $set['items']);
        $entries = PracticeSetEntry::orderBy('position')->get();
        $this->assertNotNull($entries[0]->practice_item_version_id);
        $this->assertNull($entries[0]->library_item_version_id);
        $this->assertSame([$this->items['fractions']->published_version_id, $this->items['decimals']->published_version_id], $entries->slice(1)->pluck('library_item_version_id')->values()->all());
    }

    public function test_evidence_records_its_source_and_the_library_version(): void
    {
        $this->switchLibrary(1, true);
        $this->approve();
        $before = $this->libraryCounts();
        $set = $this->issue();
        $this->review($set);
        $library = app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $set['items'][1]['id'],
            ['operation_id' => (string) Str::uuid(), 'answer' => '.500', 'rating' => 3]);
        $this->assertTrue($library['correct']);

        $school = MasteryEvidence::where('content_source', 'school')->sole();
        $this->assertNull($school->library_item_version_id);
        $this->assertSame($this->nodes[$this->keys[1]], (int) $school->topic_id);
        $fromLibrary = MasteryEvidence::where('content_source', 'library')->sole();
        $this->assertSame($this->items['fractions']->published_version_id, (int) $fromLibrary->library_item_version_id);
        $this->assertSame((int) MasteryTopic::where('library_topic_id', $this->fractions->id)->value('id'), (int) $fromLibrary->topic_id);
        $this->assertSame(['practice_review'], MasteryEvidence::pluck('source_type')->unique()->values()->all());
        $this->assertSame($this->items['fractions']->id, (int) PracticeMemory::whereNotNull('library_item_id')->sole()->library_item_id);
        // The school's practice wrote only school rows: the library is exactly as it was.
        $this->assertSame($before, $this->libraryCounts());
    }

    public function test_switching_the_library_off_withdraws_issued_library_questions(): void
    {
        $this->switchLibrary(1, true);
        $set = $this->issue();
        $this->switchLibrary(1, false);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 404);
        $this->refuses(fn () => $this->review($set), 404);
        $this->assertSame(0, MasteryEvidence::count());
    }
}
