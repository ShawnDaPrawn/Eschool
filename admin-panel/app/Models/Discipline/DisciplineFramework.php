<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DisciplineFramework extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['definition'];

    public $timestamps = true;

    protected function casts(): array
    {
        return ['definition' => 'encrypted:array', 'revision' => 'integer'];
    }
}
