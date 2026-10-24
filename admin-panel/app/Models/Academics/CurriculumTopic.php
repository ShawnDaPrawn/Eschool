<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumTopic extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'week_from' => 'integer', 'week_to' => 'integer'];
    }

    public function curriculum()
    {
        return $this->belongsTo(CurriculumPlan::class, 'curriculum_plan_id');
    }
}
