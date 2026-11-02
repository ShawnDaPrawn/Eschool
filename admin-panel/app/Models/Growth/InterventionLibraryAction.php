<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A school's reusable, non-clinical intervention action (Action Plan 4.11). */
class InterventionLibraryAction extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['steps' => 'array', 'suggested_weeks' => 'integer', 'archived_at' => 'datetime', 'revision' => 'integer'];
    }
}
