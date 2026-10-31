<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One emailed link for a staff member to choose their password. */
class StaffInvitation extends Model
{
    use Auditable, BelongsToSchool;

    public const SCHOOL_ADMIN = 'school_admin';

    public const STAFF = 'staff';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'encrypted_token', 'email_hash'];

    protected function casts(): array
    {
        return ['encrypted_token' => 'encrypted', 'expires_at' => 'datetime', 'used_at' => 'datetime', 'accepted_at' => 'datetime'];
    }
}
