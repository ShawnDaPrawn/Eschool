<?php

declare(strict_types=1);

namespace App\Models\FamilyVisits;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FamilyVisitOperation extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'family_visit_operations';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['receipt'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
