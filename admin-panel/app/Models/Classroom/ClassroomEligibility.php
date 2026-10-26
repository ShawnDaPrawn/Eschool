<?php

declare(strict_types=1);

namespace App\Models\Classroom;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClassroomEligibility extends Model
{
    use BelongsToSchool;

    protected $table = 'foundation_classroom_groups';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return ['eligible' => 'boolean', 'revision' => 'integer', 'review_reason' => 'encrypted'];
    }
}
