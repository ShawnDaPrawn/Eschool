<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of schools with one owner (Action Plan 5.7, SA.18): a platform
 * record, written only from /saas. Which schools belong to it is each
 * school's own school_group_members row, agreed by the school.
 * engineering/specs/platform/school-groups.md.
 */
class SchoolGroup extends Model
{
    public const ACTIVE = 'active';

    protected $table = 'school_groups';

    protected $guarded = ['id'];

    public function members(): HasMany
    {
        return $this->hasMany(SchoolGroupMember::class, 'school_group_id');
    }

    public function admins(): HasMany
    {
        return $this->hasMany(SchoolGroupAdmin::class, 'school_group_id');
    }
}
