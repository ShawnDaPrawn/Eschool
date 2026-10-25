<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CoverOperation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['request_hash'];
}
