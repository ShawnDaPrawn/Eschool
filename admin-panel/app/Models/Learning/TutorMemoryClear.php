<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One "clear my tutor memory" request, kept by its operation UUID so a retry replays it. */
class TutorMemoryClear extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'cleared_by' => 'integer', 'cleared_at' => 'datetime'];
    }
}
