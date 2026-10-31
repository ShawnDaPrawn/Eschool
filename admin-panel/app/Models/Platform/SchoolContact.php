<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** HQ record (engineering/specs/platform/hq-school-accounts.md); written only from /saas. */
class SchoolContact extends Model
{
    use BelongsToSchool;

    protected $table = 'school_contacts';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [];
    }
}
