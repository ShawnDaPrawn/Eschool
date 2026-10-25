<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SupportOperation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return [];
    }
}
