<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Library\LibraryAiDraft;
use App\Models\Library\LibraryContribution;
use App\Models\Library\LibraryFramework;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryRating;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibraryReviewer;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** What the /saas/library pages show (SA.16). Library rows and platform staff names only: never a school's people. */
class LibraryPages
{
    public const STATES = ['draft' => 'Draft', 'in_review' => 'Waiting for review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'published' => 'Published'];

    public const QUEUE_PER_PAGE = 50;

    /** The review queue's filters (Action Plan 3.4): everything, AI drafts only, or items learners' answers have flagged. */
    public const QUEUE_FILTERS = ['all' => 'Everything waiting', 'ai_draft' => 'AI drafts', 'attention' => 'Needs attention', 'contributed' => 'Shared by schools'];

    public const RATINGS_PER_PAGE = 50;

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
            'sources' => LibrarySource::query()->whereNull('contributor_school_id')->orderBy('title')->limit(500)->get()->map(fn ($s) => ['title' => $s->title, 'kind' => LibraryCatalogue::SOURCE_KINDS[$s->kind] ?? $s->kind,
                'licence' => $s->licence, 'attribution' => $s->attribution, 'url' => $s->url])->all(),
            'sourceKinds' => LibraryCatalogue::SOURCE_KINDS,
            'reviewers' => $reviewers->map(fn ($r) => ['id' => (int) $r->id, 'name' => $names[$r->user_id] ?? 'Former staff member',
                'framework' => $frameworks->firstWhere('id', $r->library_framework_id)?->code, 'subject_code' => $r->subject_code])->all(),
            'reviewerChoices' => app(LibraryReviewers::class)->candidates(),
            'waiting' => LibraryItemVersion::query()->where('state', 'in_review')->whereHas('item', fn ($q) => $q->whereNull('withdrawn_at'))->count(),
            'canReview' => app(LibraryAccess::class)->can($actor, LibraryAccess::REVIEW),
            'drafting' => app(LibraryDrafting::class)->status(),
            'recentDrafts' => app(LibraryDrafting::class)->recent(),
            'attention' => app(LibraryQuality::class)->flaggedCount(),
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
                    'state' => self::STATES[$latest[$i->id]->state ?? 'draft'] ?? '', 'published' => $i->published_version_id !== null,
                    'ai' => ($latest[$i->id]->origin ?? 'staff') === 'ai_draft', 'contributed' => ($latest[$i->id]->origin ?? 'staff') === 'contributed',
                    'withdrawn' => $i->withdrawn_at !== null])->values()->all()])->all(),
            'topicChoices' => $topics->mapWithKeys(fn ($t) => [(int) $t->id => $t->subject_code.' · '.$gradeNames->get($t->grade_level_id).' · '.$t->name])->all(),
            'subjectChoices' => app(LibraryCatalogue::class)->subjectChoices($framework),
            'gradeChoices' => $gradeNames->all(),
            'kinds' => LibraryContent::KINDS,
            'drafting' => app(LibraryDrafting::class)->status(),
            'draftKinds' => LibraryDrafting::KINDS,
            'draftMaximum' => LibraryDrafting::MAXIMUM,
            'operationId' => (string) Str::uuid(),
            'sourceChoices' => LibrarySource::query()->whereNull('contributor_school_id')->orderBy('title')->limit(500)->get()->mapWithKeys(fn ($s) => [(int) $s->id => $s->title.' · '.$s->licence])->all(),
        ];
    }

    public function item(User $actor, int $id): array
    {
        app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $item = LibraryItem::query()->with('topic.framework', 'source')->findOrFail($id);
        $versions = LibraryItemVersion::query()->where('library_item_id', $item->id)->orderByDesc('number')->get();
        $reviews = LibraryReview::query()->whereIn('library_item_version_id', $versions->pluck('id'))->orderBy('id')->get()->groupBy('library_item_version_id');
        $requests = LibraryAiDraft::query()->whereIn('id', $versions->pluck('library_ai_draft_id')->filter()->unique())->get()->keyBy('id');
        $names = $this->names($versions->pluck('created_by')->merge($versions->pluck('published_by'))->merge($reviews->flatten()->pluck('reviewer_id'))
            ->merge($requests->pluck('requested_by'))->push($item->unpublished_by)->push($item->source->contributor_user_id)->filter()->all());
        $latest = $versions->first();

        return [
            'item' => ['id' => (int) $item->id, 'title' => $item->title, 'kind' => $item->kind, 'kind_label' => LibraryContent::KINDS[$item->kind] ?? $item->kind,
                'revision' => (int) $item->revision, 'published_version_id' => $item->published_version_id, 'withdrawn_at' => $item->withdrawn_at?->format('Y-m-d H:i'),
                'unpublished_at' => $item->unpublished_at?->format('Y-m-d H:i'), 'unpublished_by' => $item->unpublished_by ? ($names[$item->unpublished_by] ?? '') : null,
                'unpublish_reason' => $item->unpublish_reason],
            'contribution' => $this->contribution($item->source, $names),
            'ratings' => $this->ratingsOf($item),
            'topic' => ['name' => $item->topic->name, 'subject_code' => $item->topic->subject_code, 'framework_id' => (int) $item->topic->library_framework_id,
                'framework' => $item->topic->framework->code, 'grade' => (string) GradeLevel::query()->whereKey($item->topic->grade_level_id)->value('name')],
            'source' => ['title' => $item->source->title, 'licence' => $item->source->licence, 'attribution' => $item->source->attribution],
            'versions' => $versions->map(fn ($v) => ['id' => (int) $v->id, 'number' => $v->number, 'state' => $v->state, 'state_label' => self::STATES[$v->state] ?? $v->state,
                'author' => $names[$v->created_by] ?? '', 'published_by' => $v->published_by ? ($names[$v->published_by] ?? '') : null,
                'published_at' => $v->published_at?->format('Y-m-d H:i'), 'current' => (int) $v->id === (int) $item->published_version_id, 'content' => $this->display($v),
                'origin' => (string) $v->origin, 'provenance' => $this->provenance($v, $item->source, $reviews[$v->id] ?? collect(), $requests->get($v->library_ai_draft_id), $names),
                'request' => $v->library_ai_draft_id === null || ! $requests->has($v->library_ai_draft_id) ? null : ['id' => (int) $v->library_ai_draft_id,
                    'brief' => $requests[$v->library_ai_draft_id]->brief, 'by' => $names[$requests[$v->library_ai_draft_id]->requested_by] ?? ''],
                'reviews' => ($reviews[$v->id] ?? collect())->map(fn ($r) => ['decision' => $r->decision, 'reason' => $r->reason, 'reviewer' => $names[$r->reviewer_id] ?? '',
                    'at' => $r->created_at?->format('Y-m-d H:i')])->values()->all()])->all(),
            'latest' => ['id' => (int) $latest->id, 'state' => $latest->state, 'origin' => (string) $latest->origin],
            'fields' => app(LibraryContent::class)->fields($latest),
            'signal' => $this->signal((int) $item->id),
        ];
    }

    /**
     * Versions waiting for review in this reviewer's subjects, oldest first, a
     * page at a time. The subjects filter the query before it is paged, so
     * other subjects' queues never push this reviewer's items out of view.
     * Each row says whether this reviewer may decide it, and if not, why.
     */
    public function reviews(User $actor, int $page = 1, string $filter = 'all'): array
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::REVIEW);
        $filter = array_key_exists($filter, self::QUEUE_FILTERS) ? $filter : 'all';
        $assignments = LibraryReviewer::query()->where('user_id', $actor->id)->orderBy('library_framework_id')->orderBy('subject_code')->get();
        $paginator = LibraryItemVersion::query()->with('item.topic.framework', 'item.source')->where('state', 'in_review')
            // A shared item its school withdrew leaves the queue (Action Plan 5.4).
            ->whereHas('item', fn ($q) => $q->whereNull('withdrawn_at'))
            ->when($filter === 'ai_draft', fn ($q) => $q->where('origin', 'ai_draft'))
            ->when($filter === 'contributed', fn ($q) => $q->where('origin', 'contributed'))
            ->when($filter === 'attention', fn ($q) => $q->whereIn('library_item_id', app(LibraryQuality::class)->flaggedItemIdsQuery()))
            ->whereHas('item.topic', function ($topic) use ($assignments) {
                $topic->where(function ($any) use ($assignments) {
                    $any->whereRaw('1 = 0');
                    foreach ($assignments as $assignment) {
                        $any->orWhere(fn ($q) => $q->where('library_framework_id', $assignment->library_framework_id)->where('subject_code', $assignment->subject_code));
                    }
                });
            })
            ->orderBy('submitted_at')->orderBy('id')
            ->paginate(self::QUEUE_PER_PAGE, ['*'], 'page', max(1, min(100000, $page)))->withPath(route('saas.library.reviews'))->appends($filter === 'all' ? [] : ['filter' => $filter]);
        $versions = collect($paginator->items());
        $flagged = app(LibraryQuality::class)->flaggedAmong($versions->pluck('library_item_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
        $names = $this->names($versions->pluck('submitted_by')->merge($versions->map(fn ($v) => $v->item->source->contributor_user_id))->filter()->all());
        $grades = GradeLevel::query()->pluck('name', 'id');
        $subjects = $assignments->map(fn ($r) => LibraryFramework::query()->whereKey($r->library_framework_id)->value('code').' · '.$r->subject_code)->all();

        return [
            'subjects' => $subjects,
            'filter' => $filter,
            'filters' => self::QUEUE_FILTERS,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'waiting' => $versions->map(fn ($v) => ['id' => (int) $v->id, 'number' => $v->number, 'title' => $v->item->title, 'kind' => LibraryContent::KINDS[$v->item->kind] ?? $v->item->kind,
                'framework' => $v->item->topic->framework->code, 'subject_code' => $v->item->topic->subject_code, 'grade' => (string) $grades->get($v->item->topic->grade_level_id),
                'topic' => $v->item->topic->name, 'source' => $v->item->source->title.' · '.$v->item->source->licence, 'submitted_by' => $names[$v->submitted_by] ?? '',
                'submitted_at' => $v->submitted_at?->format('Y-m-d H:i'), 'content' => $this->display($v), 'refusal' => app(LibraryReviewers::class)->refusal($actor, $v),
                'ai' => $v->drafted(), 'attention' => in_array((int) $v->library_item_id, $flagged, true),
                'contribution' => $v->contributed() ? $this->contribution($v->item->source, $names) : null])->all(),
        ];
    }

    /** A version's content, ready to show. */
    public function display(LibraryItemVersion $version): array
    {
        $content = $version->content;
        if (isset($content['definition'])) {
            $d = $content['definition'];

            return ['type' => 'question', 'kind_label' => __(LibraryContent::QUESTION_KINDS[$d['kind']] ?? $d['kind']), 'prompt' => $d['prompt'], 'options' => $d['options'], 'answers' => $d['answers'],
                'explanation' => $d['explanation'], 'seconds' => $version->estimated_seconds, 'paper' => $content['paper'] ?? null, 'steps' => $d['steps'] ?? [],
                'diagram' => $d['diagram'] ?? null, 'regions' => $d['regions'] ?? [], 'labels' => $d['labels'] ?? [], 'mapping' => $d['mapping'] ?? [], 'max_seconds' => $d['max_seconds'] ?? null];
        }
        if (isset($content['body'])) {
            return ['type' => 'lesson', 'body' => $content['body']];
        }

        return ['type' => 'diagnostic', 'instructions' => $content['instructions'] ?? '',
            'items' => LibraryItem::query()->whereIn('id', $content['item_ids'] ?? [])->orderBy('id')->pluck('title')->all()];
    }

    /**
     * Where a version's content came from, in words: the source for staff
     * writing; for an AI draft, who asked and who reviewed it.
     */
    private function provenance(LibraryItemVersion $version, LibrarySource $source, Collection $reviews, ?LibraryAiDraft $request, array $names): string
    {
        if ($version->contributed()) {
            $approval = $reviews->where('decision', 'approved')->last();
            $shared = $this->contribution($source, $names);

            return 'Shared by '.($shared['teacher'] ?? 'a teacher').' at '.($shared['school'] ?? 'a school').' under '.$source->licence.' on '.($shared['date'] ?? '').' — '
                .($approval === null ? 'not yet approved by a reviewer' : 'reviewed by '.($names[$approval->reviewer_id] ?? 'a former staff member'));
        }
        if (! $version->drafted()) {
            return 'Written by staff from '.$source->title.' ('.$source->licence.')';
        }
        $approval = $reviews->where('decision', 'approved')->last();
        $reviewed = $approval === null ? 'not yet approved by a reviewer' : 'reviewed by '.($names[$approval->reviewer_id] ?? 'a former staff member');

        return 'AI draft from '.$source->title.($request === null ? '' : ', asked for by '.($names[$request->requested_by] ?? 'a former staff member')).' — '.$reviewed;
    }

    /**
     * Shared library items with teachers' ratings (Action Plan 5.4), in this
     * reviewer's subjects, a page at a time: the counted average and count,
     * how many ratings came from the sharing school (kept, never counted) and
     * the comments, never with a teacher's or school's name.
     */
    public function ratings(User $actor, int $page = 1): array
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::REVIEW);
        $assignments = LibraryReviewer::query()->where('user_id', $actor->id)->get();
        $paginator = LibraryItem::query()->with('topic.framework', 'source')->whereHas('source', fn ($q) => $q->where('kind', 'contributed'))
            ->whereIn('id', LibraryRating::acrossSchools()->select('library_item_id'))
            ->whereHas('topic', function ($topic) use ($assignments) {
                $topic->where(function ($any) use ($assignments) {
                    $any->whereRaw('1 = 0');
                    foreach ($assignments as $assignment) {
                        $any->orWhere(fn ($q) => $q->where('library_framework_id', $assignment->library_framework_id)->where('subject_code', $assignment->subject_code));
                    }
                });
            })
            ->orderByDesc('id')->paginate(self::RATINGS_PER_PAGE, ['*'], 'page', max(1, min(100000, $page)))->withPath(route('saas.library.ratings'));
        $items = collect($paginator->items());
        $totals = app(LibraryRatings::class)->totals($items->pluck('id')->map(fn ($id) => (int) $id)->all());
        $grades = GradeLevel::query()->pluck('name', 'id');

        return [
            'page' => $paginator->currentPage(), 'lastPage' => $paginator->lastPage(), 'total' => $paginator->total(), 'threshold' => LibraryRatings::threshold(),
            'items' => $items->map(fn (LibraryItem $item) => ['id' => (int) $item->id, 'title' => $item->title, 'revision' => (int) $item->revision,
                'topic' => $item->topic->framework->code.' · '.$item->topic->subject_code.' · '.$grades->get($item->topic->grade_level_id).' · '.$item->topic->name,
                'published' => $item->published_version_id !== null, 'withdrawn' => $item->withdrawn_at !== null, 'unpublished' => $item->unpublished_at !== null,
                'count' => $totals[(int) $item->id]['count'] ?? 0, 'average' => $totals[(int) $item->id]['average'] ?? null,
                'own_school' => app(LibraryRatings::class)->ownSchool((int) $item->id), 'comments' => app(LibraryRatings::class)->comments((int) $item->id, false)])->all(),
        ];
    }

    /**
     * Who shared a contributed item, for HQ: the teacher, the school, the date
     * and the credit they chose for other schools. Null for HQ's own sources.
     *
     * @param  array<int, string>  $names
     * @return array{teacher: string, school: string, date: string, credit: string}|null
     */
    private function contribution(LibrarySource $source, array $names): ?array
    {
        if ($source->contributor_school_id === null) {
            return null;
        }

        return ['teacher' => $names[$source->contributor_user_id] ?? 'a former teacher',
            'school' => (string) DB::table('schools')->where('id', $source->contributor_school_id)->value('name'),
            'date' => (string) $source->contributed_at?->format('Y-m-d'), 'credit' => LibraryContribution::CREDITS[$source->credit] ?? (string) $source->credit];
    }

    /** @return array{count: int, average: ?float, own_school: int, comments: list<array{stars: int, comment: string}>}|null */
    private function ratingsOf(LibraryItem $item): ?array
    {
        if ($item->source->contributor_school_id === null) {
            return null;
        }
        $total = app(LibraryRatings::class)->totals([(int) $item->id])[(int) $item->id] ?? null;

        return ['count' => $total['count'] ?? 0, 'average' => $total['average'] ?? null, 'own_school' => app(LibraryRatings::class)->ownSchool((int) $item->id),
            'comments' => app(LibraryRatings::class)->comments((int) $item->id, false)];
    }

    /** The item's learner-answer signal, as HQ may see it, or null. */
    private function signal(int $itemId): ?array
    {
        $signal = app(LibraryQuality::class)->signal($itemId);
        if ($signal === null) {
            return null;
        }

        return ['facility' => intdiv($signal['facility_bp'] + 50, 100), 'learners' => app(LibraryQuality::class)->band($signal['learners']),
            'label' => $signal['signal'] === null ? null : LibraryQuality::SIGNALS[$signal['signal']]];
    }

    /** @return array<int, string> */
    private function names(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return app(CurrentSchool::class)->acrossSchools(fn () => User::query()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all());
    }
}
