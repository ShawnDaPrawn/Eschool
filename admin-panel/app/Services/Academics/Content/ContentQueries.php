<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentRecipient;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\TeachingGroup;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ContentQueries
{
    public function configuration(User $actor, ?int $yearId = null): array
    {
        $a = app(AcademicAuthority::class);
        $a->staff($actor);
        $years = SessionYear::orderByDesc('default')->orderByDesc('id')->get();
        $year = $yearId === null ? $years->first() : $years->firstWhere('id', $yearId);
        abort_if($year === null, 404);
        $groups = TeachingGroup::with('subject', 'gradeLevel')->where('session_year_id', $year->id)->where('active', true)->orderBy('name')->get()->filter(function ($g) use ($actor) {
            try {
                app(ContentPolicy::class)->group($actor, (int) $g->id);

                return $g->subject !== null;
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                    throw $e;
                }

                return false;
            }
        })->values();
        $pairs = $groups->map(fn ($g) => $g->subject_id.':'.$g->grade_level_id)->unique()->all();
        $plans = CurriculumPlan::where('session_year_id', $year->id)->whereHas('assessments', fn ($q) => $q->where('state', 'published'))->get()->filter(fn ($p) => in_array($p->subject_id.':'.$p->grade_level_id, $pairs, true));
        $topics = CurriculumTopic::with('curriculum')->whereIn('curriculum_plan_id', $plans->pluck('id'))->orderBy('sort_order')->orderBy('id')->get();

        return ['session_year_id' => (int) $year->id, 'school_years' => $years->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all(),
            'groups' => $groups->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name, 'subject_id' => (int) $g->subject_id, 'subject_name' => $g->subject->name, 'grade_level_id' => (int) $g->grade_level_id, 'grade_name' => $g->gradeLevel?->name ?? __('Archived grade')])->all(),
            'topics' => $topics->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name, 'subject_id' => (int) $t->curriculum->subject_id, 'grade_level_id' => (int) $t->curriculum->grade_level_id, 'curriculum_version' => $t->curriculum->version])->all(),
            'settings' => app(ContentSettings::class)->data(), 'can_manage' => $a->manages($actor), 'maximum_source_bytes' => (int) config('academic_content.maximum_source_bytes')];
    }

    private function visible(User $actor)
    {
        $a = app(AcademicAuthority::class);
        $a->staff($actor);
        $q = ContentItem::query();
        if ($a->manages($actor) || AcademicLeadership::where('user_id', $actor->id)->where('kind', 'principal')->whereNull('grade_level_id')->whereDate('starts_on', '<=', $a->today())->whereDate('ends_on', '>=', $a->today())->exists()) {
            return $q;
        }
        $grades = AcademicLeadership::where('user_id', $actor->id)->where('kind', 'grade_head')->whereDate('starts_on', '<=', $a->today())->whereDate('ends_on', '>=', $a->today())->pluck('grade_level_id');

        return $q->where(fn ($scope) => $scope->where('author_id', $actor->id)->orWhereIn('grade_level_id', $grades));
    }

    public function staff(User $actor, ?int $year = null, int $page = 1): array
    {
        if ($year !== null) {
            SessionYear::findOrFail($year);
        }
        $rows = $this->visible($actor)->with('draft', 'published')->when($year !== null, fn ($q) => $q->where('session_year_id', $year))->orderByDesc('id')->paginate(50, ['*'], 'page', $page);
        $items = [];
        foreach ($rows as $row) {
            try {
                app(ContentPolicy::class)->staff($actor, (int) $row->id);
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                    throw $e;
                }

                continue;
            }
            $v = $row->draft ?? $row->published;
            if ($v === null) {
                continue;
            }
            $items[] = ['id' => (int) $row->id, 'title' => $v->metadata['title'], 'active' => $row->active, 'draft_version_id' => $row->draft_version_id === null ? null : (int) $row->draft_version_id,
                'published_version_id' => $row->published_version_id === null ? null : (int) $row->published_version_id, 'draft_state' => $row->draft?->state, 'revision' => $row->revision, 'updated_at' => $row->updated_at->toISOString()];
        }

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $items];
    }

    public function versionData(ContentVersion $v): array
    {
        return ['id' => (int) $v->id, 'content_item_id' => (int) $v->content_item_id, 'number' => $v->number, 'state' => $v->state, 'selection' => $v->metadata, 'markdown' => $v->markdown,
            'revision' => $v->revision, 'failure_code' => $v->failure_code, 'duration_seconds' => $v->duration_seconds, 'language' => $v->language, 'created_at' => $v->created_at->toISOString(), 'published_at' => $v->published_at?->toISOString(),
            'source' => ['name' => $v->source->name, 'extension' => $v->source->extension, 'mime' => $v->source->mime, 'bytes' => $v->source->bytes, 'scan_state' => $v->source->scan_state, 'scanned_at' => $v->source->scanned_at?->toISOString()]];
    }

    private function accessibleVersion(User $actor, ?int $id): ?array
    {
        if ($id === null) {
            return null;
        }
        try {
            return $this->versionData(app(ContentPolicy::class)->version($actor, $id));
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                throw $e;
            }

            return null;
        }
    }

    public function show(User $actor, int $id, int $page = 1): array
    {
        $item = app(ContentPolicy::class)->staff($actor, $id);
        $rows = $item->versions()->with('source')->orderByDesc('number')->paginate(50, ['*'], 'page', $page);
        $history = [];
        foreach ($rows as $v) {
            try {
                app(ContentPolicy::class)->version($actor, (int) $v->id);
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                    throw $e;
                }

                continue;
            }
            $history[] = ['id' => (int) $v->id, 'number' => $v->number, 'state' => $v->state, 'title' => $v->metadata['title'], 'published_at' => $v->published_at?->toISOString()];
        }
        try {
            app(ContentPolicy::class)->staff($actor, $id, true);
            $canWrite = true;
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                throw $e;
            } $canWrite = false;
        }

        return ['id' => $id, 'can_write' => $canWrite, 'active' => $item->active, 'revision' => $item->revision, 'author_id' => (int) $item->author_id,
            'draft' => $this->accessibleVersion($actor, $item->draft_version_id === null ? null : (int) $item->draft_version_id),
            'published' => $this->accessibleVersion($actor, $item->published_version_id === null ? null : (int) $item->published_version_id),
            'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'versions' => $history];
    }

    public function library(User $actor, int $learner, int $page = 1): array
    {
        $groups = app(ContentPolicy::class)->learnerGroups($actor, $learner);
        $items = ContentItem::with('published')->where('active', true)->whereNotNull('published_version_id')->whereHas('audienceGroups', fn ($q) => $q->whereIn('teaching_group_id', $groups))->orderByDesc('published_version_id')->paginate(50, ['*'], 'page', $page);
        $subjects = Subject::withTrashed()->whereIn('id', $items->getCollection()->pluck('subject_id'))->get()->keyBy('id');

        return ['learner_id' => $learner, 'page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'items' => $items->getCollection()->filter(fn ($i) => $i->published?->state === 'published')->map(fn ($i) => ['id' => (int) $i->id, 'version_id' => (int) $i->published_version_id,
            'title' => $i->published->metadata['title'], 'summary' => $i->published->metadata['summary'], 'subject_id' => (int) $i->subject_id, 'subject_name' => $subjects->get($i->subject_id)?->name ?? __('Archived subject'), 'published_at' => $i->published->published_at->toISOString()])->values()->all()];
    }

    public function reader(User $actor, int $learner, int $id): array
    {
        $v = app(ContentPolicy::class)->reader($actor, $learner, $id);
        $metadata = $v->metadata;
        $topics = CurriculumTopic::withTrashed()->whereIn('id', $metadata['topic_ids'])->get()->keyBy('id');
        $instant = now();

        return ['id' => $id, 'content_item_id' => (int) $v->content_item_id, 'learner_id' => $learner, 'school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id,
            'title' => $metadata['title'], 'summary' => $metadata['summary'], 'markdown' => $v->markdown, 'body_sha256' => hash('sha256', $v->markdown), 'published_at' => $v->published_at->toISOString(),
            'topics' => array_values(array_map(fn ($topic) => ['id' => $topic, 'name' => $topics->get($topic)?->name ?? __('Archived topic')], $metadata['topic_ids'])),
            'server_time' => $instant->toISOString(), 'offline_until' => $instant->copy()->addHours((int) config('academic_content.offline_lease_hours'))->toISOString()];
    }

    public function read(User $actor, int $learner, int $id): array
    {
        app(ContentPolicy::class)->reader($actor, $learner, $id);
        ContentRecipient::firstOrCreate(['content_version_id' => $id, 'learner_id' => $learner, 'user_id' => $actor->id])->update(['read_at' => now()]);

        return ['version_id' => $id, 'learner_id' => $learner, 'read' => true];
    }
}
