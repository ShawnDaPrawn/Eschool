<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SupportPlan extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['contents'];

    protected function casts(): array
    {
        return ['started_on' => 'date', 'review_due_on' => 'date', 'revision' => 'integer', 'accommodation_revision' => 'integer', 'contents' => 'encrypted:array'];
    }
}
