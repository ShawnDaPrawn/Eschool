<?php

declare(strict_types=1);

namespace App\Models\People\Pools;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One vetting decision about a pool member: immutable history. */
class PoolVetting extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'people_pool_vettings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['checks' => 'array', 'checked_on' => 'date', 'expires_on' => 'date', 'note' => 'encrypted'];
    }
}
