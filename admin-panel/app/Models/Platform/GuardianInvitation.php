<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GuardianInvitation extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'encrypted_token'];

    protected function casts(): array
    {
        return [
            'encrypted_token' => 'encrypted', 'expires_at' => 'datetime', 'used_at' => 'datetime',
            'reminded_day_three_at' => 'datetime', 'reminded_day_seven_at' => 'datetime',
        ];
    }
}
