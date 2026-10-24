<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Term extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'session_year_id',
        'start_date',
        'end_date',
        'grade_from', 'grade_to', 'marks_lock_on', 'reports_on',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'grade_from' => 'integer', 'grade_to' => 'integer',
        'marks_lock_on' => 'date', 'reports_on' => 'date',
    ];

    protected $appends = ['current', 'start_month_name', 'end_month_name'];

    public function class_subjects()
    {
        return $this->hasMany(ClassSubject::class, 'term_id', 'id')->with('subject');
    }

    public function elective_subject_groups()
    {
        return $this->hasMany(ElectiveSubjectGroup::class, 'term_id', 'id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'term_id', 'id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'term_id', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'term_id', 'id');
    }

    public function student_subjects()
    {
        return $this->hasMany(StudentSubject::class, 'term_id', 'id');
    }

    public function getCurrentAttribute(): bool
    {
        $now = Carbon::today();

        $start = $this->start_date;
        $end = $this->end_date;
        if (! $start || ! $end || $start->greaterThan($end)) {
            return false; // Preserve irregular imported dates, never treat them as active.
        }

        return $now->between($start, $end);
    }

    public function coversGrade(?int $grade): bool
    {
        return $grade === null ? $this->grade_from === 0 && $this->grade_to === 12
            : $this->grade_from <= $grade && $this->grade_to >= $grade;
    }

    public static function currentForClass(int $yearId, ?int $classId): ?self
    {
        $grade = $classId === null ? null : ClassSchool::query()->findOrFail($classId)->gradeLevel?->sort_order;

        return self::query()->where('session_year_id', $yearId)->forGrade($grade)
            ->get()->first(fn ($term) => $term->current);
    }

    public function scopeForGrade(Builder $query, ?int $grade): void
    {
        if ($grade === null) {
            $query->where('grade_from', 0)->where('grade_to', 12);
        } else {
            $query->where('grade_from', '<=', $grade)->where('grade_to', '>=', $grade);
        }
    }

    public function getStartMonthNameAttribute(): string
    {
        return $this->start_date ? $this->start_date->translatedFormat('F') : '';
    }

    public function getEndMonthNameAttribute(): string
    {
        return $this->end_date ? $this->end_date->translatedFormat('F') : '';
    }
}
