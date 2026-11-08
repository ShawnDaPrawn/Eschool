<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A group-level role (config/school_groups.php roles): a person who may see
 * the group's dashboard. It grants nothing inside any school; a school's own
 * role grants decide that. Written only from /saas; revoked, never deleted.
 */
class SchoolGroupAdmin extends Model
{
    protected $table = 'school_group_admins';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['revoked_at' => 'datetime', 'school_group_id' => 'integer', 'user_id' => 'integer', 'open_key' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
