<?php

declare(strict_types=1);

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

/** Reference thresholds only; this does not recalculate legacy marks. */
class AcademicRatingLevel extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'min_basis_points' => 'integer', 'max_basis_points' => 'integer'];
    }
}
