<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceRegister extends Model
{
    use BelongsToSchool;

    protected $table = 'attendance_registers';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'on_date' => 'date'];
    }

    public function entries()
    {
        return $this->hasMany(AttendanceEntry::class);
    }
}
