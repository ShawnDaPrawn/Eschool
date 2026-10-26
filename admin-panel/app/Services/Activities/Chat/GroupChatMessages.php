<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Events\Activities\GroupChatMessagePosted;
use App\Jobs\Platform\DispatchOutbox;
use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Activities\Chat\GroupChatRead;
use App\Models\Activities\Chat\GroupChatThread;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Activities\ActivityOperations;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GroupChatMessages
{
    public function __construct(private GroupChatAccess $access, private GroupChatRules $rules) {}

    public function validate(array $input): array
    {
        return Validator::make($input, ['body' => ['required', 'string', 'max:'.$this->rules->maxCharacters()],
            'language' => ['required', 'string', 'regex:/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8}){0,2}$/D', 'max:20']])->validate();
    }

    private function writable(User $actor, int $group, string $id, string $persona): GroupChatThread
    {
        $thread = $this->access->thread($actor, $group, $id, $persona, true);
        abort_if($thread->closed, 409, 'This conversation is closed.');

        return $thread;
    }

    private function envelope(User $actor, int $group, string $id, string $persona, array $input): array
    {
        $thread = $this->writable($actor, $group, $id, $persona);
        $data = Validator::make($input, ['visibility' => ['required', 'in:thread,staff'], 'reply_to' => ['nullable', 'integer', 'min:1']])->validate();
        $private = null;
        $visibility = $data['visibility'];
        $reply = null;
        if (($data['reply_to'] ?? null) !== null) {
            $reply = $this->access->messages($actor, $group, $id, $persona)->whereKey($data['reply_to'])->whereNull('removed_at')->firstOrFail();
        }
        if ($persona !== 'staff') {
            $shared = $this->rules->current()[$persona === 'parent' ? 'guardian_shared_replies' : 'learner_shared_replies'];
            abort_unless($visibility === 'staff' || $thread->private || $shared, 403, 'Your school limits family replies to group staff.');
            $private = $visibility === 'staff' ? (int) $actor->id : null;
            // A public reply cannot expose a private conversation reference.
            abort_if($visibility === 'thread' && ! $thread->private && $reply?->visibility === 'staff', 422);
        } elseif ($reply?->private_user_id !== null) {
            $private = (int) $reply->private_user_id;
            $recipientPersona = $thread->audience === 'guardians' ? 'parent' : 'student';
            $this->access->group(User::findOrFail($private), $group, $recipientPersona);
            $visibility = 'staff';
        } else {
            abort_unless($visibility === 'thread', 422, 'Choose an available private family message to reply to.');
        }

        return [$thread, $visibility, $private, $reply];
    }

    /** Internal transactional append; fresh authority and text limits still apply. */
    public function record(User $actor, int $group, string $id, string $persona, array $input): GroupChatMessage
    {
        abort_unless(DB::transactionLevel() > 0, 409);
        $text = $this->validate($input);
        [$thread, $visibility, $private, $reply] = $this->envelope($actor, $group, $id, $persona, $input);
        $message = GroupChatMessage::create(['thread_id' => $id, 'sender_id' => $actor->id, 'sender_persona' => $persona,
            'private_user_id' => $private, 'visibility' => $visibility, 'reply_to' => $reply?->id, 'body' => $text['body'], 'language' => $text['language'],
            'source_hash' => app(GroupChatThreads::class)->fingerprint(json_encode([$text['body'], $text['language']], JSON_THROW_ON_ERROR)),
            'revision' => 1, 'sent_at' => now()->utc()]);
        $thread->last_message_at = now()->utc();
        $thread->save();
        $this->writable($actor, $group, $id, $persona);
        Outbox::record(new GroupChatMessagePosted(app(CurrentSchool::class)->id(), $group, $id, (int) $message->id, (int) $message->revision));
        DB::afterCommit(static function () {
            try {
                DispatchOutbox::dispatch();
            } catch (Throwable $error) {
                Log::warning('group_chat_notice_recovery_pending', ['exception' => $error::class]);
            }
        });

        return $message;
    }

    public function send(User $actor, int $group, string $id, array $input): array
    {
        $persona = app(GroupChatThreads::class)->persona($input);
        $this->writable($actor, $group, $id, $persona);
        $text = $this->validate($input);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'visibility' => ['required', 'in:thread,staff'], 'reply_to' => ['nullable', 'integer', 'min:1']])->validate();
        $operation = ['operation_id' => $data['operation_id'], 'persona' => $persona, 'visibility' => $data['visibility'],
            'reply_to' => $data['reply_to'] ?? null, 'body_hash' => app(GroupChatThreads::class)->fingerprint(json_encode([$text['body'], $text['language']], JSON_THROW_ON_ERROR))];

        return app(ActivityOperations::class)->once($actor, 'chat.message.send:'.$id, $operation,
            fn () => $this->envelope($actor, $group, $id, $persona, $text + $data), function () use ($actor, $group, $id, $persona, $text, $data) {
                $message = $this->record($actor, $group, $id, $persona, $text + $data);
                Audit::record('activity.chat_message_sent', $message, [], ['group_id' => $group, 'thread_id' => $id, 'visibility' => $message->visibility]);

                return ['operation_type' => 'message_sent', 'persona' => $persona, 'thread_id' => $id, 'message_id' => (int) $message->id, 'activity_group_id' => $group, 'revision' => 1];
            });
    }

    public function read(User $actor, int $group, string $id, string $persona, ?int $before = null): array
    {
        $thread = $this->access->thread($actor, $group, $id, $persona);
        $policy = $this->rules->current();
        $rows = $this->access->messages($actor, $group, $id, $persona)->when($before !== null, fn ($q) => $q->where('id', '<', $before))
            ->orderByDesc('id')->limit(50)->get();
        $allowed = $this->access->messages($actor, $group, $id, $persona)->whereIn('id', $rows->pluck('id'))->pluck('id')->map(fn ($value) => (int) $value)->all();
        $items = [];
        foreach ($rows->reverse() as $message) {
            if (! in_array((int) $message->id, $allowed, true)) {
                continue;
            }
            $label = User::select(['id', 'first_name', 'last_name'])->find($message->sender_id)?->full_name;
            $items[] = ['id' => (int) $message->id, 'sender_id' => (int) $message->sender_id, 'sender_name' => $label ?? 'Former participant',
                'sender_persona' => $message->sender_persona, 'body' => $message->removed_at === null ? $message->body : null,
                'language' => $message->language, 'revision' => $message->revision, 'reply_to' => $message->reply_to,
                'private' => $thread->private || $message->visibility === 'staff', 'removed' => $message->removed_at !== null,
                'can_remove' => $message->removed_at === null && ($persona === 'staff' || ((int) $message->sender_id === (int) $actor->id && $message->sender_persona === $persona)),
                'sent_at' => $message->sent_at->toIso8601String()];
        }
        $writable = $this->access->writable($actor, $group, $persona);
        $thread = $this->access->thread($actor, $group, $id, $persona);
        abort_unless($policy === $this->rules->current(), 409, 'Group chat rules changed. Refresh this conversation.');

        return ['thread' => app(GroupChatThreads::class)->metadata($thread), 'items' => $items, 'policy' => $policy,
            'next_before' => $rows->count() === 50 ? (int) $rows->last()->id : null,
            'can_post' => ! $thread->closed && $writable, 'can_reopen' => $persona === 'staff' && $thread->closed && $writable,
            'can_close' => $persona === 'staff' || ($thread->private && (int) $thread->owner_id === (int) $actor->id),
            'max_characters' => $this->rules->maxCharacters()];
    }

    /** Exact, fresh source contract for a later allowlisted translation adapter. */
    public function source(User $actor, int $group, string $thread, int $message, string $persona): array
    {
        $row = $this->access->messages($actor, $group, $thread, $persona)->whereKey($message)->whereNull('removed_at')->firstOrFail();
        abort_unless($this->access->messages($actor, $group, $thread, $persona)->whereKey($message)->whereNull('removed_at')->exists(), 404);

        return ['id' => (int) $row->id, 'activity_group_id' => $group, 'thread_id' => $thread, 'body' => $row->body,
            'language' => $row->language, 'source_hash' => $row->source_hash, 'revision' => $row->revision];
    }

    public function remove(User $actor, int $group, string $thread, int $id, array $input): array
    {
        $persona = app(GroupChatThreads::class)->persona($input);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000']])->validate();
        $authorize = function () use ($actor, $group, $thread, $id, $persona) {
            $message = $this->access->messages($actor, $group, $thread, $persona)->whereKey($id)->firstOrFail();
            abort_unless($persona === 'staff' || ((int) $message->sender_id === (int) $actor->id && $message->sender_persona === $persona), 403);
        };
        $authorize();
        $operation = ['operation_id' => $data['operation_id'], 'revision' => (int) $data['revision'], 'persona' => $persona,
            'reason_hash' => app(GroupChatThreads::class)->fingerprint($data['reason'])];

        return app(ActivityOperations::class)->once($actor, 'chat.message.remove:'.$id, $operation, $authorize,
            function () use ($actor, $group, $thread, $id, $data, $authorize, $persona) {
                $message = GroupChatMessage::findOrFail($id);
                abort_unless($message->removed_at === null && $message->revision === (int) $data['revision'], 409);
                $message->forceFill(['body' => null, 'removed_at' => now()->utc(), 'removed_by' => $actor->id,
                    'removal_reason' => $data['reason'], 'revision' => $message->revision + 1])->save();
                $authorize();
                Audit::record('activity.chat_message_removed', $message, [], ['group_id' => $group, 'revision' => $message->revision]);

                return ['operation_type' => 'message_removed', 'persona' => $persona, 'thread_id' => $thread, 'message_id' => $id, 'activity_group_id' => $group, 'revision' => $message->revision, 'removed' => true];
            });
    }

    public function markRead(User $actor, int $group, string $thread, string $persona, int $last): array
    {
        $this->access->messages($actor, $group, $thread, $persona)->whereKey($last)->firstOrFail();

        return DB::transaction(function () use ($actor, $group, $thread, $persona, $last) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->access->messages($actor, $group, $thread, $persona)->whereKey($last)->firstOrFail();
            $row = GroupChatRead::firstOrNew(['thread_id' => $thread, 'user_id' => $actor->id, 'persona' => $persona]);
            $row->last_message_id = max($last, (int) ($row->last_message_id ?? 0));
            $row->save();

            return ['thread_id' => $thread, 'last_message_id' => (int) $row->last_message_id];
        });
    }
}
