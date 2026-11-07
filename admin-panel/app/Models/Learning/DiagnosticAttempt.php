<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A learner's attempt at a published library diagnostic (Action Plan 3.7). */
class DiagnosticAttempt extends Model
{
    use BelongsToSchool;

    protected $table = 'diagnostic_attempts';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'teaching_group_id' => 'integer', 'subject_id' => 'integer', 'grade_level_id' => 'integer', 'session_year_id' => 'integer',
            'library_item_id' => 'integer', 'library_item_version_id' => 'integer', 'started_by' => 'integer', 'completed_at' => UtcInstant::class];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DiagnosticAttemptItem::class)->orderBy('position');
    }
}
