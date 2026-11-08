<?php

declare(strict_types=1);

namespace App\Models\People\Pools;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** An idempotent pool write: its request hash and a receipt of record ids only. */
class PoolOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'people_pool_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
