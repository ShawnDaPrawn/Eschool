<?php

declare(strict_types=1);

namespace App\Models\Classroom;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClassroomLease extends Model
{
    use BelongsToSchool, \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'foundation_classroom_leases';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return ['expires_at' => UtcInstant::class, 'revoked_at' => UtcInstant::class];
    }
}
