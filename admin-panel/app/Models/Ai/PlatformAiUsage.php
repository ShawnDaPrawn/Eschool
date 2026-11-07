<?php

declare(strict_types=1);

namespace App\Models\Ai;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * HQ's own AI metering (Action Plan 3.4): one row per model call made from
 * /saas with no school, such as AI drafts for the platform library. Tokens,
 * cents, hashes and flag codes only: never a prompt or an answer. A school
 * never writes here; its calls are metered in its own ai_usage.
 */
class PlatformAiUsage extends Model
{
    protected $table = 'platform_ai_usage';

    protected $guarded = ['id'];

    protected $hidden = ['request_hash'];

    protected static function booted(): void
    {
        $refuse = function (): void {
            abort_if(app(CurrentSchool::class)->isSet(), 403, 'HQ AI metering is written only from HQ, never from a school.');
        };
        static::saving($refuse);
        static::deleting(fn () => abort(409, 'AI metering is kept.'));
    }

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'reserved_units' => 'integer', 'used_units' => 'integer', 'reserved_cost_cents' => 'integer', 'cost_cents' => 'integer',
            'flags' => 'array', 'completed_at' => 'datetime'];
    }
}
