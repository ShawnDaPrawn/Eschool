<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RiskSettings extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['academic_threshold_bp' => 'integer', 'minimum_coverage_bp' => 'integer', 'absence_threshold_bp' => 'integer', 'minimum_registers' => 'integer', 'revision' => 'integer'];
    }
}
