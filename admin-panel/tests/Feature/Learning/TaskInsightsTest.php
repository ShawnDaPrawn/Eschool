<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Jobs\Learning\RefreshTaskInsights;
use App\Listeners\Learning\QueueTaskInsights;
use App\Models\Academics\TeachingAllocation;
use App\Models\Ai\AiUsage;
use App\Models\Learning\TaskInsight;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Services\Learning\TaskInsights;
use App\Services\Learning\TeachingDraftAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class TaskInsightsTest extends TestCase
{
    use UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        foreach (['test_insights', 'teaching_assistant'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }
        app(ModuleGate::class)->forget();
    }

    public function test_missing_question_evidence_stays_unknown_and_summary_contains_no_learner_identity(): void
    {
        $this->capture('4.00', null);
        $result = app(TaskInsights::class)->read($this->actor(), $this->group, $this->task);
        $questions = collect($result['summary']['questions'])->keyBy('question_id');
        $this->assertSame(1, $result['summary']['learner_count']);
        $this->assertSame(1, $questions[$this->questions[0]]['missed_count']);
        $this->assertSame('4.00', $questions[$this->questions[0]]['average_mark']);
        $this->assertSame(1, $questions[$this->questions[1]]['unknown_count']);
        $this->assertSame(0, $questions[$this->questions[1]]['evidence_count']);
        $this->assertNull($questions[$this->questions[1]]['average_mark']);
        $text = json_encode($result, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('learner_id', $text);
        $this->assertStringNotContainsString($this->actor('asha')->first_name, $text);
        $this->assertStringNotContainsString('questions', TaskInsight::findOrFail($result['id'])->getRawOriginal('summary'));
    }

    public function test_changed_marks_refresh_one_snapshot_and_ended_students_disappear(): void
    {
        $this->capture();
        $s = app(TaskInsights::class);
        $a = $s->read($this->actor(), $this->group, $this->task);
        $same = $s->read($this->actor(), $this->group, $this->task);
        $this->assertSame($a, $same);
        $this->capture('10.00', '10.00', 1);
        $b = $s->read($this->actor(), $this->group, $this->task);
        $this->assertSame($a['id'], $b['id']);
        $this->assertNotSame($a['fingerprint'], $b['fingerprint']);
        $this->assertSame(0, collect($b['summary']['questions'])->sum('missed_count'));
        SchoolMembership::where('user_id', $this->actor('asha')->id)->where('role', 'Student')->update(['status' => 'ended']);
        $c = $s->read($this->actor(), $this->group, $this->task);
        $this->assertSame(0, $c['summary']['learner_count']);
        $this->assertSame(0, collect($c['summary']['questions'])->sum('evidence_count'));
        $this->assertCount(1, TaskInsight::all());
    }

    public function test_ordinary_admin_family_wrong_school_and_ended_allocation_cannot_read(): void
    {
        $s = app(TaskInsights::class);
        $this->refuses(fn () => $s->read($this->superAdmin(), $this->group, $this->task), 403);
        $this->refuses(fn () => $s->read($this->actor('farid'), $this->group, $this->task), 403);
        $this->refuses(fn () => $s->read($this->actor('asha'), $this->group, $this->task), 403);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $s->read($this->actor(), $this->group, $this->task)), 404);
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => $s->read($this->actor(), $this->group, $this->task), 403);
    }

    public function test_platform_module_and_source_withdrawal_are_checked_afresh(): void
    {
        $a = app(TeachingDraftAccess::class);
        $group = $a->group($this->actor(), $this->group);
        $this->assertSame($this->source, (int) $a->source($this->actor(), $group, $this->source)->id);
        $this->content->update(['active' => false]);
        $this->refuses(fn () => $a->source($this->actor(), $group, $this->source), 404);
        $this->content->update(['active' => true]);
        SchoolModule::where('module', 'teaching_assistant')->update(['read_only' => true]);
        $this->refuses(fn () => $a->group($this->actor(), $this->group, true), 403);
        SchoolModule::where('module', 'test_insights')->update(['enabled' => false]);
        $this->refuses(fn () => app(TaskInsights::class)->read($this->actor(), $this->group, $this->task), 403);
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id, 'active' => true]);
        $this->refuses(fn () => $a->group($this->actor(), $this->group), 403);
    }

    public function test_mark_listener_queues_only_enabled_school_and_never_runs_inference(): void
    {
        Bus::fake();
        $listener = app(QueueTaskInsights::class);
        $event = new OutboxEvent(['school_id' => 1, 'type' => 'academics.marks_changed', 'payload' => ['group_id' => $this->group, 'term_id' => $this->term]]);
        SchoolModule::where('module', 'test_insights')->update(['enabled' => false]);
        $listener->handle($event);
        Bus::assertNotDispatched(RefreshTaskInsights::class);
        SchoolModule::where('module', 'test_insights')->update(['enabled' => true]);
        $listener->handle($event);
        Bus::assertDispatched(RefreshTaskInsights::class, fn ($job) => $job->schoolId === 1 && $job->groupId === $this->group);
        $this->assertSame(0, AiUsage::count());
    }
}
