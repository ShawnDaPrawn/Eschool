<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DisciplineRecord extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['description', 'staff_note', 'basis', 'decision_reason', 'family_reason'];

    public $timestamps = true;

    protected function casts(): array
    {
        return ['description' => 'encrypted', 'staff_note' => 'encrypted', 'basis' => 'encrypted:array', 'decision_reason' => 'encrypted', 'family_reason' => 'encrypted', 'occurred_on' => 'date', 'published_at' => 'datetime', 'acknowledged_by_parent_at' => 'datetime', 'revision' => 'integer', 'framework_revision' => 'integer'];
    }
}
