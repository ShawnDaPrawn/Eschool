<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;

class NotificationVisibility
{
    public static function forUser(Builder $query, int $userId, int $portalAudience): Builder
    {
        $links = UserNotification::query()->where('user_id', $userId)->select('notification_id');

        return $query->where(function ($visible) use ($links, $portalAudience) {
            $visible->whereIn('id', $links)->orWhere(function ($legacy) use ($portalAudience) {
                // Existing history remains readable. Old private academic
                // rows are never promoted to a whole-portal broadcast.
                $legacy->where('preferences_enforced', false)->where(function ($audience) use ($portalAudience) {
                    $audience->where('send_to', 1)->orWhere(fn ($group) => $group->where('send_to', $portalAudience)->where('is_custom', 1));
                });
            });
        });
    }
}
