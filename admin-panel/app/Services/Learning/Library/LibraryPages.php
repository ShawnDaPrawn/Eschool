<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Library\LibraryFramework;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibraryReviewer;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

/** What the /saas/library pages show (SA.16). Library rows and platform staff names only: never a school's people. */
class LibraryPages
{
    public const STATES = ['draft' => 'Draft', 'in_review' => 'Waiting for review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'published' => 'Published'];

    public const QUEUE_LIMIT = 200;

    public function index(User $actor, int $schoolPage = 1): array
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $frameworks = LibraryFramework::query()->orderBy('code')->get();
        $topicCounts = LibraryTopic::query()->selectRaw('library_framework_id, COUNT(*) as total')->groupBy('library_framework_id')->pluck('total', 'library_framework_id');
        $itemCounts = LibraryItem::query()->join('library_topics', 'library_topics.id', '=', 'library_items.library_topic_id')
            ->selectRaw('library_topics.library_framework_id, COUNT(*) as total, SUM(library_items.published_version_id IS NOT NULL) as published')
            ->groupBy('library_topics.library_framework_id')->get()->keyBy('library_framework_id');
        $reviewers = LibraryReviewer::query()->orderBy('library_framework_id')->orderBy('subject_code')->limit(500)->get();
        $names = $this->names($reviewers->pluck('user_id')->all());

        return [
            'frameworks' => $frameworks->map(fn ($f) => ['id' => (int) $f->id, 'code' => $f->code, 'name' => $f->name, 'topics' => (int) ($topicCounts[$f->id] ?? 0),
                'items' => (int) ($itemCounts[$f->id]->total ?? 0), 'published' => (int) ($itemCounts[$f->id]->published ?? 0)])->all(),
            'frameworkChoices' => $frameworks->pluck('code', 'id')->all(),
            'subjectChoices' => $frameworks->mapWithKeys(fn ($f) => [$f->id => app(LibraryCatalogue::class)->subjectChoices($f)])->all(),
            'sources' => LibrarySource::query()->orderBy('title')->limit(500)->get()->map(fn ($s) => ['title' => $s->title, 'kind' => LibraryCatalogue::SOURCE_KINDS[$s->kind] ?? $s->kind,
                'licence' => $s->licence, 'attribution' => $s->attribution, 'url' => $s->url])->all(),
            'sourceKinds' => LibraryCatalogue::SOURCE_KINDS,
            'reviewers' => $reviewers->map(fn ($r) => ['id' => (int) $r->id, 'name' => $names[$r->user_id] ?? 'Former staff member',
                'framework' => $frameworks->firstWhere('id', $r->library_framework_id)?->code, 'subject_code' => $r->subject_code])->all(),
            'reviewerChoices' => app(LibraryReviewers::class)->candidates(),
            'waiting' => LibraryItemVersion::query()->where('state', 'in_review')->count(),
            'canReview' => app(LibraryAccess::class)->can($actor, LibraryAccess::REVIEW),
        ] + app(LibrarySchools::class)->list($schoolPage);
    }

    public function framework(User $actor, int $id): array
    {
        app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $framework = LibraryFramework::query()->findOrFail($id);
        $grades = GradeLevel::query()->orderBy('sort_order')->get(['id', 'name', 'sort_order']);
        $topics = LibraryTopic::query()->where('library_framework_id', $framework->id)->orderBy('subject_code')->orderBy('grade_level_id')->orderBy('name')->limit(2000)->get();
        $edges = LibraryTopicPrerequisite::query()->whereIn('library_topic_id', $topics->pluck('id'))->get()->groupBy('library_topic_id');
        $items = LibraryItem::query()->whereIn('library_topic_id', $topics->pluck('id'))->orderBy('id')->limit(5000)->get()->groupBy('library_topic_id');
        $latest = LibraryItemVersion::query()->whereIn('id', LibraryItemVersion::query()->selectRaw('MAX(id)')
            ->whereIn('library_item_id', $items->flatten()->pluck('id'))->groupBy('library_item_id'))->get()->keyBy('library_item_id');
        $names = $topics->pluck('name', 'id');
        $gradeNames = $grades->pluck('name', 'id');

        return [
            'framework' => ['id' => (int) $framework->id, 'code' => $framework->code, 'name' => $framework->name],
            'topics' => $topics->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name, 'subject_code' => $t->subject_code, 'grade' => (string) $gradeNames->get($t->grade_level_id),
                'prerequisite_ids' => ($edges[$t->id] ?? collect())->pluck('prerequisite_id')->map(fn ($p) => (int) $p)->all(),
                'prerequisites' => ($edges[$t->id] ?? collect())->map(fn ($e) => (string) $names->get($e->prerequisite_id))->all(),
                'siblings' => $topics->where('subject_code', $t->subject_code)->where('id', '!=', $t->id)->mapWithKeys(fn ($s) => [(int) $s->id => $s->name.' ('.$gradeNames->get($s->grade_level_id).')'])->all(),
                'items' => ($items[$t->id] ?? collect())->map(fn ($i) => ['id' => (int) $i->id, 'title' => $i->title, 'kind' => LibraryContent::KINDS[$i->kind] ?? $i->kind,
                    'state' => self::STATES[$latest[$i->id]->state ?? 'draft'] ?? '', 'published' => $i->published_version_id !== null])->values()->all()])->all(),
            'topicChoices' => $topics->mapWithKeys(fn ($t) => [(int) $t->id => $t->subject_code.' · '.$gradeNames->get($t->grade_level_id).' · '.$t->name])->all(),
            'subjectChoices' => app(LibraryCatalogue::class)->subjectChoices($framework),
            'gradeChoices' => $gradeNames->all(),
            'kinds' => LibraryContent::KINDS,
            'sourceChoices' => LibrarySource::query()->orderBy('title')->limit(500)->get()->mapWithKeys(fn ($s) => [(int) $s->id => $s->title.' · '.$s->licence])->all(),
        ];
    }

    public function item(User $actor, int $id): array
    {
        app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $item = LibraryItem::query()->with('topic.framework', 'source')->findOrFail($id);
        $versions = LibraryItemVersion::query()->where('library_item_id', $item->id)->orderByDesc('number')->get();
        $reviews = LibraryReview::query()->whereIn('library_item_version_id', $versions->pluck('id'))->orderBy('id')->get()->groupBy('library_item_version_id');
        $names = $this->names($versions->pluck('created_by')->merge($versions->pluck('published_by'))->merge($reviews->flatten()->pluck('reviewer_id'))->filter()->all());
        $latest = $versions->first();

        return [
            'item' => ['id' => (int) $item->id, 'title' => $item->title, 'kind' => $item->kind, 'kind_label' => LibraryContent::KINDS[$item->kind] ?? $item->kind,
                'revision' => (int) $item->revision, 'published_version_id' => $item->published_version_id],
            'topic' => ['name' => $item->topic->name, 'subject_code' => $item->topic->subject_code, 'framework_id' => (int) $item->topic->library_framework_id,
                'framework' => $item->topic->framework->code, 'grade' => (string) GradeLevel::query()->whereKey($item->topic->grade_level_id)->value('name')],
            'source' => ['title' => $item->source->title, 'licence' => $item->source->licence, 'attribution' => $item->source->attribution],
            'versions' => $versions->map(fn ($v) => ['id' => (int) $v->id, 'number' => $v->number, 'state' => $v->state, 'state_label' => self::STATES[$v->state] ?? $v->state,
                'author' => $names[$v->created_by] ?? '', 'published_by' => $v->published_by ? ($names[$v->published_by] ?? '') : null,
                'published_at' => $v->published_at?->format('Y-m-d H:i'), 'current' => (int) $v->id === (int) $item->published_version_id, 'content' => $this->display($v),
                'reviews' => ($reviews[$v->id] ?? collect())->map(fn ($r) => ['decision' => $r->decision, 'reason' => $r->reason, 'reviewer' => $names[$r->reviewer_id] ?? '',
                    'at' => $r->created_at?->format('Y-m-d H:i')])->values()->all()])->all(),
            'latest' => ['id' => (int) $latest->id, 'state' => $latest->state],
            'fields' => app(LibraryContent::class)->fields($latest),
        ];
    }

    /** Items waiting for review; each says whether this reviewer may decide it, and if not, why. */
    public function reviews(User $actor): array
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::REVIEW);
        $versions = LibraryItemVersion::query()->with('item.topic.framework', 'item.source')->where('state', 'in_review')->orderBy('submitted_at')->orderBy('id')
            ->limit(self::QUEUE_LIMIT)->get();
        $names = $this->names($versions->pluck('submitted_by')->filter()->all());
        $grades = GradeLevel::query()->pluck('name', 'id');
        $subjects = LibraryReviewer::query()->where('user_id', $actor->id)->get()
            ->map(fn ($r) => LibraryFramework::query()->whereKey($r->library_framework_id)->value('code').' · '.$r->subject_code)->all();

        return [
            'subjects' => $subjects,
            'waiting' => $versions->map(fn ($v) => ['id' => (int) $v->id, 'number' => $v->number, 'title' => $v->item->title, 'kind' => LibraryContent::KINDS[$v->item->kind] ?? $v->item->kind,
                'framework' => $v->item->topic->framework->code, 'subject_code' => $v->item->topic->subject_code, 'grade' => (string) $grades->get($v->item->topic->grade_level_id),
                'topic' => $v->item->topic->name, 'source' => $v->item->source->title.' · '.$v->item->source->licence, 'submitted_by' => $names[$v->submitted_by] ?? '',
                'submitted_at' => $v->submitted_at?->format('Y-m-d H:i'), 'content' => $this->display($v), 'refusal' => app(LibraryReviewers::class)->refusal($actor, $v)])->all(),
        ];
    }

    /** A version's content, ready to show. */
    public function display(LibraryItemVersion $version): array
    {
        $content = $version->content;
        if (isset($content['definition'])) {
            $d = $content['definition'];

            return ['type' => 'question', 'prompt' => $d['prompt'], 'options' => $d['options'], 'answers' => $d['answers'], 'explanation' => $d['explanation'],
                'seconds' => $version->estimated_seconds, 'paper' => $content['paper'] ?? null];
        }
        if (isset($content['body'])) {
            return ['type' => 'lesson', 'body' => $content['body']];
        }

        return ['type' => 'diagnostic', 'instructions' => $content['instructions'] ?? '',
            'items' => LibraryItem::query()->whereIn('id', $content['item_ids'] ?? [])->orderBy('id')->pluck('title')->all()];
    }

    /** @return array<int, string> */
    private function names(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return app(CurrentSchool::class)->acrossSchools(fn () => User::query()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all());
    }
}
