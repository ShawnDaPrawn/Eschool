<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One push-notification registration (an FCM token) for one device of one
 * human. Global, like users: the school comes from each notification.
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property string $token
 * @property string $platform
 * @property string|null $app
 * @property int|null $personal_access_token_id
 */
class UserDevice extends Model
{
    protected $table = 'user_devices';

    protected $fillable = ['user_id', 'token_hash', 'token', 'platform', 'app', 'personal_access_token_id', 'last_seen_at', 'revoked_at'];

    protected $hidden = ['token', 'token_hash'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
