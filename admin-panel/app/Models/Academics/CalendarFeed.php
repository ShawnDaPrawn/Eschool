<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A learner's calendar feed link (Action Plan 1.10). The token itself is
 * shown once and never stored; token_hash is its SHA-256.
 *
 * @property int $id
 * @property int $school_id
 * @property int $user_id
 * @property int $learner_id
 * @property string $token_hash
 * @property bool $include_lessons
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 */
class CalendarFeed extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'learner_id' => 'integer', 'include_lessons' => 'boolean', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
