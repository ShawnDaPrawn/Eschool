<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeroomAllocation extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'homeroom_allocations';

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['revision' => 1];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'active' => 'boolean'];
    }

    public function homeroom()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}
