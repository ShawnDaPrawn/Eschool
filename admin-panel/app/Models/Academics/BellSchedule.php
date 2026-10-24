<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\SessionYear;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BellSchedule extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'bell_schedules';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }

    public function periods()
    {
        return $this->hasMany(BellPeriod::class)->orderBy('position');
    }

    public function year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}
