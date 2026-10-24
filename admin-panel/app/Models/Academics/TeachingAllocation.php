<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\Teacher;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeachingAllocation extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'teaching_allocations';

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['revision' => 1];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'active' => 'boolean'];
    }

    public function group()
    {
        return $this->belongsTo(TeachingGroup::class, 'teaching_group_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
