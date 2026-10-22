<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\FeesClass;
use App\Models\FeesType;
use App\Models\Parents;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The setup checklist on Home: what a new school does in its first week, each
 * step worked out from the school's own data and linked to the page that
 * completes it. Steps are about the school's current year.
 */
class SetupChecklist
{
    public function __construct(private readonly SessionYearContext $years) {}

    /**
     * The checklist for someone who can act on at least one step, or null.
     *
     * @return array{items: list<array<string, mixed>>, done: int, total: int, complete: bool}|null
     */
    public function forUser(User $user): ?array
    {
        $items = $this->items($user);
        if (! in_array(true, array_column($items, 'can_act'), true)) {
            return null;
        }
        $done = count(array_filter(array_column($items, 'done')));

        return ['items' => $items, 'done' => $done, 'total' => count($items), 'complete' => $done === count($items)];
    }

    /** @return list<array<string, mixed>> */
    public function items(User $user): array
    {
        $year = $this->years->current();

        return [
            $this->schoolProfile($user),
            $this->yearAndTerms($user, $year),
            $this->gradesAndHomerooms($user),
            $this->subjects($user, $year),
            $this->staff($user, $year),
            $this->learnersAndParents($user, $year),
            $this->fees($user, $year),
        ];
    }

    private function schoolProfile(User $user): array
    {
        $labels = [
            'school_name' => __('school_name'), 'school_email' => __('email'),
            'school_phone' => __('phone'), 'school_address' => __('address'),
        ];
        $missing = array_keys(array_filter($labels, fn ($label, $key) => trim((string) schoolSetting($key, '')) === '', ARRAY_FILTER_USE_BOTH));

        return $this->item($user, 'school_profile', $missing === [],
            $missing === [] ? __('checklist_school_profile_done') : __('checklist_missing', ['items' => implode(', ', array_map(fn ($k) => $labels[$k], $missing))]),
            url('settings'), ['setting-create']);
    }

    private function yearAndTerms(User $user, ?SessionYear $year): array
    {
        $running = $year && $year->end_date >= now()->toDateString();
        $terms = $year ? Semester::where('session_year_id', $year->id)->count() : 0;
        $newYearUrl = Route::has('new-year.index') ? route('new-year.index') : route('session-years.index');

        if (! $running) {
            return $this->item($user, 'year_terms', false,
                $year ? __('checklist_year_ended', ['year' => $year->name]) : __('checklist_no_year'),
                $newYearUrl, ['session-year-create']);
        }

        return $this->item($user, 'year_terms', $terms > 0,
            $terms > 0 ? trans_choice('checklist_year_terms_done', $terms, ['year' => $year->name, 'count' => $terms]) : __('checklist_no_terms', ['year' => $year->name]),
            route('semester.index'), ['semester-create']);
    }

    private function gradesAndHomerooms(User $user): array
    {
        $grades = ClassSchool::count();
        $homerooms = ClassSection::count();

        return $this->item($user, 'grades_homerooms', $grades > 0 && $homerooms > 0,
            __('checklist_grades_detail', ['grades' => $grades, 'homerooms' => $homerooms]),
            route('class.index'), ['class-create']);
    }

    private function subjects(User $user, ?SessionYear $year): array
    {
        $subjects = Subject::count();
        $gradesWithSubjects = $year ? ClassSubject::where('session_year_id', $year->id)->distinct()->count('class_id') : 0;
        $grades = ClassSchool::count();

        return $this->item($user, 'subjects', $subjects > 0 && $gradesWithSubjects > 0,
            __('checklist_subjects_detail', ['subjects' => $subjects, 'given' => $gradesWithSubjects, 'grades' => $grades]),
            $subjects > 0 ? route('class.subject') : route('subject.index'),
            $subjects > 0 ? ['assign-class-subject'] : ['subject-create']);
    }

    private function staff(User $user, ?SessionYear $year): array
    {
        $teachers = Teacher::count();
        $withTeacher = $year ? ClassTeacher::where('session_year_id', $year->id)->distinct()->count('class_section_id') : 0;
        $homerooms = ClassSection::count();

        return $this->item($user, 'staff', $teachers > 0 && $withTeacher > 0,
            __('checklist_staff_detail', ['teachers' => $teachers, 'with' => $withTeacher, 'homerooms' => $homerooms]),
            $teachers > 0 ? route('class.teacher') : route('teachers.index'),
            $teachers > 0 ? ['class-teacher-create'] : ['teacher-create']);
    }

    private function learnersAndParents(User $user, ?SessionYear $year): array
    {
        $learners = $year ? StudentSessions::where('session_year_id', $year->id)->where('status', 1)->distinct()->count('student_id') : 0;
        $parents = Parents::count();

        return $this->item($user, 'learners_parents', $learners > 0 && $parents > 0,
            __('checklist_learners_detail', ['learners' => $learners, 'parents' => $parents]),
            route('students.create'), ['student-create']);
    }

    private function fees(User $user, ?SessionYear $year): array
    {
        $types = FeesType::count();
        $gradesWithFees = $year ? FeesClass::where('session_year_id', $year->id)->distinct()->count('class_id') : 0;

        return $this->item($user, 'fees', $types > 0 && $gradesWithFees > 0,
            __('checklist_fees_detail', ['types' => $types, 'grades' => $gradesWithFees]),
            $types > 0 ? route('fees.class.index') : route('fees-type.index'),
            $types > 0 ? ['fees-classes'] : ['fees-type']);
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function item(User $user, string $key, bool $done, string $detail, string $url, array $permissions): array
    {
        return [
            'key' => $key,
            'title' => __('checklist_'.$key),
            'hint' => __('checklist_'.$key.'_hint'),
            'detail' => $detail,
            'done' => $done,
            'url' => $url,
            'can_act' => $user->canAny($permissions),
        ];
    }
}
