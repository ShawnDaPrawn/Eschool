<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Models\Academics\GradeLevel;
use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The age stage a school gives one grade (Action Plan 4.1). Every change is audited. */
class SchoolStage extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['grade_level_id' => 'integer', 'changed_by' => 'integer'];
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
