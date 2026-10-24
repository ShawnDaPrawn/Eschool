<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\DatedAcademicProjection;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassTeacher extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $hidden = ['homeroom_allocation_id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new DatedAcademicProjection('homeroom'));
        static::updating(fn ($row) => abort_if($row->getRawOriginal('homeroom_allocation_id') !== null, 409, __('Edit the dated homeroom allocation instead.')));
        static::deleting(fn ($row) => abort_if($row->homeroom_allocation_id !== null, 409, __('Edit the dated homeroom allocation instead.')));
    }

    protected $fillable = [
        'class_section_id',
        'class_teacher_id',
        'session_year_id',
        'term_id',
    ];

    public function scopeForTermOrDated($query, ?int $termId)
    {
        return $query->where(fn ($q) => $q->where('term_id', $termId)->orWhereNotNull('homeroom_allocation_id'));
    }

    public function classSections()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function classTeachers()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }
}
