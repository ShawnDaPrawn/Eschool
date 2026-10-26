<?php

declare(strict_types=1);

namespace App\Models\FamilyVisits;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FamilyVisitEvent extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'family_visit_events';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array'];
    }
}
