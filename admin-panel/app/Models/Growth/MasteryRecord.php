<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MasteryRecord extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_records';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'score_bp' => 'integer', 'confidence_bp' => 'integer', 'evidence_count' => 'integer', 'computed_at' => 'datetime'];
    }
}
