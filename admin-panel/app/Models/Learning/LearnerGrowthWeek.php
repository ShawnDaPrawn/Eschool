<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One learner's own weekly "you grew in" receipt behind the growth.weekly notice. */
class LearnerGrowthWeek extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['topics' => 'encrypted:array', 'week_start' => 'immutable_date', 'expires_at' => 'immutable_datetime'];
    }
}
