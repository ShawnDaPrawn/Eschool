<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A learner's own weekly practice goal (days a week). Action Plan 3.13, engineering/specs/learning/practice-motivation.md. */
class PracticeGoal extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_goals';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'days_per_week' => 'integer', 'set_by' => 'integer', 'revision' => 'integer'];
    }
}
