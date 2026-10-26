<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TaskInsight extends Model
{
    use BelongsToSchool;

    protected $guarded = ['school_id'];

    protected $hidden = ['summary'];

    protected function casts(): array
    {
        return ['summary' => 'encrypted:array'];
    }
}
