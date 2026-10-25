<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardCategoryAdmin extends Model
{
    use BelongsToSchool;

    protected $table = 'award_category_admins';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'revision' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date'];
    }
}
