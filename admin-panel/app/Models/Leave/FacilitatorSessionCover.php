<?php

declare(strict_types=1);

namespace App\Models\Leave;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FacilitatorSessionCover extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    /** Attendance increments the session revision without changing this dated cover commitment. */
    public function needsReview(?array $session): bool
    {
        return $session === null || ($session['status'] ?? null) === 'cancelled'
            || (int) ($session['activity_group_id'] ?? 0) !== (int) $this->activity_group_id
            || substr((string) ($session['on_date'] ?? ''), 0, 10) !== $this->on_date->toDateString()
            || substr((string) ($session['starts_at'] ?? ''), 0, 5) !== substr((string) $this->starts_at, 0, 5)
            || substr((string) ($session['ends_at'] ?? ''), 0, 5) !== substr((string) $this->ends_at, 0, 5);
    }

    protected function casts(): array
    {
        return ['on_date' => 'date', 'reason' => 'encrypted', 'session_revision' => 'integer', 'revision' => 'integer'];
    }
}
