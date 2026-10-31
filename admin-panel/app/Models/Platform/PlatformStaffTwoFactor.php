<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A platform staff member's authenticator app (SA.2). Not Auditable on
 * purpose: every change is a named platform_two_factor_* audit row written by
 * PlatformTwoFactor, so the secret and the recovery code hashes never reach
 * audit_log. Both are hidden from arrays and JSON.
 *
 * @property string $secret
 * @property Carbon|null $confirmed_at
 * @property int|null $last_used_step
 * @property list<string>|null $recovery_codes
 */
class PlatformStaffTwoFactor extends Model
{
    protected $table = 'platform_staff_two_factor';

    protected $guarded = ['id'];

    protected $hidden = ['secret', 'recovery_codes'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'confirmed_at' => 'datetime',
            'last_used_step' => 'integer',
            'recovery_codes' => 'array',
        ];
    }
}
