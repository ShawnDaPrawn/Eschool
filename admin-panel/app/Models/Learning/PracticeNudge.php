<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Nudge ledger: at most one gentle practice nudge per person per week. Action Plan 3.13, engineering/specs/learning/practice-motivation.md. */
class PracticeNudge extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_nudges';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'user_id' => 'integer', 'week_start' => 'immutable_date', 'notification_id' => 'integer'];
    }
}
