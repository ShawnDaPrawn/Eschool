<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A named adult asked to reach out to an unseen learner (Action Plan 4.5). */
class UnseenAssignment extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['assigned_on' => 'date', 'cleared_at' => 'datetime'];
    }
}
