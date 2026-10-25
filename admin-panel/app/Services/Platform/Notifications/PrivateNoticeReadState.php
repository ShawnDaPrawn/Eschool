<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Optional bridge to an existing domain inbox; source policy is never duplicated. */
interface PrivateNoticeReadState
{
    public function applyUnread(Builder $notifications, User $actor): void;

    /** @param array<int,array> $payloads notification id => opaque delivery payload
     * @return array<int,?string> authorized notification id => ISO read instant
     */
    public function readStates(User $actor, array $payloads): array;

    public function recordRead(User $actor, int $notificationId, array $payload): void;
}
