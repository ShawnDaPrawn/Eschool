<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermTemplate extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['base_year' => 'integer', 'terms' => 'array'];
    }
}
