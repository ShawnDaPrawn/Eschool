<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The structured mark points of one past paper version (Action Plan 3.15). */
class PastPaperMemo extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['state' => 'draft', 'revision' => 1];

    protected $hidden = ['mark_points'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'mark_points' => 'encrypted:array', 'reviewed_at' => UtcInstant::class];
    }
}
