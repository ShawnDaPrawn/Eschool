<?php

declare(strict_types=1);

namespace App\Models\Ai;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    use BelongsToSchool;

    protected $table = 'ai_usage';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['request_hash', 'inference_output', 'inference_sources'];

    protected function casts(): array
    {
        return ['reserved_units' => 'integer', 'used_units' => 'integer', 'cost_cents' => 'integer', 'reserved_cost_cents' => 'integer', 'inference_output' => 'encrypted:array', 'inference_sources' => 'encrypted:array', 'completed_at' => 'datetime', 'quarantine_purged_at' => 'immutable_datetime'];
    }
}
