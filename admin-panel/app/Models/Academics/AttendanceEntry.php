<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\Students;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceEntry extends Model
{
    use BelongsToSchool;

    protected $table = 'attendance_entries';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'amended_at' => 'datetime', 'reason' => 'encrypted'];
    }

    public function register()
    {
        return $this->belongsTo(AttendanceRegister::class, 'attendance_register_id');
    }

    public function learner()
    {
        return $this->belongsTo(Students::class, 'learner_id');
    }
}
