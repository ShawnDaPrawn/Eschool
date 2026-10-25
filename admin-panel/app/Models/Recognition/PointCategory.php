<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PointCategory extends Model
{
    use BelongsToSchool;

    protected $table = 'point_categories';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['default_points' => 'integer', 'minimum_points' => 'integer', 'maximum_points' => 'integer', 'active' => 'boolean', 'revision' => 'integer'];
    }
}
