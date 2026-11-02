<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
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
        $content = app(LibraryContent::class)->normalise($data['kind'], $topic, $input);

        return DB::transaction(function () use ($actor, $topic, $source, $data, $content) {
            $item = LibraryItem::create(['library_topic_id' => $topic->id, 'library_source_id' => $source->id, 'kind' => $data['kind'],
                'title' => trim($data['title']), 'revision' => 1, 'created_by' => $actor->id]);
            $version = LibraryItemVersion::create(['library_item_id' => $item->id, 'number' => 1, 'state' => 'draft', 'content' => $content['content'],
                'estimated_seconds' => $content['estimated_seconds'], 'created_by' => $actor->id]);
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

        return DB::transaction(function () use ($actor, $itemId, $data, $input) {
            $item = $this->locked($itemId, (int) $data['revision']);
            $content = app(LibraryContent::class)->normalise($item->kind, $item->topic, $input);
            $latest = $this->latest($item);
            abort_if($latest->state === 'in_review', 409, 'This item is waiting for review. Wait for the decision before changing it.');
            $item->update(['title' => trim($data['title']), 'revision' => $item->revision + 1]);
            if ($latest->state === 'draft') {
                $latest->update(['content' => $content['content'], 'estimated_seconds' => $content['estimated_seconds']]);
                Audit::record('library_draft_saved', $item, [], ['version_id' => $latest->id, 'number' => $latest->number, 'changed_by' => $actor->id]);

                return $latest;
            }
            $draft = LibraryItemVersion::create(['library_item_id' => $item->id, 'number' => $latest->number + 1, 'state' => 'draft', 'content' => $content['content'],
                'estimated_seconds' => $content['estimated_seconds'], 'created_by' => $actor->id]);
            Audit::record('library_draft_started', $item, ['version_id' => $latest->id, 'state' => $latest->state], ['version_id' => $draft->id, 'number' => $draft->number, 'changed_by' => $actor->id]);

            return $draft;
        });
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
            $refusal = app(LibraryReviewers::class)->refusal($actor, $version);
            abort_if($refusal !== null, 403, (string) $refusal);
            $review = LibraryReview::create(['library_item_version_id' => $version->id, 'reviewer_id' => $actor->id, 'decision' => $data['decision'],
                'reason' => isset($data['reason']) ? trim((string) $data['reason']) : null]);
            $version->update(['state' => $data['decision']]);
            $item->update(['revision' => $item->revision + 1]);
            Audit::record('library_reviewed', $item, ['state' => 'in_review'], ['version_id' => $version->id, 'review_id' => $review->id, 'state' => $data['decision'], 'changed_by' => $actor->id]);

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
            abort_if($item->published_version_id !== null && (int) LibraryItemVersion::query()->whereKey($item->published_version_id)->value('number') > $version->number,
                409, 'A later version is already published.');
            $before = $item->published_version_id;
            $version->update(['state' => 'published', 'published_by' => $actor->id, 'published_at' => now()]);
            $item->update(['published_version_id' => $version->id, 'revision' => $item->revision + 1]);
            Audit::record('library_published', $item, ['published_version_id' => $before], ['published_version_id' => $version->id, 'number' => $version->number, 'changed_by' => $actor->id]);

            return $version;
        });
    }

    public function latest(LibraryItem $item): LibraryItemVersion
    {
        return LibraryItemVersion::query()->where('library_item_id', $item->id)->orderByDesc('number')->firstOrFail();
    }

    private function locked(int $itemId, int $revision): LibraryItem
    {
        $item = LibraryItem::query()->with('topic')->lockForUpdate()->findOrFail($itemId);
        abort_unless($item->revision === $revision, 409, 'This item changed. Reload it before saving.');

        return $item;
    }
}
