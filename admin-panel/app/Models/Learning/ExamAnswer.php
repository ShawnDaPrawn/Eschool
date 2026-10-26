<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['path', 'sha256', 'crop', 'working', 'response', 'upload_hash', 'request_hash'];

    protected function casts(): array
    {
        return ['working' => 'encrypted', 'response' => 'encrypted:array', 'crop' => 'encrypted:array', 'share_with_teacher' => 'boolean',
            'lease_until' => UtcInstant::class, 'expires_at' => UtcInstant::class, 'revoked_at' => UtcInstant::class, 'purged_at' => UtcInstant::class];
    }
}
