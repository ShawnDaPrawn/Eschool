<?php

declare(strict_types=1);

namespace App\Models\Pathways;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdmissionRequirement extends Model
{
    use BelongsToSchool, HasUuids;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'current_version_id' => 'integer', 'expires_on' => 'immutable_date'];
    }
}
