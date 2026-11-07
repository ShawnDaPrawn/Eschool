<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's own share of their strengths mirror with one named guardian or
 * teacher (Action Plan 4.8). The snapshot is an encrypted read-only copy; read
 * it only through App\Services\Learning\Strengths\StrengthsShares, which
 * checks that the reader is the learner or the named viewer of a share that
 * is still open. Nothing else may read it (StrengthsMirrorGuardTest).
 */
class StrengthsMirrorShare extends Model
{
    use BelongsToSchool;

    protected $table = 'strengths_mirror_shares';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['snapshot', 'request_hash', 'operation_id'];

    protected function casts(): array
    {
        return ['sections' => 'array', 'snapshot' => 'encrypted:array', 'snapshot_on' => 'immutable_date', 'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime', 'last_viewed_at' => 'immutable_datetime', 'learner_id' => 'integer', 'owner_user_id' => 'integer',
            'viewer_user_id' => 'integer'];
    }

    public function open(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
