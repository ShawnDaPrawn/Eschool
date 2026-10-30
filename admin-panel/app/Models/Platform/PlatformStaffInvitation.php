<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Casts\UtcInstant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-use, expiring link for an invited platform staff member to choose
 * their password (Action Plan SA.1). Only the SHA-256 of the token is kept.
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 */
class PlatformStaffInvitation extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => UtcInstant::class, 'used_at' => 'datetime', 'accepted_at' => 'datetime', 'created_by' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
