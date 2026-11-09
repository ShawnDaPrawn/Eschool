<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Library\ContributionGate;
use App\Models\Library\LibraryContribution;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibrarySchoolSetting;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\User;
use App\Services\Learning\PracticeAnswers;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Teachers share their own questions with the platform library (Action Plan
 * 5.4). School data never flows into the library by a live link: sharing is a
 * deliberate copy of one approved practice question's teaching content (the
 * question, its options, accepted answers and worked explanation, and its
 * expected time), never a learner's answer, mark or name, a class list or the
 * school's material. The copy is scanned for identifying data, the teacher
 * confirms they may share it and grants the licence, and, where the school
 * asks for it, a leader approves it. It then enters the library as version 1
 * waiting for Navigate Ed's review, exactly like an item HQ writes: nothing is
 * published, shown to other schools or used in practice until a reviewer
 * assigned to the subject approves it and Content publishes it.
 *
 * Sharing never changes the teacher's question. Withdrawing takes the item
 * out of the library (unpublished, never offered again); the published
 * version itself stays as it was, so practice and evidence that used it keep
 * pointing at it.
 */
class SharedLibrary
{
    /** Questions with a picture cannot be shared yet: a picture cannot be checked for identifying data. */
    public const SHAREABLE_KINDS = ['choice', 'number', 'steps', 'short_text', 'audio'];

    public const STATUS = [
        'awaiting_approval' => 'Waiting for a school leader to approve',
        'declined' => 'Not approved by the school',
        'in_review' => 'Waiting for Navigate Ed review',
        'not_accepted' => 'Not accepted by Navigate Ed',
        'published' => 'In the shared library',
        'unpublished' => 'Taken down by Navigate Ed',
        'withdrawn' => 'Withdrawn',
    ];

    public static function settingsRules(): array
    {
        return ['sharing_enabled' => ['required', 'boolean'], 'leader_approval' => ['required', 'boolean']];
    }

    public static function contributeRules(): array
    {
        return ['practice_item_id' => ['required', 'integer', 'min:1'], 'library_topic_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'min:3', 'max:191'], 'credit' => ['required', Rule::in(array_keys(LibraryContribution::CREDITS))],
            'rights_confirmed' => ['required', 'accepted'], 'licence_accepted' => ['required', 'accepted']];
    }

    public static function declineRules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    /** @return array{sharing_enabled: bool, leader_approval: bool, library_enabled: bool} */
    public function settings(): array
    {
        $row = LibrarySchoolSetting::query()->first();

        return ['sharing_enabled' => (bool) ($row?->sharing_enabled ?? false), 'leader_approval' => (bool) ($row?->leader_approval ?? true),
            'library_enabled' => (bool) ($row?->enabled ?? false)];
    }

    /** A school leader switches sharing on or off and chooses whether a leader approves each contribution. */
    public function saveSettings(User $actor, array $input): array
    {
        $actor = app(SharedLibraryAccess::class)->leader($actor, true);
        $data = Validator::make($input, self::settingsRules())->validate();
        $sharing = filter_var($data['sharing_enabled'], FILTER_VALIDATE_BOOLEAN);
        $approval = filter_var($data['leader_approval'], FILTER_VALIDATE_BOOLEAN);

        return DB::transaction(function () use ($actor, $sharing, $approval) {
            $row = LibrarySchoolSetting::query()->lockForUpdate()->first();
            $before = ['sharing_enabled' => (bool) ($row?->sharing_enabled ?? false), 'leader_approval' => (bool) ($row?->leader_approval ?? true)];
            $after = ['sharing_enabled' => $sharing, 'leader_approval' => $approval];
            if ($before !== $after) {
                $row ??= new LibrarySchoolSetting;
                $row->fill($after + ['sharing_changed_by' => $actor->id, 'sharing_changed_at' => now()])->save();
                Audit::record('library.sharing_settings_changed', $row, $before, $after + ['changed_by' => $actor->id]);
            }

            return $this->settings();
        });
    }

    /**
     * What the teacher may choose when sharing one of their questions: the
     * library topics of its subject and grade (from its group's published
     * curriculum framework), the credit, and the licence.
     */
    public function options(User $actor, int $practiceItemId): array
    {
        $actor = app(SharedLibraryAccess::class)->teacher($actor);
        [$item, $version, $definition] = $this->ownQuestion($actor, $practiceItemId);
        $live = $this->live((int) $item->id)->first();

        return [
            'practice_item_id' => (int) $item->id,
            'revision' => (int) $item->revision,
            'question' => ['kind' => (string) $definition['kind'], 'prompt' => (string) $definition['prompt']],
            'suggested_title' => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $definition['prompt']) ?? ''), 0, 80),
            'topics' => $this->topicChoices($item)->map(fn (LibraryTopic $t) => ['id' => (int) $t->id, 'name' => $t->name, 'framework' => (string) $t->framework?->code,
                'subject_code' => $t->subject_code, 'grade' => (string) GradeLevel::query()->whereKey($t->grade_level_id)->value('name')])->values()->all(),
            'credits' => collect(LibraryContribution::CREDITS)->map(fn ($label, $key) => ['value' => $key, 'label' => __($label)])->values()->all(),
            'licence' => $this->licence(),
            'sharing_enabled' => $this->settings()['sharing_enabled'],
            'leader_approval' => $this->settings()['leader_approval'],
            'shareable' => in_array($definition['kind'], self::SHAREABLE_KINDS, true),
            'current_contribution_id' => $live === null ? null : (int) $live->id,
            'version_id' => (int) $version->id,
        ];
    }

    /** A teacher offers one of their own approved questions to the library. */
    public function contribute(User $actor, array $input): LibraryContribution
    {
        $actor = app(SharedLibraryAccess::class)->teacher($actor, true);
        $data = Validator::make($input, self::contributeRules())->validate();
        [$item, $version, $definition] = $this->ownQuestion($actor, (int) $data['practice_item_id']);
        abort_unless($this->settings()['sharing_enabled'], 403, __('Your school has not switched on sharing with the library. A school leader can switch it on.'));
        if (! in_array($definition['kind'], self::SHAREABLE_KINDS, true)) {
            throw ValidationException::withMessages(['practice_item_id' => __('Questions with a picture cannot be shared yet.')]);
        }
        $topic = $this->topicChoices($item)->firstWhere('id', (int) $data['library_topic_id']);
        if ($topic === null) {
            throw ValidationException::withMessages(['library_topic_id' => __('Choose a library topic of this question\'s subject and grade.')]);
        }
        $title = trim((string) $data['title']);
        app(SharedLibraryScan::class)->refuse([$title, $definition], 'question');

        return DB::transaction(function () use ($actor, $item, $version, $topic, $title, $data) {
            // A read lock on the teacher's question settles two shares at once; the question itself is never written.
            PracticeItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            // Locking reads: what another request committed while this one waited counts.
            $settings = LibrarySchoolSetting::query()->lockForUpdate()->first();
            abort_unless((bool) ($settings?->sharing_enabled ?? false), 403, __('Your school has not switched on sharing with the library. A school leader can switch it on.'));
            abort_if($this->live((int) $item->id)->lockForUpdate()->exists(), 409, __('This question is already shared or waiting for approval. Withdraw it first to share it again.'));
            abort_if(LibraryContribution::query()->where('practice_item_version_id', $version->id)->lockForUpdate()->exists(), 409,
                __('This version of the question was shared before. Approve a new version of it to share it again.'));
            $approval = $settings?->leader_approval;
            $contribution = LibraryContribution::query()->create(['practice_item_id' => $item->id, 'practice_item_version_id' => $version->id, 'library_topic_id' => $topic->id,
                'title' => $title, 'credit' => $data['credit'], 'licence' => $this->licence()['code'], 'contributed_by' => $actor->id, 'rights_confirmed_at' => now(),
                'state' => ($approval ?? true) ? 'awaiting_approval' : 'shared']);
            Audit::record('library.contribution_offered', $contribution, [], ['practice_item_id' => (int) $item->id, 'version_id' => (int) $version->id,
                'library_topic_id' => (int) $topic->id, 'credit' => $data['credit'], 'licence' => $contribution->licence, 'state' => $contribution->state, 'changed_by' => $actor->id]);
            if ($contribution->state === 'shared') {
                $this->share($contribution);
            }

            return $contribution->fresh();
        });
    }

    /** A leader (or a content lead of the question's grade) lets the contribution leave the school. */
    public function approve(User $actor, int $id): LibraryContribution
    {
        $actor = app(SharedLibraryAccess::class)->staff($actor, true);

        return DB::transaction(function () use ($actor, $id) {
            $contribution = $this->decidable($actor, $id);
            abort_if((int) $contribution->contributed_by === (int) $actor->id, 403, __('Someone else must approve a question you shared.'));
            abort_unless((bool) (LibrarySchoolSetting::query()->lockForUpdate()->value('sharing_enabled') ?? false), 409, __('Sharing is switched off for your school.'));
            $contribution->update(['decided_by' => $actor->id, 'decided_at' => now()]);
            Audit::record('library.contribution_approved', $contribution, ['state' => 'awaiting_approval'], ['state' => 'shared', 'changed_by' => $actor->id]);
            $this->share($contribution);

            return $contribution->fresh();
        });
    }

    public function decline(User $actor, int $id, array $input): LibraryContribution
    {
        $actor = app(SharedLibraryAccess::class)->staff($actor, true);
        $data = Validator::make($input, self::declineRules())->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            $contribution = $this->decidable($actor, $id);
            $contribution->update(['state' => 'declined', 'decided_by' => $actor->id, 'decided_at' => now(), 'decline_reason' => trim((string) $data['reason'])]);
            Audit::record('library.contribution_declined', $contribution, ['state' => 'awaiting_approval'], ['state' => 'declined', 'changed_by' => $actor->id]);

            return $contribution->fresh();
        });
    }

    /**
     * The teacher who shared it, a leader, or a content lead of its grade
     * withdraws a contribution. Once it reached the library, the library item
     * is withdrawn too: unpublished and never offered again, its versions kept.
     */
    public function withdraw(User $actor, int $id): LibraryContribution
    {
        $actor = app(SharedLibraryAccess::class)->staff($actor, true);

        return DB::transaction(function () use ($actor, $id) {
            $contribution = LibraryContribution::query()->lockForUpdate()->findOrFail($id);
            $grade = (int) PracticeItem::query()->whereKey($contribution->practice_item_id)->value('grade_level_id');
            $own = (int) $contribution->contributed_by === (int) $actor->id && app(SharedLibraryAccess::class)->isTeacher($actor);
            abort_unless($own || app(SharedLibraryAccess::class)->leadsGrade($actor, $grade), 403, __('Only the teacher who shared it or a school leader can withdraw it.'));
            if ($contribution->state === 'withdrawn' || $contribution->state === 'declined') {
                return $contribution;
            }
            $before = $contribution->state;
            $contribution->update(['state' => 'withdrawn', 'withdrawn_by' => $actor->id, 'withdrawn_at' => now()]);
            if ($contribution->library_item_id !== null) {
                ContributionGate::run(function () use ($contribution) {
                    $item = LibraryItem::query()->lockForUpdate()->findOrFail($contribution->library_item_id);
                    if ($item->withdrawn_at === null) {
                        $item->update(['withdrawn_at' => now(), 'published_version_id' => null, 'revision' => $item->revision + 1]);
                    }
                });
            }
            Audit::record('library.contribution_withdrawn', $contribution, ['state' => $before], ['state' => 'withdrawn', 'library_item_id' => $contribution->library_item_id, 'changed_by' => $actor->id]);

            return $contribution->fresh();
        });
    }

    /**
     * The teacher's own contributions, newest first, with where each stands.
     *
     * @return list<array<string, mixed>>
     */
    public function mine(User $actor): array
    {
        $actor = app(SharedLibraryAccess::class)->teacher($actor);

        return $this->rows(LibraryContribution::query()->where('contributed_by', $actor->id)->orderByDesc('id')->limit(200)->get(), $actor);
    }

    /**
     * What a leader or content lead looks after: every contribution of the
     * grades they lead (a school leader: all), waiting ones first.
     *
     * @return list<array<string, mixed>>
     */
    public function school(User $actor): array
    {
        $actor = app(SharedLibraryAccess::class)->staff($actor);
        $rows = LibraryContribution::query()->orderByRaw("CASE WHEN state = 'awaiting_approval' THEN 0 ELSE 1 END")->orderByDesc('id')->limit(500)->get();
        $grades = PracticeItem::query()->whereIn('id', $rows->pluck('practice_item_id'))->pluck('grade_level_id', 'id');
        $rows = $rows->filter(fn (LibraryContribution $c) => app(SharedLibraryAccess::class)->leadsGrade($actor, (int) ($grades[$c->practice_item_id] ?? 0)));

        return $this->rows($rows->values(), $actor);
    }

    /** @return array{code: string, name: string, url: string} */
    public function licence(): array
    {
        return ['code' => (string) config('shared_library.licence.code'), 'name' => (string) config('shared_library.licence.name'), 'url' => (string) config('shared_library.licence.url')];
    }

    /** Where a contribution stands, from its own state and its library item's. */
    public function status(LibraryContribution $contribution, ?LibraryItem $item, ?LibraryItemVersion $latest): string
    {
        if ($contribution->state !== 'shared') {
            return $contribution->state;
        }
        if ($item === null) {
            return 'in_review';
        }
        if ($item->published_version_id !== null) {
            return 'published';
        }
        if ($item->unpublished_at !== null) {
            return 'unpublished';
        }

        return $latest?->state === 'rejected' ? 'not_accepted' : 'in_review';
    }

    /**
     * Copy the contribution into the library: a contributed source, the item
     * and version 1 waiting for review. The only school-initiated library
     * write (ContributionGate), scanned again as it leaves the school.
     */
    private function share(LibraryContribution $contribution): void
    {
        $version = PracticeItemVersion::query()->where('practice_item_id', $contribution->practice_item_id)->findOrFail($contribution->practice_item_version_id);
        $definition = app(PracticeAnswers::class)->definition((array) $version->definition, self::SHAREABLE_KINDS);
        app(SharedLibraryScan::class)->refuse([$contribution->title, $definition], 'question');
        $school = app(CurrentSchool::class)->id();
        $teacher = (int) $contribution->contributed_by;
        $now = now();
        [$item, $libraryVersion] = ContributionGate::run(function () use ($contribution, $definition, $version, $school, $teacher, $now) {
            $source = LibrarySource::query()->create(['title' => 'Shared by a NavigateEd school', 'kind' => 'contributed', 'licence' => $contribution->licence,
                'attribution' => 'Credited as the teacher chose', 'url' => $this->licence()['url'], 'created_by' => $teacher, 'contributor_school_id' => $school,
                'contributor_user_id' => $teacher, 'credit' => $contribution->credit, 'contributed_at' => $now]);
            $item = LibraryItem::query()->create(['library_topic_id' => $contribution->library_topic_id, 'library_source_id' => $source->id, 'kind' => 'practice_question',
                'title' => $contribution->title, 'revision' => 1, 'created_by' => $teacher]);
            $libraryVersion = LibraryItemVersion::query()->create(['library_item_id' => $item->id, 'number' => 1, 'state' => 'draft', 'content' => ['definition' => $definition],
                'estimated_seconds' => (int) $version->estimated_seconds, 'created_by' => $teacher, 'editor_ids' => [$teacher], 'origin' => 'contributed']);
            $libraryVersion->update(['state' => 'in_review', 'submitted_by' => $teacher, 'submitted_at' => $now]);

            return [$item, $libraryVersion];
        });
        $contribution->update(['state' => 'shared', 'library_item_id' => $item->id, 'shared_at' => $now]);
        Audit::record('library.contribution_shared', $contribution, [], ['library_item_id' => (int) $item->id, 'library_version_id' => (int) $libraryVersion->id, 'state' => 'in_review']);
    }

    /**
     * The teacher's own active question and its approved version. Another
     * school's question is not found; a colleague's is refused.
     *
     * @return array{0: PracticeItem, 1: PracticeItemVersion, 2: array<string, mixed>}
     */
    private function ownQuestion(User $actor, int $practiceItemId): array
    {
        $item = PracticeItem::query()->findOrFail($practiceItemId);
        abort_unless((int) $item->author_id === (int) $actor->id, 403, __('Only the teacher who wrote this question can share it.'));
        abort_unless($item->active, 409, __('Restore this question before sharing it.'));
        $version = PracticeItemVersion::query()->where('practice_item_id', $item->id)->orderByDesc('revision')->firstOrFail();

        return [$item, $version, app(PracticeAnswers::class)->definition((array) $version->definition)];
    }

    /** @return Collection<int, LibraryTopic> the library topics of the question's framework, subject and grade */
    private function topicChoices(PracticeItem $item): Collection
    {
        $group = TeachingGroup::query()->find($item->teaching_group_id);
        $scopes = $group === null ? [] : app(LibraryPractice::class)->scopes($group);
        if ($scopes === []) {
            return collect();
        }

        return LibraryTopic::query()->with('framework')->where('grade_level_id', $item->grade_level_id)->where(function ($query) use ($scopes) {
            foreach ($scopes as [$framework, $code]) {
                $query->orWhere(fn ($q) => $q->where('subject_code', $code)->whereHas('framework', fn ($f) => $f->where('code', $framework)));
            }
        })->orderBy('subject_code')->orderBy('name')->limit(500)->get();
    }

    private function live(int $practiceItemId): Builder
    {
        return LibraryContribution::query()->where('practice_item_id', $practiceItemId)->whereIn('state', ['awaiting_approval', 'shared']);
    }

    private function decidable(User $actor, int $id): LibraryContribution
    {
        $contribution = LibraryContribution::query()->lockForUpdate()->findOrFail($id);
        $grade = (int) PracticeItem::query()->whereKey($contribution->practice_item_id)->value('grade_level_id');
        abort_unless(app(SharedLibraryAccess::class)->leadsGrade($actor, $grade), 403, __('Only a school leader or a content lead of this grade can decide.'));
        abort_unless($contribution->state === 'awaiting_approval', 409, __('This contribution is not waiting for approval.'));

        return $contribution;
    }

    /**
     * @param  Collection<int, LibraryContribution>  $contributions
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $contributions, User $actor): array
    {
        $items = LibraryItem::query()->whereIn('id', $contributions->pluck('library_item_id')->filter())->get()->keyBy('id');
        $latest = LibraryItemVersion::query()->whereIn('id', LibraryItemVersion::query()->selectRaw('MAX(id)')->whereIn('library_item_id', $items->keys())->groupBy('library_item_id'))
            ->get()->keyBy('library_item_id');
        $topics = LibraryTopic::query()->with('framework')->whereIn('id', $contributions->pluck('library_topic_id'))->get()->keyBy('id');
        $grades = GradeLevel::query()->pluck('name', 'id');
        $names = DB::table('users')->whereIn('id', $contributions->pluck('contributed_by')->unique())->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all();
        $ratings = app(LibraryRatings::class)->shown($items->keys()->map(fn ($id) => (int) $id)->all());
        $practice = PracticeItem::query()->whereIn('id', $contributions->pluck('practice_item_id'))->pluck('grade_level_id', 'id');
        $access = app(SharedLibraryAccess::class);

        return $contributions->map(function (LibraryContribution $c) use ($items, $latest, $topics, $grades, $names, $ratings, $practice, $actor, $access) {
            $item = $c->library_item_id === null ? null : $items->get($c->library_item_id);
            $status = $this->status($c, $item, $item === null ? null : $latest->get($item->id));
            $topic = $topics->get($c->library_topic_id);
            $leads = $access->leadsGrade($actor, (int) ($practice[$c->practice_item_id] ?? 0));
            $own = (int) $c->contributed_by === (int) $actor->id;

            return ['id' => (int) $c->id, 'practice_item_id' => (int) $c->practice_item_id, 'title' => $c->title, 'state' => $c->state, 'status' => $status,
                'status_label' => __(self::STATUS[$status]), 'credit' => $c->credit, 'credit_label' => __(LibraryContribution::CREDITS[$c->credit] ?? $c->credit),
                'licence' => $c->licence, 'teacher' => $names[$c->contributed_by] ?? '', 'mine' => $own,
                'topic' => $topic === null ? null : ['name' => $topic->name, 'framework' => (string) $topic->framework?->code, 'subject_code' => $topic->subject_code,
                    'grade' => (string) $grades->get($topic->grade_level_id)],
                'library_item_id' => $status === 'published' ? (int) $c->library_item_id : null,
                'rating' => $item === null ? null : ($ratings[(int) $item->id] ?? null),
                'decline_reason' => $c->state === 'declined' ? $c->decline_reason : null,
                'contributed_at' => $c->created_at?->toIso8601String(),
                'can_decide' => $c->state === 'awaiting_approval' && $leads && ! $own,
                'can_withdraw' => in_array($c->state, ['awaiting_approval', 'shared'], true) && ($own || $leads)];
        })->values()->all();
    }
}
