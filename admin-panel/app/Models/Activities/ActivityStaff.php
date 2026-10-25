<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivityStaff extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_group_staff';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date', 'cascade' => 'boolean', 'reason' => 'encrypted', 'revision' => 'integer'];
    }
}
