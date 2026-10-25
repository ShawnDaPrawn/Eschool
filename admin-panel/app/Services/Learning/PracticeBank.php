<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

/** A teacher explicitly approves each immutable definition before a learner can use it. */
class PracticeBank
{
    public function approve(User $actor, int $groupId, ?int $itemId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'topic_id' => ['required', 'integer', 'min:1'], 'content_version_id' => ['required', 'integer', 'min:1'],
            'estimated_seconds' => ['required', 'integer', 'between:60,300'], 'definition' => ['required', 'array'], 'active' => ['required', 'boolean']])->validate();
        $data['definition'] = app(PracticeAnswers::class)->definition($data['definition']);
        $authorize = function () use ($actor, $groupId, $data) {
            $group = app(PracticeAccess::class)->staff($actor, $groupId, true);
            app(PracticeAccess::class)->topic($group, $data['topic_id'], true);
            $source = app(ContentPolicy::class)->version($actor, $data['content_version_id']);
            abort_unless($source->state === 'published' && $source->item->active && (int) $source->item->published_version_id === (int) $source->id
                && (int) $source->item->subject_id === (int) $group->subject_id && $source->item->audienceGroups()->where('teaching_group_id', $groupId)->exists(), 404);
        };

        return app(PracticeOperations::class)->run($actor, $data + ['group_id' => $groupId, 'item_id' => $itemId], $authorize, function () use ($actor, $groupId, $itemId, $data) {
            $item = $itemId === null ? new PracticeItem(['teaching_group_id' => $groupId, 'topic_id' => $data['topic_id'], 'revision' => 0])
                : PracticeItem::where('teaching_group_id', $groupId)->lockForUpdate()->findOrFail($itemId);
            abort_unless((int) $item->revision === (int) $data['expected_revision'], 409, 'The approved question changed. Reload before editing.');
            // Identity/topic cannot move underneath retained learner review memory.
            abort_unless((int) $item->topic_id === (int) $data['topic_id'], 409, 'Create a separate question for another topic.');
            $item->fill(['active' => $data['active'], 'revision' => (int) $item->revision + 1])->save();
            $version = PracticeItemVersion::create(['practice_item_id' => $item->id, 'content_version_id' => $data['content_version_id'],
                'approved_by' => $actor->id, 'revision' => $item->revision, 'estimated_seconds' => $data['estimated_seconds'], 'definition' => $data['definition']]);

            Audit::record('learning.practice_approved', $item, [], ['revision' => $item->revision, 'version_id' => $version->id, 'active' => $item->active]);

            return ['id' => (int) $item->id, 'version_id' => (int) $version->id, 'revision' => (int) $item->revision, 'active' => $item->active];
        });
    }

    public function catalogue(User $actor, int $groupId, int $page = 1): array
    {
        app(PracticeAccess::class)->staff($actor, $groupId);
        $items = PracticeItem::where('teaching_group_id', $groupId)->orderByDesc('id')->paginate(25, ['id', 'topic_id', 'revision', 'active'], 'page', $page);
        $versions = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items->pluck('id'))->groupBy('practice_item_id'))->get()->keyBy('practice_item_id');
        $result = $items->map(fn ($item) => ['id' => (int) $item->id, 'topic_id' => (int) $item->topic_id, 'revision' => (int) $item->revision, 'active' => $item->active,
            'content_version_id' => (int) $versions[$item->id]->content_version_id, 'estimated_seconds' => (int) $versions[$item->id]->estimated_seconds,
            'definition' => $versions[$item->id]->definition])->all();
        app(PracticeAccess::class)->staff($actor, $groupId);

        return ['items' => $result, 'page' => $items->currentPage(), 'last_page' => $items->lastPage()];
    }
}
