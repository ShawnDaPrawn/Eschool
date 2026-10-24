<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Audience extends Model
{
    use BelongsToSchool;

    protected $table = 'audiences';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['definition', 'name'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'definition' => 'encrypted:array', 'active' => 'boolean', 'revision' => 'integer'];
    }
}
