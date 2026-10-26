<?php

declare(strict_types=1);

namespace App\Models\Ai;

use App\Casts\UtcInstant;
use Illuminate\Database\Eloquent\Model;

/** Explicit, expiring global cost-only grant; never a learner-content permission. */
class PlatformAiCostGrant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'expires_at' => UtcInstant::class];
    }
}
