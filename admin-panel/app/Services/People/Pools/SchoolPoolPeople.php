<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Models\People\Pools\PoolMember;
use Illuminate\Validation\ValidationException;

/** The school's own pool: consented, vetted on the session date and today, and offering this kind of session. */
final class SchoolPoolPeople implements BookablePeople
{
    public function __construct(private readonly PoolAccess $access) {}

    public function bookable(int $memberId, string $kind, string $date): PoolMember
    {
        $member = PoolMember::lockForUpdate()->findOrFail($memberId);
        if ($member->consent_status !== PoolMember::GIVEN) {
            throw ValidationException::withMessages(['pool_member_id' => __('pool_not_consented')]);
        }
        if (! $member->vettedOn($this->access->today()) || ! $member->vettedOn($date)) {
            throw ValidationException::withMessages(['pool_member_id' => __('pool_not_vetted')]);
        }
        if (! $member->offers($kind)) {
            throw ValidationException::withMessages(['kind' => __('pool_kind_not_offered')]);
        }

        return $member;
    }
}
