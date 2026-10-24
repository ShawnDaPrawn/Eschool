<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\BelongsToSchool;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Students extends Model
{
    use Auditable, BelongsToSchool, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'class_id',
        'class_section_id',
        'category_id',
        'admission_no',
        'roll_number',
        'caste',
        'religion',
        'admission_date',
        'blood_group',
        'height',
        'weight',
        'is_new_admission',
        'father_id',
        'mother_id',
        'guardian_id',
        'dynamic_fields',
        'application_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['attendance_qr_version', 'deleted_at', 'created_at', 'updated_at', 'student_number'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'is_new_admission' => 'boolean',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    /**
     * Get the height attribute with proper string casting for API
     */
    public function getHeightAttribute($value)
    {
        if (empty($value) || ! is_numeric($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Get the weight attribute with proper string casting for API
     */
    public function getWeightAttribute($value)
    {
        if (empty($value) || ! is_numeric($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Get the is_new_admission attribute with proper integer casting for API
     */
    public function getIsNewAdmissionAttribute($value)
    {
        return (int) $value;
    }

    public function announcement(): MorphMany
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class_section(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class)->with('class.medium', 'section');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassSchool::class)->with('medium', 'streams');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(Parents::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Parents::class, 'mother_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Parents::class, 'guardian_id');
    }

    public function exam_result(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'student_id');
    }

    public function exam_marks(): HasMany
    {
        return $this->hasMany(ExamMarks::class, 'student_id');
    }

    public function fees_paid(): HasOne
    {
        return $this->hasOne(FeesPaid::class, 'student_id')->with('class', 'session_year');
    }

    public function student_subjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class, 'student_id');
    }

    public function studentSessions()
    {
        return $this->hasMany(StudentSessions::class, 'student_id', 'id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function onlineExamAnswers(): HasMany
    {
        return $this->hasMany(OnlineExamStudentAnswer::class, 'student_id');
    }

    public function onlineExamStatus(): HasMany
    {
        return $this->hasMany(StudentOnlineExamStatus::class, 'student_id');
    }

    /**
     * Get student subjects with core and elective subjects.
     *
     * @return array<string, mixed>
     */
    public function subjects(bool $ignoreTerm = false): array
    {
        $currentTerm = $this->getCurrentTerm();
        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];

        // Use class_section from student_sessions for the current session year
        $studentSession = StudentSessions::where([
            'student_id' => $this->id,
            'session_year_id' => $session_year_id,
        ])->first();

        $classSection = $studentSession ? $studentSession->class_section : $this->class_section;

        $class_id = $classSection->class->id;
        $class_section_id = $classSection->id;

        $class = ClassSchool::withSessionConfigFor($session_year_id)
            ->where('id', $class_id)
            ->first();

        $includesTerms = ! $ignoreTerm
            && $class?->getIncludeTermsForSession($session_year_id)
            && $currentTerm;

        // Core subjects — filter by term if applicable
        $core_subjects = $includesTerms
            ? $classSection->class->coreSubject
                ?->where('term_id', $currentTerm->id)
                ->where('session_year_id', $session_year_id)
                ->values()->toArray() ?? []
            : $classSection->class->coreSubject
                ?->where('session_year_id', $session_year_id)
                ->values()->toArray() ?? [];

        // Elective subjects — include term_id in select so where() works correctly
        $electiveSubjectQuery = StudentSubject::where('student_id', $this->id)
            ->where('class_section_id', $class_section_id)
            ->where('session_year_id', $session_year_id)
            ->select('subject_id', 'term_id')
            ->with('subject');

        if ($includesTerms) {
            $electiveSubjectQuery->where('term_id', $currentTerm->id);
            $elective_subject_count = $classSection->class->electiveSubjectGroup
                ->where('session_year_id', $session_year_id)
                ->where('term_id', $currentTerm->id)
                ->count();
        } else {
            $elective_subject_count = $classSection->class->electiveSubjectGroup
                ->where('session_year_id', $session_year_id)
                ->count();
        }

        $elective_subjects = $electiveSubjectQuery->get();

        return [
            'core_subject' => $core_subjects,
            'elective_subject' => $elective_subject_count > 0 ? $elective_subjects : [],
        ];
    }

    /**
     * Get class subjects with core and elective subject groups.
     *
     * @return array<string, mixed>
     */
    public function classSubjects(): array
    {
        $currentTerm = $this->getCurrentTerm();
        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];
        $class = ClassSchool::withSessionConfigFor($session_year_id)
            ->where('id', $this->class_section->class->id)
            ->first();

        $includesTerms = $class?->getIncludeTermsForSession($session_year_id) && $currentTerm;

        if ($includesTerms) {
            $core_subjects = $this->class_section->class->coreSubject
                ->where('session_year_id', $session_year_id)
                ->where('term_id', $currentTerm->id)->values();
            $elective_subjects = $this->class_section->class->electiveSubjectGroup
                ->where('term_id', $currentTerm->id)
                ->where('session_year_id', $session_year_id)
                ->values()
                ->load('electiveSubjects.subject');
        } else {
            $core_subjects = $this->class_section->class->coreSubject
                ->where('session_year_id', $session_year_id)
                ->values();

            $elective_subjects = $this->class_section->class->electiveSubjectGroup
                ->where('session_year_id', $session_year_id)
                ->values()
                ->load('electiveSubjects.subject');
        }

        return [
            'core_subject' => $core_subjects,
            'elective_subject_group' => $elective_subjects,
        ];
    }

    /**
     * Get current term.
     */
    private function getCurrentTerm(): ?Term
    {
        $year = getSettings('session_year')['session_year'] ?? null;
        $section = $this->studentSessions()->where('session_year_id', $year)->first()?->class_section ?? $this->class_section;
        $grade = $section?->class?->gradeLevel?->sort_order;

        return Term::where('session_year_id', $year)->forGrade($grade)
            ->get()
            ->first(fn ($term) => $term->current);
    }

    /**
     * Get all parent relationships.
     */
    public function parents()
    {
        return $this->father()->union($this->mother())->union($this->guardian());
    }

    /**
     * Scope query to filter students for teachers.
     */
    public function scopeOfTeacher(Builder $query): Builder
    {
        $user = Auth::user();

        // Super Admin can see all students. (There is no seeded 'Admin' role; the old
        // check for one let any custom role named "Admin" see every student.)
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        // If not a teacher, return empty result
        if (! $user->hasRole('Teacher')) {
            return $query->where('class_section_id', 0); // No access
        }

        $class_section_ids = [];

        // Get class sections where user is class teacher
        $class_teacher = $user->teacher->class_sections;
        if ($class_teacher->isNotEmpty()) {
            $class_section_ids = array_merge($class_section_ids, $class_teacher->pluck('class_section_id')->toArray());
        }

        // Get class sections where user is subject teacher
        $subject_teachers = $user->teacher->subjects;
        if ($subject_teachers) {
            foreach ($subject_teachers as $subject_teacher) {
                $class_section_ids[] = $subject_teacher->class_section_id;
            }
        }

        return empty($class_section_ids)
            ? $query->where('class_section_id', 0) // No access
            : $query->whereIn('class_section_id', array_unique($class_section_ids));
    }

    /**
     * Get the father's image URL.
     */
    public function getFatherImageAttribute(?string $value): string
    {
        return $value ? url(Storage::url($value)) : '';
    }

    /**
     * Get the mother's image URL.
     */
    public function getMotherImageAttribute(?string $value): string
    {
        return $value ? url(Storage::url($value)) : '';
    }

    /**
     * Cascade delete student-owned academic data.
     *
     * Academic and operational records that cannot exist without a student
     * are deleted automatically. Financial records are intentionally preserved
     * for audit and compliance purposes.
     */
    protected static function booted()
    {
        static::created(function (Students $student) {
            if (! app(CurrentSchool::class)->isMigrating()) {
                app(StudentNumbers::class)->assign($student);
            }
        });

        static::saved(fn (Students $student) => app(GuardianService::class)->syncLegacy($student));

        static::deleting(function (Students $student) {

            if ($student->isForceDeleting()) {

                // Force delete (hard delete)
                $student->assignmentSubmissions()->forceDelete();
                $student->attendances()->forceDelete();
                $student->exam_marks()->forceDelete();
                $student->exam_result()->forceDelete();
                $student->onlineExamAnswers()->forceDelete();
                $student->onlineExamStatus()->forceDelete();
                $student->studentSessions()->forceDelete();
                $student->student_subjects()->forceDelete();
            } else {

                // Soft delete
                $student->assignmentSubmissions()->delete();
                $student->attendances()->delete();
                $student->exam_marks()->delete();
                $student->exam_result()->delete();
                $student->onlineExamAnswers()->delete();
                $student->onlineExamStatus()->delete();
                $student->studentSessions()->delete();
                $student->student_subjects()->delete();
            }
        });
    }
}
