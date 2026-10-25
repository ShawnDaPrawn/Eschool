<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DigestPreference extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
}
