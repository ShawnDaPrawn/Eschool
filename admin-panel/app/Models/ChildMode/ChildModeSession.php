<?php

declare(strict_types=1);

namespace App\Models\ChildMode;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One child session (Action Plan 4.2): who started it, for which child, from
 * which parent-app sign-in, and when it started, expires and ended. Ids and
 * times only, never anything the child opened or typed.
 *
 * @property int $id
 * @property int $school_id
 * @property int $learner_id
 * @property int $guardian_user_id
 * @property int|null $token_id
 * @property int|null $issued_from_token_id
 * @property int $pin_version
 * @property Carbon $started_at
 * @property Carbon $expires_at
 * @property Carbon|null $ended_at
 * @property string|null $end_reason
 * @property int|null $ended_by
 */
class ChildModeSession extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'learner_id' => 'integer', 'guardian_user_id' => 'integer', 'token_id' => 'integer', 'issued_from_token_id' => 'integer',
            'pin_version' => 'integer', 'started_at' => 'datetime', 'expires_at' => 'datetime', 'ended_at' => 'datetime', 'ended_by' => 'integer',
        ];
    }
}
