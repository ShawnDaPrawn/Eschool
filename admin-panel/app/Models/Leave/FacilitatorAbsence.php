<?php

declare(strict_types=1);

namespace App\Models\Leave;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FacilitatorAbsence extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['from_date' => 'date', 'to_date' => 'date', 'portions' => 'array', 'reason' => 'encrypted', 'attachments' => 'encrypted:array', 'decided_at' => 'datetime', 'revision' => 'integer'];
    }
}
