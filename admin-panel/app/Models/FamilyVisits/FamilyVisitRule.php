<?php

declare(strict_types=1);

namespace App\Models\FamilyVisits;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FamilyVisitRule extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'family_visit_rules';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['max_window_minutes' => 'integer', 'max_future_days' => 'integer', 'scan_max_age_seconds' => 'integer', 'revision' => 'integer'];
    }
}
