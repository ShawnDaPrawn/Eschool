<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CoverAssignment extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['source_snapshot', 'source_hash'];

    protected function casts(): array
    {
        return ['on_date' => 'date', 'revision' => 'integer', 'source_snapshot' => 'encrypted:array'];
    }
}
