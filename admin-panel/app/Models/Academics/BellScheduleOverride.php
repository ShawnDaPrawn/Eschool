<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One date on which a bell schedule is replaced (a short day). */
class BellScheduleOverride extends Model
{
    use BelongsToSchool;

    protected $table = 'bell_schedule_overrides';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['on_date' => 'date', 'revision' => 'integer'];
    }

    public function schedule()
    {
        return $this->belongsTo(BellSchedule::class, 'bell_schedule_id')->withTrashed();
    }

    public function replacement()
    {
        return $this->belongsTo(BellSchedule::class, 'replacement_bell_schedule_id')->withTrashed();
    }
}
