<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\User;
use App\Services\Platform\Notifications\PrivateNoticeReadState;
use Illuminate\Database\Eloquent\Builder;

/** Staff only: the original permitted intent/read marker remains authoritative. */
class WellbeingNoticeReadState implements PrivateNoticeReadState
{
    public function __construct(private readonly WellbeingNotices $notices) {}

    public function applyUnread(Builder $notifications, User $actor): void
    {
        $notifications->where('type', WellbeingNotices::STAFF)
            ->whereIn('id', $this->notices->staffReadIntents($actor)->whereNull('read_at')->select('notification_id'));
    }

    public function readStates(User $actor, array $payloads): array
    {
        $rows = $this->notices->staffReadIntents($actor)->whereIn('notification_id', array_keys($payloads))->get(['id', 'notification_id', 'read_at']);
        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row->notification_id;
            if (($payloads[$id]['wellbeing_intent_id'] ?? null) === $row->id) {
                $result[$id] = $row->read_at?->toISOString();
            }
        }

        return $result;
    }

    public function recordRead(User $actor, int $notificationId, array $payload): void
    {
        $this->notices->staffReadIntents($actor)->where('notification_id', $notificationId)->whereKey($payload['wellbeing_intent_id'] ?? '')->firstOrFail();
        $this->notices->markRead($actor, $notificationId);
    }
}
