<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_categories';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'revision' => 'integer'];
    }
}
