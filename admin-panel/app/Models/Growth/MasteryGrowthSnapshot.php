<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Derived term-start and term-end mastery for one learner, topic, term, audience and model version. */
class MasteryGrowthSnapshot extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_growth_snapshots';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['start_level' => 'integer', 'start_score_bp' => 'integer', 'start_confidence_bp' => 'integer', 'start_evidence_count' => 'integer',
            'end_level' => 'integer', 'end_score_bp' => 'integer', 'end_confidence_bp' => 'integer', 'end_evidence_count' => 'integer',
            'end_as_of' => 'date', 'end_final' => 'boolean', 'captured_at' => 'datetime'];
    }
}
