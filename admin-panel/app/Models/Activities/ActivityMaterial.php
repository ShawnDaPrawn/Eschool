<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivityMaterial extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_materials';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['markdown' => 'encrypted', 'published' => 'boolean', 'revision' => 'integer'];
    }
}
