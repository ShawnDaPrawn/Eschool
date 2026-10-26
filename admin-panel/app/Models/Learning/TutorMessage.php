<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TutorMessage extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $attributes = ['state' => 'pending'];

    protected $hidden = ['learner_text', 'working', 'media_ids', 'source_manifest', 'context_text', 'response', 'request_hash'];

    protected function casts(): array
    {
        return ['learner_text' => 'encrypted', 'working' => 'encrypted', 'media_ids' => 'encrypted:array', 'source_manifest' => 'encrypted:array',
            'context_text' => 'encrypted', 'response' => 'encrypted:array', 'purged_at' => UtcInstant::class, 'lease_until' => UtcInstant::class];
    }
}
