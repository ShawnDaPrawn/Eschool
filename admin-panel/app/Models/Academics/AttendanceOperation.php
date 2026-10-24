<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'attendance_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
