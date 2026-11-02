<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A practice question used in a teaching group (Action Plan 3.6). */
class PracticeItemGroup extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_item_groups';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['practice_item_id' => 'integer', 'teaching_group_id' => 'integer', 'added_by' => 'integer'];
    }
}
