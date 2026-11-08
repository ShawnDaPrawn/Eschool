<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Models\People\Pools\PoolMember;

/**
 * Where bookings find the vetted people a school may book (Action Plan 5.6).
 *
 * The seam for sharing vetted people between schools later: today the only
 * source is the school's own pool (SchoolPoolPeople). A cross-school source
 * built on the shared-records pattern would give each school its own pool
 * row for a shared person, with that school's own consent and vetting, and
 * resolve it here; bookings, attendance and the pool member profile would
 * not change. Implementations run inside the booking transaction and lock
 * the row they return.
 */
interface BookablePeople
{
    /** The current school's pool member, locked, if they may be booked for $kind on $date; refuses otherwise. */
    public function bookable(int $memberId, string $kind, string $date): PoolMember;
}
