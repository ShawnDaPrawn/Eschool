<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BellPeriod extends Model
{
    use BelongsToSchool;

    protected $table = 'bell_periods';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_break' => 'boolean'];
    }

    public function schedule()
    {
        return $this->belongsTo(BellSchedule::class, 'bell_schedule_id');
    }
}
