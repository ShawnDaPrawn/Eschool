<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A way to set a learner's password: a link emailed to a guardian, or a set-up
 * code the school prints. See App\Services\Platform\Identity\LearnerInvitations.
 *
 * @property int $id
 * @property int|null $school_id
 * @property int $learner_user_id
 * @property int|null $guardian_user_id
 * @property string $channel
 * @property string|null $token_hash
 * @property string|null $code_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $revoked_at
 * @property int|null $created_by
 */
class LearnerInvitation extends Model
{
    public const LINK = 'link';

    public const CODE = 'code';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_user_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    /** Not used, not revoked and not expired. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('used_at')->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public function isOpen(): bool
    {
        return $this->used_at === null && $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
