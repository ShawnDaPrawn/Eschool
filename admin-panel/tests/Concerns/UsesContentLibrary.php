<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\SubjectStandard;
use App\Models\Library\LibraryFramework;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\LibraryReviewers;
use App\Services\Learning\Library\LibrarySchools;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/**
 * Builds platform library content the way HQ does (Action Plan 3.3): through
 * the library services, as platform staff, with no school set.
 */
trait UsesContentLibrary
{
    /** Every table of the platform library: none may hold a school_id. */
    protected const LIBRARY_TABLES = ['library_frameworks', 'library_sources', 'library_topics', 'library_topic_prerequisites', 'library_items',
        'library_item_versions', 'library_reviews', 'library_reviewers'];

    private array $libraryStaff = [];

    protected function libraryStaff(string $role): User
    {
        return $this->libraryStaff[$role] ??= app(CurrentSchool::class)->acrossSchools(function () use ($role) {
            $user = User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Librarian', 'email' => $role.'@library.example', 'mobile' => '',
                'password' => 'Synthetic-library-password-582!', 'status' => 1]);
            PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
            PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHours(4)]);

            return $user;
        });
    }

    /** Run as HQ: no school set, whatever the test had set. */
    protected function asHq(callable $callback): mixed
    {
        return app(CurrentSchool::class)->acrossSchools($callback);
    }

    protected function standard(string $code = 'MATH-SP', int $from = 7, int $to = 9): SubjectStandard
    {
        return SubjectStandard::query()->create(['framework' => 'CAPS', 'code' => $code, 'name' => 'Mathematics '.$code, 'phase' => 'senior', 'grade_from' => $from,
            'grade_to' => $to, 'version' => '2011', 'source_url' => 'https://www.education.gov.za/synthetic', 'effective_on' => '2012-01-01',
            'fingerprint' => hash('sha256', $code), 'published_at' => now()]);
    }

    protected function grade(string $code = '7'): int
    {
        return (int) GradeLevel::query()->where('code', $code)->value('id');
    }

    protected function framework(): LibraryFramework
    {
        return $this->asHq(fn () => LibraryFramework::query()->where('code', 'CAPS')->first()
            ?? app(LibraryCatalogue::class)->createFramework($this->libraryStaff('content'), ['code' => 'CAPS', 'name' => 'Curriculum and Assessment Policy Statement']));
    }

    protected function libraryTopic(string $name, string $code = 'MATH-SP', string $grade = '7'): LibraryTopic
    {
        $framework = $this->framework();

        return $this->asHq(fn () => app(LibraryCatalogue::class)->createTopic($this->libraryStaff('content'), $framework->id,
            ['subject_code' => $code, 'grade_level_id' => $this->grade($grade), 'name' => $name]));
    }

    protected function librarySource(string $kind = 'original'): LibrarySource
    {
        return $this->asHq(fn () => app(LibraryCatalogue::class)->createSource($this->libraryStaff('content'), ['title' => 'Navigate Ed maths bank '.$kind, 'kind' => $kind,
            'licence' => 'All rights reserved, Navigate Ed', 'attribution' => 'Navigate Ed content team']));
    }

    protected function question(array $changes = []): array
    {
        return $changes + ['kind' => 'practice_question', 'title' => 'Half as a decimal', 'question_kind' => 'number', 'prompt' => 'Write one half as a decimal.',
            'explanation' => 'One divided by two is 0.5.', 'answers' => '0.5', 'estimated_seconds' => 120];
    }

    protected function draftItem(LibraryTopic $topic, ?LibrarySource $source = null, array $changes = []): LibraryItem
    {
        $source ??= $this->librarySource();

        return $this->asHq(fn () => app(LibraryItems::class)->create($this->libraryStaff('content'), $this->question($changes + ['library_topic_id' => $topic->id, 'library_source_id' => $source->id])));
    }

    protected function assignReviewer(string $code = 'MATH-SP', string $role = 'reviewer'): void
    {
        $framework = $this->framework();
        $this->asHq(fn () => app(LibraryReviewers::class)->assign($this->libraryStaff('content'), ['user_id' => $this->libraryStaff($role)->id,
            'library_framework_id' => $framework->id, 'subject_code' => $code]));
    }

    /** Draft, review and publish: what learners can then see. */
    protected function publishedItem(LibraryTopic $topic, array $changes = []): LibraryItem
    {
        $this->assignReviewer($topic->subject_code);
        $item = $this->draftItem($topic, null, $changes);

        return $this->asHq(function () use ($item) {
            $items = app(LibraryItems::class);
            $content = $this->libraryStaff('content');
            $version = $items->submit($content, $item->id, ['revision' => $item->fresh()->revision]);
            $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']);
            $items->publish($content, $item->id, ['version_id' => $version->id, 'revision' => $item->fresh()->revision]);

            return $item->fresh();
        });
    }

    protected function switchLibrary(int $schoolId, bool $enabled): void
    {
        $this->asHq(fn () => app(LibrarySchools::class)->switch($this->libraryStaff('content'), $schoolId, ['enabled' => $enabled, 'reason' => 'Synthetic library roll-out decision']));
    }

    /** @return array<string, int> */
    protected function libraryCounts(): array
    {
        return array_combine(self::LIBRARY_TABLES, array_map(fn ($table) => DB::table($table)->count(), self::LIBRARY_TABLES));
    }
}
