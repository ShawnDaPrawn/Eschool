<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TeachingDraft extends Model
{
    use BelongsToSchool;

    protected $guarded = ['school_id'];

    protected $hidden = ['request', 'source_manifest', 'context_text', 'request_hash'];

    protected $attributes = ['state' => 'pending', 'revision' => 0];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['request' => 'encrypted:array', 'source_manifest' => 'encrypted:array', 'context_text' => 'encrypted', 'revision' => 'integer', 'lease_until' => UtcInstant::class, 'purged_at' => UtcInstant::class];
    }
}
