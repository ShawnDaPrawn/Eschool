<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Activities\Chat\GroupChatRead;
use App\Models\Activities\Chat\GroupChatThread;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Activities\ActivityOperations;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GroupChatThreads
{
    public function __construct(private GroupChatAccess $access, private GroupChatRules $rules) {}

    public function persona(array $input): string
    {
        return is_string($input['persona'] ?? null) ? $input['persona'] : '';
    }

    public function fingerprint(string $text): string
    {
        return hash_hmac('sha256', $text, (string) config('app.key'));
    }

    public function create(User $actor, int $group, array $input): array
    {
        $persona = $this->persona($input);
        $this->access->group($actor, $group, $persona, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'title' => ['required', 'string', 'max:191'],
            'kind' => ['required', 'in:question,announcement'], 'audience' => ['sometimes', 'in:learners,guardians,staff']])->validate();
        $text = app(GroupChatMessages::class)->validate($input);
        $audience = $persona === 'parent' ? 'guardians' : ($persona === 'student' ? 'learners' : ($data['audience'] ?? 'staff'));
        abort_unless($persona === 'staff' || $data['kind'] === 'question', 403);
        abort_unless(in_array($audience, $this->access->audiences($persona, $this->rules->current()), true), 403);
        $private = $data['kind'] === 'question';
        $operation = ['operation_id' => $data['operation_id'], 'persona' => $persona, 'kind' => $data['kind'], 'audience' => $audience,
            'title_hash' => $this->fingerprint($data['title']), 'body_hash' => $this->fingerprint(json_encode([$text['body'], $text['language']], JSON_THROW_ON_ERROR))];
        $authorize = function () use ($actor, $group, $persona, $audience) {
            $this->access->group($actor, $group, $persona, true);
            abort_unless(in_array($audience, $this->access->audiences($persona, $this->rules->current()), true), 403);
        };

        return app(ActivityOperations::class)->once($actor, 'chat.thread.create:'.$group, $operation, $authorize,
            function () use ($actor, $group, $persona, $audience, $private, $data, $text, $authorize) {
                $thread = GroupChatThread::create(['id' => (string) Str::uuid(), 'activity_group_id' => $group, 'created_by' => $actor->id,
                    'audience' => $audience, 'private' => $private, 'owner_id' => $private ? $actor->id : null, 'owner_persona' => $private ? $persona : null, 'title' => $data['title'],
                    'closed' => false, 'revision' => 1, 'opened_at' => now()->utc(), 'last_message_at' => now()->utc()]);
                $message = app(GroupChatMessages::class)->record($actor, $group, $thread->id, $persona,
                    $text + ['visibility' => $persona === 'staff' ? 'thread' : 'staff', 'reply_to' => null]);
                $authorize();
                Audit::record('activity.chat_thread_created', $thread, [], ['group_id' => $group, 'audience' => $audience, 'private' => $private]);

                return ['thread_id' => $thread->id, 'message_id' => (int) $message->id, 'activity_group_id' => $group, 'revision' => 1];
            });
    }

    public function metadata(GroupChatThread $thread): array
    {
        return ['id' => $thread->id, 'activity_group_id' => (int) $thread->activity_group_id, 'title' => $thread->title,
            'audience' => $thread->audience, 'private' => $thread->private, 'closed' => $thread->closed, 'revision' => $thread->revision,
            'last_message_at' => $thread->last_message_at->toIso8601String()];
    }

    public function listing(User $actor, int $group, string $persona, ?string $before = null): array
    {
        $this->access->group($actor, $group, $persona);
        $policy = $this->rules->current();
        $query = $this->access->threads($actor, $group, $persona);
        if ($before !== null) {
            $cursor = $this->access->thread($actor, $group, $before, $persona);
            $query->where(fn ($q) => $q->where('last_message_at', '<', $cursor->last_message_at->utc())
                ->orWhere(fn ($q) => $q->where('last_message_at', $cursor->last_message_at->utc())->where('id', '<', $cursor->id)));
        }
        $rows = $query->orderByDesc('last_message_at')->orderByDesc('id')->limit(25)->get();
        $items = [];
        foreach ($rows as $thread) {
            // Recheck each captured row before serialization, including private titles.
            $fresh = $this->access->thread($actor, $group, $thread->id, $persona);
            $last = (int) (GroupChatRead::where('thread_id', $thread->id)->where('user_id', $actor->id)->where('persona', $persona)->value('last_message_id') ?? 0);
            $unread = $this->access->messages($actor, $group, $thread->id, $persona)->where('id', '>', $last)->whereNull('removed_at')->count();
            $items[] = $this->metadata($fresh) + ['unread' => $unread];
        }
        $this->access->group($actor, $group, $persona);
        abort_unless($policy === $this->rules->current(), 409, 'Group chat rules changed. Refresh this conversation.');

        return ['items' => $items, 'next_before' => $rows->count() === 25 ? $rows->last()->id : null, 'policy' => $policy,
            'persona' => $persona, 'max_characters' => $this->rules->maxCharacters()];
    }

    public function close(User $actor, int $group, string $id, array $input): array
    {
        $persona = $this->persona($input);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'closed' => ['required', 'boolean']])->validate();
        $authorize = function () use ($actor, $group, $id, $persona, $data) {
            $thread = $this->access->thread($actor, $group, $id, $persona, ! $data['closed']);
            abort_unless($persona === 'staff' || ($thread->private && (int) $thread->owner_id === (int) $actor->id && $data['closed']), 403);
        };
        $authorize();

        return app(ActivityOperations::class)->once($actor, 'chat.thread.state:'.$id, $data + ['persona' => $persona], $authorize,
            function () use ($group, $id, $data, $authorize) {
                $thread = GroupChatThread::findOrFail($id);
                abort_unless($thread->revision === (int) $data['revision'], 409, 'Refresh this conversation before changing its state.');
                $thread->closed = (bool) $data['closed'];
                $thread->revision++;
                $thread->save();
                $authorize();
                Audit::record('activity.chat_thread_state', $thread, [], ['closed' => $thread->closed, 'revision' => $thread->revision]);

                return ['thread_id' => $id, 'activity_group_id' => $group, 'revision' => $thread->revision, 'closed' => $thread->closed];
            });
    }

    public function prune(): int
    {
        return DB::transaction(function () {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $cutoff = now()->utc()->subDays($this->rules->retentionDays());
            $count = 0;
            foreach (GroupChatMessage::where('sent_at', '<', $cutoff)->whereNull('removed_at')->orderBy('id')->limit(500)->get() as $message) {
                $message->forceFill(['body' => null, 'removal_reason' => null, 'removed_at' => now()->utc(), 'revision' => $message->revision + 1])->save();
                $count++;
            }
            foreach (GroupChatThread::where('last_message_at', '<', $cutoff)->whereNull('redacted_at')->limit(500)->get() as $thread) {
                $thread->forceFill(['title' => null, 'closed' => true, 'redacted_at' => now()->utc(), 'revision' => $thread->revision + 1])->save();
                $count++;
            }

            return $count;
        });
    }
}
