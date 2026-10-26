<?php

declare(strict_types=1);

namespace App\Models\PeerTutoring;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeerTutoringOperation extends Model
{
    use BelongsToSchool, HasUuids;

    protected $guarded = ['id', 'school_id'];
}
