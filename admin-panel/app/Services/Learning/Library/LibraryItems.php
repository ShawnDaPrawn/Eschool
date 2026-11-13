<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibraryReviewer;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\User;
use App\Services\Learning\PracticeMedia;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Library items and their versions (Action Plan 3.3, SA.16). Content staff
 * create an item as a draft and submit it; a reviewer assigned to the subject
 * approves or rejects it with a reason; Content staff publish an approved
 * version. Editing an item whose latest version is no longer a draft starts a
 * new draft version, so a published version never changes. Every step is
 * audited with ids and states.
 */
class LibraryItems
{
    public static function createRules(): array
    {
        return ['library_topic_id' => ['required', 'integer', 'min:1'], 'kind' => ['required', Rule::in(array_keys(LibraryContent::KINDS))], 'title' => ['required', 'string', 'min:3', 'max:191'],
            'library_source_id' => ['required', 'integer', 'min:1']] + LibraryContent::rules();
    }

    public static function draftRules(): array
    {
        return ['title' => ['required', 'string', 'min:3', 'max:191'], 'revision' => ['required', 'integer', 'min:1']] + LibraryContent::rules();
    }

    public static function stepRules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1']];
    }

    public static function publishRules(): array
    {
        return ['version_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public static function unpublishRules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:10', 'max:1000']];
    }

    public static function reviewRules(): array
    {
        return ['decision' => ['required', Rule::in(['approved', 'rejected'])],
            'reason' => ['nullable', 'required_if:decision,rejected', 'string', 'min:10', 'max:1000']];
    }

    public function create(User $actor, array $input): LibraryItem
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::createRules())->validate();
        $topic = LibraryTopic::query()->find($data['library_topic_id']);
        if ($topic === null) {
            throw ValidationException::withMessages(['library_topic_id' => 'Choose a library topic.']);
        }
        $source = LibrarySource::query()->find($data['library_source_id']);
        if ($source === null) {
            throw ValidationException::withMessages(['library_source_id' => 'Choose the source and licence of this content.']);
        }
        if ($data['kind'] === 'past_paper_question' && ! in_array($source->kind, LibraryContent::PAST_PAPER_SOURCES, true)) {
            throw ValidationException::withMessages(['library_source_id' => 'A past-paper question needs a DBE, IEB or licensed source.']);
        }
        $content = app(LibraryContent::class)->normalise($data['kind'], $topic, $this->diagram($actor, $data['kind'], $input));

        return DB::transaction(function () use ($actor, $topic, $source, $data, $content) {
            $item = LibraryItem::create(['library_topic_id' => $topic->id, 'library_source_id' => $source->id, 'kind' => $data['kind'],
                'title' => trim($data['title']), 'revision' => 1, 'created_by' => $actor->id]);
            $version = LibraryItemVersion::create(['library_item_id' => $item->id, 'number' => 1, 'state' => 'draft', 'content' => $content['content'],
                'estimated_seconds' => $content['estimated_seconds'], 'created_by' => $actor->id, 'editor_ids' => [(int) $actor->id]]);
            Audit::record('library_item_created', $item, [], ['kind' => $item->kind, 'topic_id' => $topic->id, 'source_id' => $source->id, 'version_id' => $version->id, 'changed_by' => $actor->id]);

            return $item;
        });
    }

    /**
     * Change the item's draft. When the latest version is approved, published
     * or rejected, this starts a new draft version instead; while one is in
     * review it is refused.
     */
    public function saveDraft(User $actor, int $itemId, array $input): LibraryItemVersion
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::draftRules())->validate();
        $kind = (string) LibraryItem::query()->whereKey($itemId)->value('kind');
        $input = $this->diagram($actor, $kind, $input);

        return DB::transaction(function () use ($actor, $itemId, $data, $input) {
            $item = $this->locked($itemId, (int) $data['revision']);
            $content = app(LibraryContent::class)->normalise($item->kind, $item->topic, $input);
            $latest = $this->latest($item);
            abort_if($latest->state === 'in_review', 409, 'This item is waiting for review. Wait for the decision before changing it.');
            $item->update(['title' => trim($data['title']), 'revision' => $item->revision + 1]);
            if ($latest->state === 'draft') {
                // Whoever changes a draft becomes one of its editors, and so can never review it.
                $latest->update(['content' => $content['content'], 'estimated_seconds' => $content['estimated_seconds'],
                    'editor_ids' => array_values(array_unique([...$latest->editors(), (int) $actor->id]))]);
                Audit::record('library_draft_saved', $item, [], ['version_id' => $latest->id, 'number' => $latest->number, 'changed_by' => $actor->id]);

                return $latest;
            }
            $draft = $this->nextDraft($actor, $item, $latest, $content['content'], $content['estimated_seconds']);
            Audit::record('library_draft_started', $item, ['version_id' => $latest->id, 'state' => $latest->state], ['version_id' => $draft->id, 'number' => $draft->number, 'changed_by' => $actor->id]);

            return $draft;
        });
    }

    /**
     * Start the next draft from the latest version's content unchanged (Action
     * Plan 3.4): how HQ opens a new version of an item learners' answers have
     * flagged. Refused while a draft or a version waiting for review exists.
     */
    public function redraft(User $actor, int $itemId, array $input): LibraryItemVersion
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::stepRules())->validate();

        return DB::transaction(function () use ($actor, $itemId, $data) {
            $item = $this->locked($itemId, (int) $data['revision']);
            $latest = $this->latest($item);
            abort_if(in_array($latest->state, ['draft', 'in_review'], true), 409, 'This item already has a draft or a version waiting for review. Open the item to work on it.');
            $item->update(['revision' => $item->revision + 1]);
            $draft = $this->nextDraft($actor, $item, $latest, $latest->content, $latest->estimated_seconds);
            Audit::record('library_draft_started', $item, ['version_id' => $latest->id, 'state' => $latest->state], ['version_id' => $draft->id, 'number' => $draft->number,
                'reason' => 'needs_attention', 'changed_by' => $actor->id]);

            return $draft;
        });
    }

    /**
     * A new draft starts from the content before it, so that version's editors
     * carry forward, and so does its origin: content that began as an AI draft
     * stays labelled as one, however it is edited.
     */
    private function nextDraft(User $actor, LibraryItem $item, LibraryItemVersion $latest, array $content, ?int $seconds): LibraryItemVersion
    {
        return LibraryItemVersion::create(['library_item_id' => $item->id, 'number' => $latest->number + 1, 'state' => 'draft', 'content' => $content,
            'estimated_seconds' => $seconds, 'created_by' => $actor->id, 'editor_ids' => array_values(array_unique([...$latest->editors(), (int) $actor->id])),
            'origin' => (string) ($latest->origin ?? 'staff'), 'library_ai_draft_id' => $latest->library_ai_draft_id]);
    }

    public function submit(User $actor, int $itemId, array $input): LibraryItemVersion
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::stepRules())->validate();

        return DB::transaction(function () use ($actor, $itemId, $data) {
            $item = $this->locked($itemId, (int) $data['revision']);
            $latest = $this->latest($item);
            abort_unless($latest->state === 'draft', 409, 'Only a draft can be sent for review.');
            $latest->update(['state' => 'in_review', 'submitted_by' => $actor->id, 'submitted_at' => now()]);
            $item->update(['revision' => $item->revision + 1]);
            Audit::record('library_submitted', $item, ['state' => 'draft'], ['version_id' => $latest->id, 'state' => 'in_review', 'changed_by' => $actor->id]);

            return $latest;
        });
    }

    /** A reviewer assigned to the item's subject approves or rejects it; a rejection carries a reason. */
    public function review(User $actor, int $versionId, array $input): LibraryReview
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::REVIEW);
        $data = Validator::make($input, self::reviewRules())->validate();

        return DB::transaction(function () use ($actor, $versionId, $data) {
            $version = LibraryItemVersion::query()->with('item.topic')->findOrFail($versionId);
            $item = LibraryItem::query()->lockForUpdate()->findOrFail($version->library_item_id);
            $version = LibraryItemVersion::query()->with('item.topic')->findOrFail($versionId);
            abort_unless($version->state === 'in_review', 409, 'This version is not waiting for review.');
            abort_if($item->withdrawn_at !== null, 409, 'Its school withdrew this shared item.');
            $refusal = app(LibraryReviewers::class)->refusal($actor, $version);
            abort_if($refusal !== null, 403, (string) $refusal);
            $review = LibraryReview::create(['library_item_version_id' => $version->id, 'reviewer_id' => $actor->id, 'decision' => $data['decision'],
                'reason' => isset($data['reason']) ? trim((string) $data['reason']) : null]);
            $version->update(['state' => $data['decision']]);
            $item->update(['revision' => $item->revision + 1]);
            Audit::record('library_reviewed', $item, ['state' => 'in_review'], ['version_id' => $version->id, 'review_id' => $review->id, 'state' => $data['decision'],
                'origin' => $version->origin, 'changed_by' => $actor->id] + ($version->drafted() ? ['reviewed_by' => $this->name((int) $actor->id)] : []));

            return $review;
        });
    }

    /** Publish an approved version: learners at schools with the library switched on may then practise it. */
    public function publish(User $actor, int $itemId, array $input): LibraryItemVersion
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::publishRules())->validate();

        return DB::transaction(function () use ($actor, $itemId, $data) {
            $item = $this->locked($itemId, (int) $data['revision']);
            $version = LibraryItemVersion::query()->where('library_item_id', $item->id)->find($data['version_id']);
            abort_if($version === null, 404);
            abort_unless($version->state === 'approved', 409, 'Only an approved version can be published.');
            // Never older than any version published before, including one a reviewer has since unpublished (Action Plan 5.4).
            $last = LibraryItemVersion::query()->where('library_item_id', $item->id)->where('state', 'published')->max('number');
            abort_if($last !== null && (int) $last > $version->number, 409, 'A later version is already published.');
            $before = $item->published_version_id;
            $version->update(['state' => 'published', 'published_by' => $actor->id, 'published_at' => now()]);
            $item->update(['published_version_id' => $version->id, 'revision' => $item->revision + 1, 'unpublished_at' => null, 'unpublished_by' => null, 'unpublish_reason' => null]);
            $approval = LibraryReview::query()->where('library_item_version_id', $version->id)->where('decision', 'approved')->latest('id')->first();
            Audit::record('library_published', $item, ['published_version_id' => $before], ['published_version_id' => $version->id, 'number' => $version->number,
                'origin' => $version->origin, 'changed_by' => $actor->id] + ($version->drafted() ? ['reviewed_by' => $this->name((int) $approval?->reviewer_id)] : []));

            return $version;
        });
    }

    /**
     * A reviewer assigned to the item's subject takes a published item out of
     * the library (Action Plan 5.4), with a reason: no school is offered it
     * again and practice sets stop using it. Its versions stay as they were,
     * so evidence that used one keeps pointing at it. Content can publish a
     * later version after review.
     */
    public function unpublish(User $actor, int $itemId, array $input): LibraryItem
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::REVIEW);
        $data = Validator::make($input, self::unpublishRules())->validate();

        return DB::transaction(function () use ($actor, $itemId, $data) {
            $item = LibraryItem::query()->with('topic')->lockForUpdate()->findOrFail($itemId);
            abort_unless($item->revision === (int) $data['revision'], 409, 'This item changed. Reload it before saving.');
            abort_unless(LibraryReviewer::query()->where('user_id', $actor->id)->where('library_framework_id', $item->topic->library_framework_id)
                ->where('subject_code', $item->topic->subject_code)->exists(), 403, 'You are not a reviewer for this subject.');
            abort_if($item->published_version_id === null, 409, 'This item is not published.');
            $before = $item->published_version_id;
            $item->update(['published_version_id' => null, 'unpublished_at' => now(), 'unpublished_by' => $actor->id, 'unpublish_reason' => trim((string) $data['reason']),
                'revision' => $item->revision + 1]);
            Audit::record('library_unpublished', $item, ['published_version_id' => $before], ['published_version_id' => null, 'reason' => $item->unpublish_reason, 'changed_by' => $actor->id]);

            return $item;
        });
    }

    /**
     * A labelling question's new diagram picture (Action Plan 3.11) is
     * checked and stored first; the content then names it by id. Without a
     * new picture, a draft keeps the one it names.
     */
    private function diagram(User $actor, string $kind, array $input): array
    {
        $file = $input['diagram'] ?? null;
        unset($input['diagram']);
        if ($file instanceof UploadedFile && in_array($kind, LibraryContent::QUESTIONS, true) && ($input['question_kind'] ?? null) === 'diagram_label') {
            Validator::make(['diagram' => $file], PracticeMedia::diagramRules('diagram'))->validate();
            $input['diagram_id'] = (string) app(PracticeMedia::class)->storeLibraryDiagram($actor, $file)->id;
        }

        return $input;
    }

    public function latest(LibraryItem $item): LibraryItemVersion
    {
        return LibraryItemVersion::query()->where('library_item_id', $item->id)->orderByDesc('number')->firstOrFail();
    }

    /** A platform staff member's name, for the audit of an AI draft's review. */
    private function name(int $userId): string
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->find($userId, ['id', 'first_name', 'last_name']));

        return $user === null ? '' : trim($user->first_name.' '.$user->last_name);
    }

    private function locked(int $itemId, int $revision): LibraryItem
    {
        $item = LibraryItem::query()->with('topic')->lockForUpdate()->findOrFail($itemId);
        abort_unless($item->revision === $revision, 409, 'This item changed. Reload it before saving.');
        abort_if($item->withdrawn_at !== null, 409, 'Its school withdrew this shared item. It cannot be changed or published again.');

        return $item;
    }
}
