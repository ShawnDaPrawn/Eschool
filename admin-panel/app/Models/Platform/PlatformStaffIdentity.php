<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Global exclusion identity, never a school permission or a case-access grant. */
class PlatformStaffIdentity extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
