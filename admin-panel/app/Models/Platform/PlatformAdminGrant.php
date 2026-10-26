<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Explicit, expiring permission to use the platform admin area (/saas).
 * Needs an active PlatformStaffIdentity beside it; it grants no school role,
 * permission or record.
 */
class PlatformAdminGrant extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'expires_at' => 'immutable_datetime'];
    }
}
