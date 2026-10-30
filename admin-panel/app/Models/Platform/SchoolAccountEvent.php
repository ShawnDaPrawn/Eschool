<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** HQ record (engineering/specs/platform/hq-school-accounts.md); written only from /saas. */
class SchoolAccountEvent extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'school_account_events';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
