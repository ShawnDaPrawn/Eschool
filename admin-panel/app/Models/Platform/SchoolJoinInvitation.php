<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An invitation for an existing account to join this school with a role.
 * Found by its secret before anyone knows the school (acrossSchools()), always
 * created inside the inviting school. Its life is audited explicitly
 * (SchoolJoinInvitations), never with the typed name, address or secret.
 *
 * @property int $id
 * @property int $school_id
 * @property int $user_id
 * @property int|null $role_id
 * @property string $purpose
 * @property string $encrypted_name
 * @property string $encrypted_email
 * @property string|null $qualification
 * @property string $token_hash
 * @property string $encrypted_token
 * @property string $email_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $withdrawn_at
 * @property Carbon|null $withheld_at
 * @property Carbon|null $created_at
 */
class SchoolJoinInvitation extends Model
{
    use BelongsToSchool;

    public const STAFF = 'staff';

    public const SCHOOL_ADMIN = 'school_admin';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'encrypted_token', 'email_hash', 'encrypted_name', 'encrypted_email', 'withheld_at'];

    protected function casts(): array
    {
        return [
            'encrypted_token' => 'encrypted', 'encrypted_name' => 'encrypted', 'encrypted_email' => 'encrypted',
            'expires_at' => 'datetime', 'used_at' => 'datetime', 'accepted_at' => 'datetime', 'declined_at' => 'datetime',
            'withdrawn_at' => 'datetime', 'withheld_at' => 'datetime',
        ];
    }

    /** Not yet answered, withdrawn, replaced or expired. Withheld ones wait too, as far as the school can tell. */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
