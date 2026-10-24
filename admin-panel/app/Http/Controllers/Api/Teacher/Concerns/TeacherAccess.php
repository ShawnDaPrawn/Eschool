<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher\Concerns;

use App\Models\Announcement;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\Lesson;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Term;
use App\Services\ResponseService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * What the signed-in teacher may see and change: the class sections they are
 * class teacher or subject teacher of, and those sections' students, lessons
 * and announcements. Shared by the teacher app's API controllers.
 */
trait TeacherAccess
{
    /**
     * The signed-in user's teacher record. A user without one teaches nothing,
     * so the request is refused (a 403 thrown as an HttpResponseException, which
     * every helper below passes on) rather than continuing with a null teacher.
     */
    private function currentTeacher(): Teacher
    {
        $teacher = Auth::user()?->teacher;

        if (! $teacher) {
            throw new HttpResponseException(ResponseService::errorResponse('no_permission_message', null, 403));
        }

        return $teacher;
    }

    private function currentSessionYearId()
    {
        return getSettings('session_year')['session_year'];
    }

    /** Whether the teacher is class teacher of the class section in the (current) session year. */
    private function isClassTeacherOf($classSectionId, $sessionYearId = null): bool
    {
        return ClassTeacher::where('class_teacher_id', $this->currentTeacher()->id)
            ->where('class_section_id', $classSectionId)
            ->where('session_year_id', $sessionYearId ?? $this->currentSessionYearId())
            ->exists();
    }

    /**
     * Whether the teacher teaches the subject in the class section this session year
     * (in the given term, when one is given - same rule as sendAnnouncement).
     */
    private function teachesSubject($classSectionId, $subjectId, $termId = null): bool
    {
        return SubjectTeacher::where('teacher_id', $this->currentTeacher()->id)
            ->where('class_section_id', $classSectionId)
            ->where('subject_id', $subjectId)
            ->where('session_year_id', $this->currentSessionYearId())
            ->when($termId, fn ($q) => $q->where(
                fn ($q) => $q->where('term_id', $termId)->orWhereNull('term_id')
            ))
            ->exists();
    }

    /** Class sections the teacher is class teacher or subject teacher of in the current session year. */
    private function teacherClassSectionIds()
    {
        $teacherId = $this->currentTeacher()->id;
        $sessionYearId = $this->currentSessionYearId();

        return ClassTeacher::where('class_teacher_id', $teacherId)
            ->where('session_year_id', $sessionYearId)
            ->pluck('class_section_id')
            ->merge(
                SubjectTeacher::where('teacher_id', $teacherId)
                    ->where('session_year_id', $sessionYearId)
                    ->pluck('class_section_id')
            )
            ->unique()
            ->values();
    }

    /** Class sections the student belongs to: their current one plus this session year's. */
    private function studentClassSectionIds(Students $student, $sessionYearId = null)
    {
        return StudentSessions::where('student_id', $student->id)
            ->where('session_year_id', $sessionYearId ?? $this->currentSessionYearId())
            ->pluck('class_section_id')
            ->push($student->class_section_id)
            ->filter()
            ->unique()
            ->values();
    }

    /** Whether the student is in a class section the teacher is class teacher or subject teacher of. */
    private function teachesStudent($studentId): bool
    {
        $student = $studentId ? Students::find($studentId) : null;

        if (! $student) {
            return false;
        }

        return $this->studentClassSectionIds($student)
            ->intersect($this->teacherClassSectionIds())
            ->isNotEmpty();
    }

    /** Whether every given student belongs to the class section (now or this session year). */
    private function allStudentsInClassSection($studentIds, $classSectionId): bool
    {
        $studentIds = collect($studentIds)->filter(fn ($id) => $id !== null && $id !== '')->unique();

        if ($studentIds->isEmpty()) {
            return true;
        }

        $sessionYearId = $this->currentSessionYearId();
        $matching = Students::whereIn('id', $studentIds)
            ->where(fn ($q) => $q->where('class_section_id', $classSectionId)
                ->orWhereHas('studentSessions', fn ($q) => $q->where('class_section_id', $classSectionId)
                    ->where('session_year_id', $sessionYearId)))
            ->count();

        return $matching === $studentIds->count();
    }

    /** A lesson belongs to the teacher when they teach its subject in its class section. */
    private function ownsLesson(?Lesson $lesson): bool
    {
        return $lesson !== null && $this->teachesSubject($lesson->class_section_id, $lesson->subject_id);
    }

    /** An announcement belongs to the teacher when it hangs off one of their subject-teacher rows. */
    private function ownsAnnouncement(?Announcement $announcement): bool
    {
        return $announcement !== null
            && $announcement->table_type === SubjectTeacher::class
            && SubjectTeacher::where('id', $announcement->table_id)
                ->where('teacher_id', $this->currentTeacher()->id)
                ->exists();
    }

    private function applyTermFilter($query, $classSectionId, $sessionYearId, $termId = null): void
    {
        $classId = ClassSection::where('id', $classSectionId)->value('class_id');
        $isClassHaveTerm = ClassSessionConfig::classHasTerm($classId, $sessionYearId);

        if (! $isClassHaveTerm) {
            return;
        }
        if (! $termId) {
            // This session year's current term (its id, not the model).
            $termId = Term::currentForClass((int) $sessionYearId, $classId === null ? null : (int) $classId)?->id;
        }

        if ($termId) {
            $query->where('term_id', $termId);
        }
    }
}
