<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\PracticeMemory;
use App\Models\Learning\PracticeReview;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\PracticeBank;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class DailyPracticeTest extends TestCase
{
    use UsesMasteryScenario;

    private int $source;

    private ContentItem $content;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->actor('asha')->forceFill(['password_change_required' => false])->save();
        $this->actor('tara')->forceFill(['password_change_required' => false])->save();
        $this->actor('farid')->forceFill(['email_verified_at' => now(), 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $group = TeachingGroup::findOrFail($this->group);
        $this->content = ContentItem::create(['author_id' => $this->actor()->id, 'subject_id' => $this->subject, 'grade_level_id' => $group->grade_level_id,
            'session_year_id' => $this->year, 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'active' => true]);
        $source = ContentSource::create(['content_item_id' => $this->content->id, 'uploaded_by' => $this->actor()->id, 'name' => 'Synthetic approved fractions.md', 'path' => 'synthetic/no-file.md',
            'mime' => 'text/markdown', 'extension' => 'md', 'bytes' => 1, 'sha256' => str_repeat('b', 64), 'scan_state' => 'clean']);
        $version = ContentVersion::create(['content_item_id' => $this->content->id, 'content_source_id' => $source->id, 'created_by' => $this->actor()->id, 'number' => 1,
            'state' => 'published', 'metadata' => ['group_ids' => [$this->group], 'title' => 'Synthetic fractions'], 'markdown' => '# Fractions', 'generation_id' => (string) Str::uuid(), 'published_at' => now()]);
        $this->source = (int) $version->id;
        $this->content->update(['published_version_id' => $this->source]);
        ContentItemGroup::create(['content_item_id' => $this->content->id, 'teaching_group_id' => $this->group]);
    }

    private function approve(array $changes = [], ?int $id = null): array
    {
        return app(PracticeBank::class)->approve($this->actor(), $this->group, $id, $changes + ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'topic_id' => $this->nodes[$this->keys[1]], 'content_version_id' => $this->source, 'estimated_seconds' => 120, 'active' => true,
            'definition' => ['kind' => 'number', 'prompt' => 'One half as a decimal?', 'explanation' => 'Divide one by two to get 0.5.', 'options' => [], 'answers' => ['0.5']]]);
    }

    private function issue(): array
    {
        return app(DailyPractice::class)->issue($this->actor('asha'), $this->schoolA->ids['asha'], $this->group, ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10']);
    }

    private function review(array $set, array $changes = []): array
    {
        return app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $set['items'][0]['id'],
            $changes + ['operation_id' => (string) Str::uuid(), 'answer' => '.500', 'rating' => 3]);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected fresh practice authorization refusal.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException $error) {
            $this->assertSame(404, $status);
        }
    }

    public function test_approved_daily_set_review_and_separate_mastery_work_with_family_marks_off(): void
    {
        $this->approve();
        $set = $this->issue();
        $this->assertSame(120, $set['estimated_seconds']);
        $this->assertSame(['0.5'], $set['items'][0]['definition']['answers']);
        $this->assertStringEndsWith('Z', $set['offline_until']);
        $this->assertFalse($set['completed']);
        $this->assertSame(0, $set['streak_days']);
        $official = DB::table('question_marks')->count();
        $review = $this->review($set);
        $this->assertTrue($review['correct']);
        $this->assertSame(3, $review['schedule']['rating']);
        $this->assertSame(1, $review['streak_days']);
        $this->assertSame('2026-02-12', PracticeMemory::sole()->due_on->toDateString());
        $this->assertSame($official, DB::table('question_marks')->count());
        $view = $this->masteryView('asha', staff: false);
        $this->assertFalse($view['marks_visible']);
        $this->assertSame(4, collect($view['topics'])->firstWhere('id', $this->nodes[$this->keys[1]])['level']);
        $this->assertSame(['practice_review'], MasteryEvidence::pluck('source_type')->unique()->all());
        $this->assertStringNotContainsString('.500', PracticeReview::sole()->getRawOriginal('answer'));
    }

    public function test_wrong_answer_forces_again_even_when_self_rating_is_easy_and_replay_is_exact(): void
    {
        $this->approve();
        $set = $this->issue();
        $op = ['operation_id' => (string) Str::uuid(), 'answer' => '0.25', 'rating' => 4];
        $first = $this->review($set, $op);
        $this->assertFalse($first['correct']);
        $this->assertSame(1, $first['schedule']['rating']);
        $this->assertSame($first, $this->review($set, $op));
        $this->assertSame(1, PracticeReview::count());
        $this->refuses(fn () => $this->review($set, array_replace($op, ['answer' => '.5'])), 409);
        $this->refuses(fn () => $this->review($set), 409);
    }

    public function test_set_is_immutable_bounded_to_ten_minutes_and_new_approved_version_does_not_rewrite_answers(): void
    {
        $first = $this->approve();
        for ($i = 0; $i < 5; $i++) {
            $this->approve();
        }
        $set = $this->issue();
        $this->assertCount(5, $set['items']);
        $this->assertSame(600, $set['estimated_seconds']);
        $this->assertSame($set['id'], $this->issue()['id']);
        $this->approve(['expected_revision' => 1, 'definition' => ['kind' => 'number', 'prompt' => 'One quarter as a decimal?', 'explanation' => 'Divide one by four.', 'options' => [], 'answers' => ['.25']]], $first['id']);
        $saved = app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']);
        $this->assertSame(['0.5'], $saved['items'][0]['definition']['answers']);
        $this->assertSame(7, PracticeItemVersion::count());
        $this->refuses(fn () => PracticeItemVersion::firstOrFail()->update(['estimated_seconds' => 60]), 409);
    }

    public function test_offline_sync_uses_server_review_day_and_cannot_backdate_streak_or_accept_ancient_sets(): void
    {
        $this->approve();
        $set = $this->issue();
        $this->travelTo(now()->addDay());
        $saved = $this->review($set);
        $this->assertSame('2026-02-11', $saved['reviewed_on']);
        $this->assertSame('2026-02-11', PracticeDailySet::sole()->completed_on->toDateString());
        $this->assertSame(1, $saved['streak_days']);
        $this->travelTo(now()->addDays(8));
        $this->refuses(fn () => $this->review($set, ['operation_id' => (string) Str::uuid()]), 409);
    }

    public function test_withdrawn_guardian_approval_and_source_hide_downloads_and_replays(): void
    {
        $this->approve();
        $set = $this->issue();
        $op = ['operation_id' => (string) Str::uuid()];
        $this->review($set, $op);
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 403);
        $this->refuses(fn () => $this->review($set, $op), 403);
        LearnerApproval::query()->update(['withdrawn_at' => null]);
        $this->content->update(['active' => false]);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 404);
        $this->refuses(fn () => $this->review($set, $op), 404);
    }

    public function test_wrong_role_wrong_school_wrong_learner_and_revoked_teacher_allocation_are_refused(): void
    {
        $this->approve();
        $set = $this->issue();
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('farid'), $this->schoolA->ids['asha'], $set['id']), 404);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['ben'], $set['id']), 404);
        app(CurrentSchool::class)->run(2, function () use ($set) {
            $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 404);
        });
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => $this->approve(), 403);
        $this->refuses(fn () => app(PracticeBank::class)->catalogue($this->actor(), $this->group), 403);
    }

    public function test_unpublished_curriculum_or_deactivated_item_cannot_be_read_even_in_an_issued_set(): void
    {
        $this->approve();
        $set = $this->issue();
        PracticeItem::query()->update(['active' => false]);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 404);
        PracticeItem::query()->update(['active' => true]);
        CurriculumPlan::query()->update(['state' => 'draft']);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 404);
    }

    public function test_read_only_practice_keeps_authorized_reads_and_refuses_review(): void
    {
        $this->approve();
        $set = $this->issue();
        SchoolModule::where('module', 'daily_practice')->update(['read_only' => true]);
        $this->assertSame($set['id'], app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'])['id']);
        $this->refuses(fn () => $this->review($set), 403);
    }

    public function test_populated_practice_history_refuses_destructive_rollback(): void
    {
        $this->approve();
        $set = $this->issue();
        $this->review($set);
        $migration = require database_path('migrations/2026_10_25_213000_create_daily_practice.php');
        try {
            $migration->down();
            $this->fail('Populated practice history must be retained.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('exported', $error->getMessage());
        }
        $this->assertSame(1, PracticeReview::count());
        $this->assertSame(1, PracticeDailySet::count());
    }
}
