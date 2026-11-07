<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One row per school: gentle practice nudges, off by default. Action Plan 3.13, engineering/specs/learning/practice-motivation.md. */
class PracticeMotivationSettings extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_motivation_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['guardian_nudge_enabled' => 'boolean', 'learner_nudge_enabled' => 'boolean', 'nudge_after_school_days' => 'integer', 'changed_by' => 'integer', 'revision' => 'integer'];
    }
}
