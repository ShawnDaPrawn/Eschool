<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Communication\MessageRead;
use App\Models\Communication\MessageRecipient;
use App\Models\Platform\NotificationDelivery;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The sender's delivery report for one published school message (Action Plan
 * 2.4): how many recipients it was published to, how many read it, and per
 * person and channel whether the email and push messages were sent,
 * delivered, bounced or failed. Only the author and the people who may review
 * the message see it. Names only: never addresses, device tokens or provider
 * errors. engineering/specs/communication/delivery-report.md.
 */
class MessageReport
{
    public const PER_PAGE = 50;

    /** What a person's email or push came to, best first: one reached device is enough. */
    public const STATES = ['delivered', 'sent', 'queued', 'bounced', 'complained', 'failed', 'suppressed'];

    /**
     * @return array{id: int, title: string, published_at: ?string, recipient_count: int, read_count: int,
     *     not_notified: int, channels: array<string, array<string, int>>, page: int, last_page: int,
     *     items: list<array{name: string, read_at: ?string, notice: 'pending'|'queued'|'none', email: ?string, push: ?string}>}
     */
    public function for(User $actor, int $messageId, int $page = 1): array
    {
        $message = app(MessagePolicy::class)->staff($actor, $messageId);
        abort_if($message->published_at === null, 404);
        $schoolId = app(CurrentSchool::class)->id();
        $noticeId = $message->notification_id === null ? null : (int) $message->notification_id;

        $states = $this->states($schoolId, $noticeId, null);
        $channels = [];
        foreach (['mail' => 'email', 'push' => 'push'] as $channel => $label) {
            $channels[$label] = array_fill_keys(self::STATES, 0);
            foreach ($states[$channel] ?? [] as $state) {
                $channels[$label][$state]++;
            }
        }

        $rows = MessageRecipient::query()->where('message_id', $message->id)->orderBy('user_id')
            ->paginate(self::PER_PAGE, ['user_id', 'processed_at', 'notification_queued'], 'page', max(1, $page));
        $ids = $rows->getCollection()->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $names = User::query()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)]);
        $reads = MessageRead::query()->where('message_id', $message->id)->whereIn('user_id', $ids)->pluck('read_at', 'user_id');
        $pageStates = $this->states($schoolId, $noticeId, $ids);

        return [
            'id' => (int) $message->id,
            'title' => (string) $message->title,
            'published_at' => $message->published_at?->toIso8601String(),
            'recipient_count' => (int) $message->recipient_count,
            'read_count' => MessageRead::query()->where('message_id', $message->id)->count(),
            'not_notified' => MessageRecipient::query()->where('message_id', $message->id)->whereNotNull('processed_at')->where('notification_queued', false)->count(),
            'channels' => $channels,
            'page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'items' => $rows->getCollection()->map(fn (MessageRecipient $row) => [
                'name' => $names[(int) $row->user_id] ?? __('Former member'),
                'read_at' => isset($reads[$row->user_id]) ? Carbon::parse((string) $reads[$row->user_id])->toIso8601String() : null,
                // pending: not yet processed; none: the person's choices or access left no channel.
                'notice' => $row->processed_at === null ? 'pending' : ($row->notification_queued ? 'queued' : 'none'),
                'email' => $pageStates['mail'][(int) $row->user_id] ?? null,
                'push' => $pageStates['push'][(int) $row->user_id] ?? null,
            ])->values()->all(),
        ];
    }

    /**
     * Each recipient's state per channel: the latest attempt of each message,
     * then the best of a person's messages (push goes to every device).
     *
     * @param  list<int>|null  $userIds
     * @return array<string, array<int, string>>
     */
    private function states(int $schoolId, ?int $noticeId, ?array $userIds): array
    {
        if ($noticeId === null || $userIds === []) {
            return [];
        }
        $query = NotificationDelivery::query()->where('school_id', $schoolId)->where('notification_id', $noticeId)->whereNotNull('recipient_user_id');
        if ($userIds !== null) {
            $query->whereIn('recipient_user_id', $userIds);
        }
        /** @var Collection<int, NotificationDelivery> $rows */
        $rows = $query->orderBy('attempt')->get(['delivery_key', 'channel', 'recipient_user_id', 'status', 'attempt']);
        $latest = $rows->keyBy('delivery_key');
        $rank = array_flip(self::STATES);
        $states = [];
        foreach ($latest as $row) {
            $state = array_key_exists($row->status, $rank) ? $row->status : 'failed';
            $current = $states[$row->channel][(int) $row->recipient_user_id] ?? null;
            if ($current === null || $rank[$state] < $rank[$current]) {
                $states[$row->channel][(int) $row->recipient_user_id] = $state;
            }
        }

        return $states;
    }
}
