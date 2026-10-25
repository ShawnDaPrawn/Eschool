<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\User;

/** Optional transport guard on a registered private query-scope provider. */
interface PrivateNoticeDelivery
{
    public function allows(User $actor, string $type, array $payload): bool;
}
