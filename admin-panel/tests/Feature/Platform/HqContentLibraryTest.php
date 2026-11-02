<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Library\LibraryFramework;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Models\Platform\AuditLog;
use App\Models\User;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\LibraryReviewers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The platform content library in HQ (Action Plan 3.3, SA.16): Content staff
 * build frameworks, topics and items; a reviewer for the subject approves;
 * only an approved version is published, and a published one never changes.
 * engineering/specs/learning/content-library.md.
 */
class HqContentLibraryTest extends TestCase
{
    use UsesContentLibrary, UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        $this->standard('MATH-SP');
        $this->standard('ENG-SP');
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected a refusal.');
        } catch (HttpException $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    private function invalid(callable $call, string $field): void
    {
        try {
            $call();
            $this->fail('Expected a validation error.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors());
        }
    }

    public function test_content_staff_build_a_framework_and_publish_only_after_a_subject_reviewer_approves(): void
    {
        $this->actingAs($this->libraryStaff('content'));
        $this->post('/saas/library/frameworks', ['code' => 'CAPS', 'name' => 'Curriculum and Assessment Policy Statement'])->assertRedirect()->assertSessionHasNoErrors();
        $framework = LibraryFramework::query()->sole();
        $this->post('/saas/library/sources', ['title' => 'Navigate Ed maths bank', 'kind' => 'original', 'licence' => 'All rights reserved, Navigate Ed',
            'attribution' => 'Navigate Ed content team', 'url' => 'https://navigate.example/bank'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/saas/library/frameworks/'.$framework->id.'/topics', ['subject_code' => 'MATH-SP', 'grade_level_id' => $this->grade('7'), 'name' => 'Fractions'])
            ->assertRedirect()->assertSessionHasNoErrors();
        // A subject code outside the framework's catalogue for the grade is refused.
        $this->post('/saas/library/frameworks/'.$framework->id.'/topics', ['subject_code' => 'MATH-SP', 'grade_level_id' => $this->grade('11'), 'name' => 'Calculus'])
            ->assertSessionHasErrors('subject_code');
        $topic = $framework->topics()->sole();
        $source = DB::table('library_sources')->value('id');
        $this->post('/saas/library/items', $this->question(['library_topic_id' => $topic->id, 'library_source_id' => $source]))->assertRedirect()->assertSessionHasNoErrors();
        // A past-paper question needs a source that can licence it.
        $this->post('/saas/library/items', $this->question(['library_topic_id' => $topic->id, 'library_source_id' => $source, 'kind' => 'past_paper_question',
            'paper_year' => 2024, 'paper_code' => 'P1', 'question_number' => '1.1']))->assertSessionHasErrors('library_source_id');
        $item = LibraryItem::query()->sole();
        $this->assertSame(['draft'], LibraryItemVersion::query()->pluck('state')->all());
        $this->get('/saas/library')->assertOk()->assertSee('CAPS')->assertSee('Navigate Ed maths bank')->assertSee('Jacaranda Demo School');
        $this->get('/saas/library/frameworks/'.$framework->id)->assertOk()->assertSee('Fractions')->assertSee('Half as a decimal');
        $this->get('/saas/library/items/'.$item->id)->assertOk()->assertSee('Write one half as a decimal.')->assertSee('Send for review');

        $this->post('/saas/library/items/'.$item->id.'/submit', ['revision' => $item->revision])->assertRedirect()->assertSessionHasNoErrors();
        $version = LibraryItemVersion::query()->sole();
        $this->assertSame('in_review', $version->state);
        // Only an approved version can be published.
        $this->post('/saas/library/items/'.$item->id.'/publish', ['version_id' => $version->id, 'revision' => $item->fresh()->revision])->assertStatus(409);
        $this->post('/saas/library/reviewers', ['user_id' => $this->libraryStaff('reviewer')->id, 'library_framework_id' => $framework->id, 'subject_code' => 'MATH-SP'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($this->libraryStaff('reviewer'));
        $this->get('/saas/library/reviews')->assertOk()->assertSee('Half as a decimal')->assertSee('Approve');
        $this->post('/saas/library/versions/'.$version->id.'/review', ['decision' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved', $version->fresh()->state);

        $this->actingAs($this->libraryStaff('content'));
        $this->post('/saas/library/items/'.$item->id.'/publish', ['version_id' => $version->id, 'revision' => $item->fresh()->revision])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('published', $version->fresh()->state);
        $this->assertSame($version->id, $item->fresh()->published_version_id);

        $this->assertEqualsCanonicalizing(['library_framework_created', 'library_source_created', 'library_topic_created', 'library_item_created', 'library_submitted',
            'library_reviewer_assigned', 'library_reviewed', 'library_published'], AuditLog::query()->where('action', 'like', 'library_%')->pluck('action')->all());
        $this->assertSame(0, AuditLog::query()->where('action', 'like', 'library_%')->whereNotNull('school_id')->count(), 'library actions belong to no school');
    }

    public function test_a_cycle_in_prerequisites_is_refused(): void
    {
        $content = $this->libraryStaff('content');
        $numbers = $this->libraryTopic('Whole numbers');
        $fractions = $this->libraryTopic('Fractions');
        $decimals = $this->libraryTopic('Decimals');
        $grammar = $this->libraryTopic('Grammar', 'ENG-SP');
        $catalogue = app(LibraryCatalogue::class);
        $catalogue->setPrerequisites($content, $fractions->id, ['prerequisite_ids' => [$numbers->id]]);
        $catalogue->setPrerequisites($content, $decimals->id, ['prerequisite_ids' => [$fractions->id]]);
        $before = LibraryTopicPrerequisite::query()->orderBy('id')->get(['library_topic_id', 'prerequisite_id'])->toArray();

        $this->invalid(fn () => $catalogue->setPrerequisites($content, $numbers->id, ['prerequisite_ids' => [$decimals->id]]), 'prerequisite_ids');
        $this->invalid(fn () => $catalogue->setPrerequisites($content, $numbers->id, ['prerequisite_ids' => [$numbers->id]]), 'prerequisite_ids');
        $this->invalid(fn () => $catalogue->setPrerequisites($content, $numbers->id, ['prerequisite_ids' => [$grammar->id]]), 'prerequisite_ids');
        $this->actingAs($content)->put('/saas/library/topics/'.$numbers->id.'/prerequisites', ['prerequisite_ids' => [$decimals->id]])->assertSessionHasErrors('prerequisite_ids');
        $this->assertSame($before, LibraryTopicPrerequisite::query()->orderBy('id')->get(['library_topic_id', 'prerequisite_id'])->toArray());

        // Replacing an edge is fine; an empty list clears them.
        $this->put('/saas/library/topics/'.$decimals->id.'/prerequisites', ['prerequisite_ids' => [$numbers->id, $fractions->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/saas/library/topics/'.$fractions->id.'/prerequisites', [])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, LibraryTopicPrerequisite::query()->where('library_topic_id', $decimals->id)->count());
        $this->assertSame(0, LibraryTopicPrerequisite::query()->where('library_topic_id', $fractions->id)->count());
        $this->assertSame(['library_prerequisites_changed'], AuditLog::query()->where('action', 'like', 'library_prereq%')->distinct()->pluck('action')->all());
    }

    public function test_a_published_version_cannot_be_edited(): void
    {
        $item = $this->publishedItem($this->libraryTopic('Fractions'));
        $published = LibraryItemVersion::query()->sole();
        $original = $published->content;

        $this->refuses(fn () => $published->update(['content' => ['definition' => ['kind' => 'number']]]), 409);
        $this->refuses(fn () => $published->update(['state' => 'draft']), 409);
        $this->refuses(fn () => $published->delete(), 409);
        $this->assertSame($original, $published->fresh()->content);

        // Editing the item starts version 2 as a draft; version 1 stays what learners see.
        $draft = app(LibraryItems::class)->saveDraft($this->libraryStaff('content'), $item->id, $this->question(['title' => 'Half as a decimal, revised', 'answers' => '.5', 'revision' => $item->revision]));
        $this->assertSame(2, $draft->number);
        $this->assertSame('draft', $draft->state);
        $this->assertSame($original, $published->fresh()->content);
        $this->assertSame('published', $published->fresh()->state);
        $this->assertSame($published->id, $item->fresh()->published_version_id);
        // An approved (not yet published) version cannot change its content either.
        $items = app(LibraryItems::class);
        $items->submit($this->libraryStaff('content'), $item->id, ['revision' => $item->fresh()->revision]);
        $items->review($this->libraryStaff('reviewer'), $draft->id, ['decision' => 'approved']);
        $this->refuses(fn () => $draft->fresh()->update(['estimated_seconds' => 200]), 409);
        // A stale revision is refused.
        $this->refuses(fn () => $items->publish($this->libraryStaff('content'), $item->id, ['version_id' => $draft->id, 'revision' => 1]), 409);
    }

    public function test_only_a_reviewer_for_that_subject_can_approve(): void
    {
        $item = $this->draftItem($this->libraryTopic('Fractions'));
        $items = app(LibraryItems::class);
        $version = $items->submit($this->libraryStaff('content'), $item->id, ['revision' => $item->revision]);
        // Assigned to English only: refused, nothing written.
        $this->assignReviewer('ENG-SP');
        $this->refuses(fn () => $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']), 403);
        $this->actingAs($this->libraryStaff('reviewer'))->get('/saas/library/reviews')->assertOk()->assertSee('You are not a reviewer for this subject.')->assertDontSee('Approve</button>', false);
        $this->post('/saas/library/versions/'.$version->id.'/review', ['decision' => 'approved'])->assertForbidden();
        // An Owner holds every ability but still needs the subject, and never reviews their own submission.
        $owner = $this->libraryStaff('owner');
        $this->refuses(fn () => $items->review($owner, $version->id, ['decision' => 'approved']), 403);
        $self = $this->draftItem($this->libraryTopic('Decimals'));
        app(LibraryReviewers::class)->assign($owner, ['user_id' => $owner->id, 'library_framework_id' => $this->framework()->id, 'subject_code' => 'MATH-SP']);
        $own = $items->submit($owner, $self->id, ['revision' => $self->revision]);
        $this->refuses(fn () => $items->review($owner, $own->id, ['decision' => 'approved']), 403);
        $this->assertSame(0, LibraryReview::query()->count());
        $this->assertSame(['in_review', 'in_review'], LibraryItemVersion::query()->orderBy('id')->pluck('state')->all());

        // The Maths reviewer may decide; a rejection needs a reason.
        $this->assignReviewer('MATH-SP');
        $this->invalid(fn () => $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'rejected']), 'reason');
        $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'rejected', 'reason' => 'The explanation should show the division step.']);
        $this->assertSame('rejected', $version->fresh()->state);
        $this->assertSame('The explanation should show the division step.', LibraryReview::query()->sole()->reason);
        $this->refuses(fn () => $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']), 409);
        // A rejected version is kept; editing starts a new draft.
        $this->assertSame(2, $items->saveDraft($this->libraryStaff('content'), $item->id, $this->question(['revision' => $item->fresh()->revision]))->number);
        // Only staff whose role reviews can be assigned.
        $this->invalid(fn () => app(LibraryReviewers::class)->assign($this->libraryStaff('content'),
            ['user_id' => $this->libraryStaff('support')->id, 'library_framework_id' => $this->framework()->id, 'subject_code' => 'MATH-SP']), 'user_id');
    }

    public function test_wrong_roles_are_refused_and_nothing_is_written(): void
    {
        $topic = $this->libraryTopic('Fractions');
        $item = $this->draftItem($topic);
        $version = app(LibraryItems::class)->submit($this->libraryStaff('content'), $item->id, ['revision' => $item->revision]);
        $framework = $this->framework();
        $before = $this->libraryCounts();
        $manage = function () use ($framework, $topic, $item, $version) {
            $this->get('/saas/library')->assertForbidden();
            $this->get('/saas/library/frameworks/'.$framework->id)->assertForbidden();
            $this->get('/saas/library/items/'.$item->id)->assertForbidden();
            $this->post('/saas/library/frameworks', ['code' => 'IEB', 'name' => 'Independent Examinations Board'])->assertForbidden();
            $this->post('/saas/library/sources', ['title' => 'Never added', 'kind' => 'original', 'licence' => 'None', 'attribution' => 'Nobody'])->assertForbidden();
            $this->post('/saas/library/frameworks/'.$framework->id.'/topics', ['subject_code' => 'MATH-SP', 'grade_level_id' => $this->grade('8'), 'name' => 'Never added'])->assertForbidden();
            $this->put('/saas/library/topics/'.$topic->id.'/prerequisites', ['prerequisite_ids' => []])->assertForbidden();
            $this->post('/saas/library/items', $this->question(['library_topic_id' => $topic->id, 'library_source_id' => $item->library_source_id]))->assertForbidden();
            $this->put('/saas/library/items/'.$item->id.'/draft', $this->question(['revision' => $item->fresh()->revision]))->assertForbidden();
            $this->post('/saas/library/items/'.$item->id.'/submit', ['revision' => $item->fresh()->revision])->assertForbidden();
            $this->post('/saas/library/items/'.$item->id.'/publish', ['version_id' => $version->id, 'revision' => $item->fresh()->revision])->assertForbidden();
            $this->post('/saas/library/reviewers', ['user_id' => $this->libraryStaff('reviewer')->id, 'library_framework_id' => $framework->id, 'subject_code' => 'MATH-SP'])->assertForbidden();
            $this->put('/saas/library/schools/1', ['enabled' => 1, 'reason' => 'Should never be switched'])->assertForbidden();
        };
        $review = function () use ($version) {
            $this->get('/saas/library/reviews')->assertForbidden();
            $this->post('/saas/library/versions/'.$version->id.'/review', ['decision' => 'approved'])->assertForbidden();
        };
        foreach (['operations', 'support', 'finance', 'sales'] as $role) {
            $this->actingAs($this->libraryStaff($role));
            $manage();
            $review();
        }
        // Content staff do not review; reviewers do not manage.
        $this->actingAs($this->libraryStaff('content'));
        $review();
        $this->assignReviewer();
        $this->actingAs($this->libraryStaff('reviewer'));
        $manage();
        $this->get('/saas/library/reviews')->assertOk();

        $this->assertSame(array_replace($before, ['library_reviewers' => $before['library_reviewers'] + 1]), $this->libraryCounts());
        $this->assertSame('in_review', $version->fresh()->state);
        $this->assertSame(0, DB::table('library_school_settings')->count());
    }

    public function test_school_accounts_cannot_reach_the_library(): void
    {
        $topic = $this->libraryTopic('Fractions');
        $before = $this->libraryCounts();
        foreach ([$this->superAdmin(), User::query()->findOrFail($this->schoolA->ids['tara_user']), User::query()->findOrFail($this->schoolB->ids['tara_user'])] as $person) {
            $this->actingAs($person);
            foreach (['/saas/library', '/saas/library/reviews', '/saas/library/frameworks/'.$topic->library_framework_id] as $page) {
                $this->get($page)->assertForbidden();
            }
            $this->post('/saas/library/frameworks', ['code' => 'IEB', 'name' => 'Independent Examinations Board'])->assertForbidden();
            $this->put('/saas/library/schools/1', ['enabled' => 1, 'reason' => 'Should never be switched'])->assertForbidden();
        }
        // Platform staff acting inside a school are refused too.
        $this->refuses(fn () => app(CurrentSchool::class)->run(1, fn () => app(LibraryCatalogue::class)->createFramework($this->libraryStaff('content'),
            ['code' => 'IEB', 'name' => 'Independent Examinations Board'])), 403);
        $this->assertSame($before, $this->libraryCounts());
        $this->assertSame(0, DB::table('library_school_settings')->count());
    }

    public function test_library_tables_hold_no_school_id_and_no_school_row_is_written_into_them(): void
    {
        foreach (self::LIBRARY_TABLES as $table) {
            $this->assertNotContains('school_id', Schema::getColumnListing($table), $table);
            $this->assertArrayHasKey($table, SchoolOwnedTables::GLOBAL, $table);
        }
        $this->assertContains('library_school_settings', SchoolOwnedTables::REQUIRED);
        $item = $this->publishedItem($this->libraryTopic('Fractions'));
        $before = $this->libraryCounts();
        // From inside a school, no library row can be created, changed or deleted, whatever the path.
        foreach ([1, 2] as $school) {
            app(CurrentSchool::class)->run($school, function () use ($item) {
                $this->refuses(fn () => LibraryFramework::query()->create(['code' => 'IEB', 'name' => 'Independent Examinations Board', 'created_by' => $this->libraryStaff('content')->id]), 403);
                $this->refuses(fn () => LibraryItem::query()->findOrFail($item->id)->update(['title' => 'Changed by a school']), 403);
                $this->refuses(fn () => LibraryTopicPrerequisite::query()->create(['library_topic_id' => $item->library_topic_id, 'prerequisite_id' => $item->library_topic_id, 'created_by' => 1]), 403);
                $this->refuses(fn () => LibraryFramework::query()->firstOrFail()->delete(), 403);
            });
        }
        $this->assertSame($before, $this->libraryCounts());
        $this->assertSame('Half as a decimal', $item->fresh()->title);
    }

    public function test_switching_the_library_for_one_school_leaves_the_other_alone(): void
    {
        $this->actingAs($this->libraryStaff('content'));
        $this->put('/saas/library/schools/2', ['enabled' => 1, 'reason' => 'Baobab High pilots the library'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([2 => 1], DB::table('library_school_settings')->pluck('enabled', 'school_id')->map(fn ($v) => (int) $v)->all());
        $this->get('/saas/library')->assertOk()->assertSee('Switch off')->assertSee('Switch on');
        // A reason is required; an unknown school is not found; nothing changes for either.
        $this->put('/saas/library/schools/1', ['enabled' => 1])->assertSessionHasErrors('reason');
        $this->put('/saas/library/schools/999', ['enabled' => 1, 'reason' => 'No such school exists here'])->assertNotFound();
        $this->assertSame([2 => 1], DB::table('library_school_settings')->pluck('enabled', 'school_id')->map(fn ($v) => (int) $v)->all());
        $audit = AuditLog::query()->where('action', 'library_school_switched')->sole();
        $this->assertSame(2, (int) $audit->school_id);
        $this->assertEqualsCanonicalizing(['enabled' => true, 'reason' => 'Baobab High pilots the library', 'changed_by' => $this->libraryStaff('content')->id], $audit->after);
    }
}
