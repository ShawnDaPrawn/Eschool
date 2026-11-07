<?php

declare(strict_types=1);

namespace App\Models\Reading;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** How long a learner read on one school-local day. School-owned. */
class ReadingDay extends Model
{
    use BelongsToSchool;

    protected $table = 'reading_days';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'read_on' => 'immutable_date', 'seconds' => 'integer'];
    }
}
