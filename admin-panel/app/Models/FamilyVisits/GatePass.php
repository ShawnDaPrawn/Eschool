<?php

declare(strict_types=1);

namespace App\Models\FamilyVisits;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'gate_passes';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['collector', 'reason'];

    protected function casts(): array
    {
        return ['collector' => 'encrypted', 'reason' => 'encrypted', 'starts_at' => UtcInstant::class, 'ends_at' => UtcInstant::class, 'collected_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
