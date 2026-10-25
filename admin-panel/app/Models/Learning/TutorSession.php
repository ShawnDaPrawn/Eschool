<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TutorSession extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $attributes = ['state' => 'open'];

    protected $hidden = ['source_version_ids', 'request_hash'];

    protected function casts(): array
    {
        return ['source_version_ids' => 'encrypted:array'];
    }
}
