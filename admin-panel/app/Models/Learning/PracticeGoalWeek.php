<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Celebration ledger: a weekly goal is celebrated once per learner per week. Action Plan 3.13, engineering/specs/learning/practice-motivation.md. */
class PracticeGoalWeek extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_goal_weeks';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'user_id' => 'integer', 'week_start' => 'immutable_date', 'days_target' => 'integer', 'notification_id' => 'integer'];
    }
}
