<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TutorPhoto extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['source_digest', 'sha256', 'crop', 'path'];

    protected function casts(): array
    {
        return ['crop' => 'encrypted:array', 'approved_at' => UtcInstant::class, 'revoked_at' => UtcInstant::class, 'purged_at' => UtcInstant::class];
    }
}
