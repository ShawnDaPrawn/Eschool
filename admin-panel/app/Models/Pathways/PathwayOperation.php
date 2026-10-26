<?php

declare(strict_types=1);

namespace App\Models\Pathways;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PathwayOperation extends Model
{
    use BelongsToSchool, HasUuids;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['receipt' => 'array'];
    }
}
