<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A saved audience: a fixed list of people (definition user_ids) or a smart
 * group (definition session_year_id, match, parents, rules) resolved at
 * preview and send. Private ones are offered only to their creator.
 * engineering/specs/communication/custom-groups.md.
 *
 * @property string $kind
 * @property bool $shared
 */
class Audience extends Model
{
    public const FIXED = 'fixed';

    public const SMART = 'smart';

    /** Scopes a smart group can combine. */
    public const RULE_KINDS = ['grade', 'homeroom', 'group', 'activity_group'];

    public const MAX_RULES = 10;

    use BelongsToSchool;

    protected $table = 'audiences';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['definition', 'name'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'definition' => 'encrypted:array', 'active' => 'boolean', 'shared' => 'boolean', 'revision' => 'integer'];
    }

    public function isSmart(): bool
    {
        return $this->kind === self::SMART;
    }
}
