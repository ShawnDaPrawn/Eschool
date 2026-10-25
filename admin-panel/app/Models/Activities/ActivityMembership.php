<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivityMembership extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_group_memberships';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date', 'reason' => 'encrypted', 'revision' => 'integer'];
    }
}
