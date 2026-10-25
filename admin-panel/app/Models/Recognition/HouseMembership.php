<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class HouseMembership extends Model
{
    use BelongsToSchool;

    protected $table = 'house_memberships';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'revision' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'reason' => 'encrypted'];
    }

    protected $hidden = ['reason'];
}
