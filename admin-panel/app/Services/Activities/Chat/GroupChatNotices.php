<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Activities\Chat\GroupChatThread;
use App\Models\Notification;
use App\Models\Platform\OutboxEvent;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Generic notices contain no title, text, sender name or participant counts. */
class GroupChatNotices implements OutboxListener, PrivateNoticeDelivery, PrivateNoticeScope
{
    public const STAFF = 'group_chat.staff_update';

    public const FAMILY = 'group_chat.family_update';

    public const STAFF_LINK = '/group-conversations';

    public const FAMILY_LINK = '/activities/groups';

    private function key(int $message, int $actor, string $persona): string
    {
        return 'group-chat:'.$message.':'.$actor.':'.$persona;
    }

    private function enabled(): bool
    {
        return app(ModuleGate::class)->allows('groups') && app(ModuleGate::class)->allows('chat');
    }

    public function handle(OutboxEvent $event): void
    {
        abort_unless((int) $event->school_id === app(CurrentSchool::class)->id(), 403);
        if (! $this->enabled()) {
            return;
        }
        $message = GroupChatMessage::select(['id', 'thread_id', 'sender_id', 'sender_persona', 'revision', 'removed_at', 'sent_at', 'private_user_id'])->find($event->payload['message_id'] ?? 0);
        if ($message === null || $message->removed_at !== null || $message->sent_at->lt(now()->utc()->subDays(app(GroupChatRules::class)->retentionDays()))) {
            return;
        }
        $thread = GroupChatThread::select(['id', 'activity_group_id', 'audience', 'private', 'owner_id', 'owner_persona'])->find($message->thread_id);
        if ((int) $message->revision !== ($event->payload['revision'] ?? null) || $thread === null || $thread->id !== ($event->payload['thread_id'] ?? null) || (int) $thread->activity_group_id !== ($event->payload['group_id'] ?? null)) {
            return;
        }
        $today = app(CommunityAuthority::class)->today();
        $group = ActivityGroup::find($thread->activity_group_id);
        if ($group === null) {
            return;
        }
        $lineage = app(CommunityAuthority::class)->lineage($group);
        $staff = ActivityStaff::where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->where(fn ($q) => $q->where('activity_group_id', $group->id)->orWhere(fn ($q) => $q->whereIn('activity_group_id', $lineage)->where('role', 'admin')->where('cascade', true)))
            ->pluck('user_id')->map(fn ($id) => [(int) $id, 'staff'])->all();
        $family = [];
        if ($thread->audience !== 'staff') {
            $persona = $thread->audience === 'guardians' ? 'parent' : 'student';
            if ($message->private_user_id !== null) {
                $family[] = [(int) $message->private_user_id, $persona];
            } elseif ($thread->private) {
                if ($thread->owner_persona === $persona) {
                    $family[] = [(int) $thread->owner_id, $persona];
                }
            } else {
                $members = ActivityMembership::where('activity_group_id', $group->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
                foreach (Students::whereIn('id', $members)->select(['id', 'school_id', 'user_id', 'father_id', 'mother_id', 'guardian_id'])->cursor() as $learner) {
                    $ids = $persona === 'student' ? [(int) $learner->user_id] : app(GuardianService::class)->guardianUserIds($learner)->all();
                    foreach ($ids as $id) {
                        $family[] = [(int) $id, $persona];
                    }
                }
            }
        }
        $seen = [];
        foreach ([...$staff, ...$family] as [$id, $persona]) {
            if ($id === (int) $message->sender_id || isset($seen[$id.':'.$persona])) {
                continue;
            }
            $seen[$id.':'.$persona] = true;
            $actor = User::find($id);
            if ($actor === null || ! $this->source($actor, (int) $thread->activity_group_id, $thread->id, (int) $message->id, $persona, (int) $message->revision)) {
                continue;
            }
            $type = $persona === 'staff' ? self::STAFF : self::FAMILY;
            $link = $persona === 'staff' ? self::STAFF_LINK : self::FAMILY_LINK;
            $source = $this->key((int) $message->id, (int) $actor->id, $persona);
            $notice = Notification::withTrashed()->where('source_key', $source)->first();
            if ($notice !== null) {
                continue;
            }
            if ($notice === null) {
                $notice = app(Notifier::class)->makeNotice($type, __('Group conversation updated'), __('Open your activity groups to read an available message.'), $link);
                $notice->source_key = $source;
                $notice->delivery_payload = ['link' => $link, 'group_chat_notice_id' => (int) $notice->id, 'group_chat_group_id' => (int) $thread->activity_group_id,
                    'group_chat_thread_id' => $thread->id, 'group_chat_message_id' => (int) $message->id, 'group_chat_persona' => $persona, 'group_chat_revision' => (int) $message->revision];
                $notice->save();
            }
            if ($this->allows($actor, $type, $notice->delivery_payload)) {
                app(Notifier::class)->assign($notice, [(int) $actor->id]);
            }
        }
    }

    private function source(User $actor, int $group, string $thread, int $message, string $persona, int $revision): bool
    {
        try {
            return app(GroupChatAccess::class)->messages($actor, $group, $thread, $persona)->whereKey($message)->where('revision', $revision)->whereNull('removed_at')->exists();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $persona = $payload['group_chat_persona'] ?? null;
        if (! in_array($persona, ['staff', 'parent', 'student'], true)
            || $type !== ($persona === 'staff' ? self::STAFF : self::FAMILY)
            || ($payload['link'] ?? null) !== ($persona === 'staff' ? self::STAFF_LINK : self::FAMILY_LINK)
            || ! is_int($payload['group_chat_notice_id'] ?? null) || $payload['group_chat_notice_id'] < 1
            || ! is_int($payload['group_chat_group_id'] ?? null) || $payload['group_chat_group_id'] < 1
            || ! is_string($payload['group_chat_thread_id'] ?? null) || ! Str::isUuid($payload['group_chat_thread_id'])
            || ! is_int($payload['group_chat_message_id'] ?? null) || $payload['group_chat_message_id'] < 1
            || ! is_int($payload['group_chat_revision'] ?? null) || $payload['group_chat_revision'] < 1) {
            return false;
        }
        if (! $this->source($actor, $payload['group_chat_group_id'], $payload['group_chat_thread_id'], $payload['group_chat_message_id'], $persona, $payload['group_chat_revision'])) {
            return false;
        }

        return Notification::whereKey($payload['group_chat_notice_id'])->where('type', $type)
            ->where('source_key', $this->key($payload['group_chat_message_id'], (int) $actor->id, $persona))->exists();
    }

    public function apply(Builder $notifications, User $actor): void
    {
        if (! $this->enabled()) {
            $notifications->whereRaw('1 = 0');

            return;
        }
        $access = app(GroupChatAccess::class);
        $policy = app(GroupChatRules::class)->current();
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $notifications->where(function ($scopes) use ($notifications, $actor, $access, $policy, $safe) {
            $scopes->whereRaw('1 = 0');
            foreach (['staff', 'parent', 'student'] as $persona) {
                try {
                    $groups = $access->groupIds($actor, $persona);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    continue;
                }
                if ($groups === []) {
                    continue;
                }
                $threads = GroupChatThread::whereColumn('activity_chat_threads.id', 'activity_chat_messages.thread_id')->whereIn('activity_group_id', $groups)
                    ->whereNull('redacted_at')->where('last_message_at', '>=', now()->utc()->subDays(app(GroupChatRules::class)->retentionDays()))
                    ->whereIn('audience', $access->audiences($persona, $policy))
                    ->whereRaw("CAST(activity_group_id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.group_chat_group_id'))")
                    ->whereRaw("activity_chat_threads.id = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.group_chat_thread_id'))")->selectRaw('1');
                if ($persona !== 'staff') {
                    $threads->where(fn ($q) => $q->where('private', false)->orWhere(fn ($q) => $q->where('owner_id', $actor->id)->where('owner_persona', $persona)));
                }
                $messages = GroupChatMessage::whereNull('removed_at')->where('sent_at', '>=', now()->utc()->subDays(app(GroupChatRules::class)->retentionDays()))
                    ->whereRaw("CAST(activity_chat_messages.id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.group_chat_message_id'))")
                    ->whereRaw("CAST(activity_chat_messages.revision AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.group_chat_revision'))")
                    ->whereExists($threads->toBase())->selectRaw('1');
                if ($persona !== 'staff') {
                    $shared = $policy[$persona === 'parent' ? 'guardian_shared_replies' : 'learner_shared_replies'];
                    $messages->where(fn ($q) => $q->where(fn ($q) => $q->where('private_user_id', $actor->id)->whereIn('sender_persona', [$persona, 'staff']))
                        ->orWhere(fn ($q) => $q->where('visibility', 'thread')->where(fn ($q) => $q->where('sender_persona', 'staff')
                            ->when($shared, fn ($q) => $q->orWhere('sender_persona', $persona))
                            ->orWhere(fn ($q) => $q->where('sender_id', $actor->id)->where('sender_persona', $persona)))));
                }
                $type = $persona === 'staff' ? self::STAFF : self::FAMILY;
                $link = $persona === 'staff' ? self::STAFF_LINK : self::FAMILY_LINK;
                $scopes->orWhere(fn ($q) => $q->where($notifications->qualifyColumn('type'), $type)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.group_chat_persona')) = ?", [$persona])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = ?", [$link])
                    ->whereRaw($notifications->qualifyColumn('id')." = JSON_EXTRACT($safe, '$.group_chat_notice_id')")
                    ->whereRaw($notifications->qualifyColumn('source_key')." = CONCAT('group-chat:', JSON_UNQUOTE(JSON_EXTRACT($safe, '$.group_chat_message_id')), ':', ?, ':', ?)", [(int) $actor->id, $persona])
                    ->whereExists($messages->toBase()));
            }
        });
    }
}
