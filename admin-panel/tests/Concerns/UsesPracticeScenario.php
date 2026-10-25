<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\PracticeBank;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait UsesPracticeScenario
{
    use UsesMasteryScenario;

    protected int $source;

    protected ContentItem $content;

    protected function setUpPracticeScenario(): void
    {
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

    protected function approve(array $changes = [], ?int $id = null): array
    {
        return app(PracticeBank::class)->approve($this->actor(), $this->group, $id, $changes + ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'topic_id' => $this->nodes[$this->keys[1]], 'content_version_id' => $this->source, 'estimated_seconds' => 120, 'active' => true,
            'definition' => ['kind' => 'number', 'prompt' => 'One half as a decimal?', 'explanation' => 'Divide one by two to get 0.5.', 'options' => [], 'answers' => ['0.5']]]);
    }

    protected function issue(): array
    {
        return app(DailyPractice::class)->issue($this->actor('asha'), $this->schoolA->ids['asha'], $this->group, ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10']);
    }

    protected function review(array $set, array $changes = []): array
    {
        return app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $set['items'][0]['id'],
            $changes + ['operation_id' => (string) Str::uuid(), 'answer' => '.500', 'rating' => 3]);
    }

    protected function refuses(callable $call, int $status): void
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
}
