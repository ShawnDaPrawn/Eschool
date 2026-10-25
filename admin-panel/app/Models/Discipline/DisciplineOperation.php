<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DisciplineOperation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
