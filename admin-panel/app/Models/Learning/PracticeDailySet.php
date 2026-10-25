<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PracticeDailySet extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_daily_sets';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['practice_on' => 'immutable_date', 'completed_on' => 'immutable_date', 'offline_until' => UtcInstant::class, 'completed_at' => UtcInstant::class];
    }
}
