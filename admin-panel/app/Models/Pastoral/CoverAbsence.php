<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CoverAbsence extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['reason', 'cancel_reason'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted', 'cancel_reason' => 'encrypted', 'starts_on' => 'date', 'ends_on' => 'date', 'revision' => 'integer'];
    }
}
