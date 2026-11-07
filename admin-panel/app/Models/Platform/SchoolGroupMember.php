<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One school's place in a school group: HQ's invitation, the school's own
 * agreement (or refusal), and its end. School-owned: the school reads its own
 * rows in its panel; HQ and the group read them across schools, and only an
 * active row lets the group see the school's counts.
 */
class SchoolGroupMember extends Model
{
    use BelongsToSchool;

    public const INVITED = 'invited';

    public const ACTIVE = 'active';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    public const LEFT = 'left';

    public const REMOVED = 'removed';

    public const OPEN = [self::INVITED, self::ACTIVE];

    public const BILLED_ALONE = 'school';

    public const BILLED_THROUGH_GROUP = 'group';

    protected $table = 'school_group_members';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['invited_at' => 'datetime', 'decided_at' => 'datetime', 'ended_at' => 'datetime', 'school_group_id' => 'integer', 'open_key' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SchoolGroup::class, 'school_group_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }
}
