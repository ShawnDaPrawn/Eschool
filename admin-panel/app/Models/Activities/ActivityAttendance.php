<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivityAttendance extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_attendance';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted', 'revision' => 'integer'];
    }
}
