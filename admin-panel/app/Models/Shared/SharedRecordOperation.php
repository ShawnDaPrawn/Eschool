<?php

declare(strict_types=1);

namespace App\Models\Shared;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One school's receipt for one shared-record write (operation UUID replay). */
class SharedRecordOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'shared_record_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
