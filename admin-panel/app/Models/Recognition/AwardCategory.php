<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardCategory extends Model
{
    use BelongsToSchool;

    protected $table = 'award_categories';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['parent_id' => 'integer', 'grade_level_id' => 'integer', 'subject_id' => 'integer', 'activity_group_id' => 'integer', 'active' => 'boolean', 'revision' => 'integer'];
    }
}
