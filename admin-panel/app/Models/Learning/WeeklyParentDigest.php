<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class WeeklyParentDigest extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['title' => 'encrypted', 'summary' => 'encrypted', 'snapshot' => 'encrypted:array', 'home_action' => 'encrypted:array', 'week_start' => 'immutable_date', 'week_end' => 'immutable_date', 'expires_at' => 'immutable_datetime', 'opened_at' => 'immutable_datetime'];
    }
}
