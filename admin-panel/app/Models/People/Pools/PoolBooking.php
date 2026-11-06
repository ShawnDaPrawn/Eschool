<?php

declare(strict_types=1);

namespace App\Models\People\Pools;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A pool member booked into a group activity session as a speaker or job-shadow host. */
class PoolBooking extends Model
{
    use BelongsToSchool;

    public const BOOKED = 'booked';

    public const CANCELLED = 'cancelled';

    public const KINDS = ['career_talk', 'job_shadow'];

    protected $table = 'people_pool_bookings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime', 'revision' => 'integer'];
    }
}
