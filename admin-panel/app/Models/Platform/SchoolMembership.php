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
 */
class SchoolMembership extends Model
{
    use Auditable;

    public const ACTIVE = 'active';

    public const SUSPENDED = 'suspended';

    public const ENDED = 'ended';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
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
}
