<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ProgressBandSettings extends Model
{
    use BelongsToSchool;

    protected $table = 'progress_band_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['high_bp' => 'integer', 'secure_bp' => 'integer', 'minimum_coverage_bp' => 'integer', 'revision' => 'integer'];
    }
}
