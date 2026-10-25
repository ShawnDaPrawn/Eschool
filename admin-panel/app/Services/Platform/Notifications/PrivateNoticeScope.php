<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Add fresh source/owner predicates without retrieving a private notice/body. */
interface PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void;
}
