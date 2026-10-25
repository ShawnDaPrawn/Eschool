<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MasteryEvidence extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_evidence';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['achieved_units' => 'integer', 'maximum_units' => 'integer', 'observed_at' => 'datetime'];
    }
}
