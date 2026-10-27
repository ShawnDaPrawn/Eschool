<?php

declare(strict_types=1);

namespace App\Policies\Money;

use App\Models\User;

final class FeePlanPolicy
{
    public function authorize(User $actor, bool $write = false, bool $locked = false): User
    {
        return app(MoneyPolicy::class)->authorize($actor, 'fee-plans-manage', $write, $locked);
    }
}
