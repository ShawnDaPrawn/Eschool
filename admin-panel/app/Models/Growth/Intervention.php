<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['action', 'outcome'];

    protected function casts(): array
    {
        return ['started_on' => 'date', 'review_on' => 'date', 'revision' => 'integer', 'action' => 'encrypted', 'outcome' => 'encrypted'];
    }
}
