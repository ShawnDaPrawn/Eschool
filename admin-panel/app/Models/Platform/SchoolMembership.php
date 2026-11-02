<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Models\User;
use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A person's role at a school, with dates (the Build Bible, section 3.1,
 * Identity). A user reaches a school's data only through an active
 * membership there; see App\Services\Platform\Identity\MembershipService.
 *
 * Read across schools for one user (it is the link itself), so it has no
 * school scope.
 *
 * @property int $id
 * @property int $user_id
 * @property int $school_id
 * @property string $role
 * @property string $status
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon|null $leaver_until
 */
class SchoolMembership extends Model
{
    use Auditable;

    public const ACTIVE = 'active';

    /**
     * Left at the year switch (Action Plan 1.21): read-only access to the
     * school's records about them until the end of leaver_until; never a
     * current member (scopeActive leaves leavers out). See
     * App\Services\People\Leavers\LeaverState.
     */
    public const LEAVER = 'leaver';

    public const SUSPENDED = 'suspended';

    public const ENDED = 'ended';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'leaver_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Active on the selected school calendar; cross-school sign-in uses MembershipService. */
    public function scopeActive(Builder $query): Builder
    {
        $timezone = app(CurrentSchool::class)->school()?->timezone ?? config('app.timezone', 'UTC');
        $today = now($timezone)->toDateString();

        return $query->where('school_memberships.status', self::ACTIVE)
            ->where(fn (Builder $q) => $q->whereNull('school_memberships.starts_on')->orWhere('school_memberships.starts_on', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('school_memberships.ends_on')->orWhere('school_memberships.ends_on', '>=', $today))
            ->whereHas('school', fn (Builder $school) => $school->where('status', School::ACTIVE));
    }

    /**
     * Who may reach the school's records today: active members, and leavers
     * until the end of their leaver_until. Use it for access checks (which
     * roles may read); use active() for who is a current member.
     */
    public function scopeReachable(Builder $query): Builder
    {
        $timezone = app(CurrentSchool::class)->school()?->timezone ?? config('app.timezone', 'UTC');
        $today = now($timezone)->toDateString();

        return $query->where(fn (Builder $either) => $either
            ->where(fn (Builder $active) => $active->active())
            ->orWhere(fn (Builder $leaver) => $leaver->where('school_memberships.status', self::LEAVER)
                ->whereDate('school_memberships.leaver_until', '>=', $today)
                ->whereHas('school', fn (Builder $school) => $school->where('status', School::ACTIVE))));
    }
}
