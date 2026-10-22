<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * A role given to a user at a school (model_has_roles). school_id stays out
 * of arrays and JSON, so the v1 answers that include a user's roles keep
 * their Phase 0 shape.
 */
class RolePivot extends MorphPivot
{
    protected $hidden = ['school_id'];
}
