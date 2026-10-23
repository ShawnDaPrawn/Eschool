<?php

declare(strict_types=1);

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

/** Platform reference data; no school/person records and no API writes. */
class GradeLevel extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
