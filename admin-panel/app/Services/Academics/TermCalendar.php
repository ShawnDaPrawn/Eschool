<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\ClassSchool;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\User;
use App\Policies\Academics\TermPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TermCalendar
{
    public const LINKS = ['mark_snapshots', 'mark_releases', 'academic_exam_links', 'curriculum_topics', 'assessment_plans', 'class_subjects', 'elective_subject_groups', 'timetables', 'student_subjects', 'lessons', 'assignments', 'announcements', 'class_teachers', 'subject_teachers', 'exams', 'online_exams'];

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'grade_from' => ['sometimes', 'integer', 'min:0', 'max:12'],
            'grade_to' => ['sometimes', 'integer', 'min:0', 'max:12'],
            'marks_lock_on' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'reports_on' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    public static function data(Term $term): array
    {
        return ['id' => (int) $term->id, 'session_year_id' => (int) $term->session_year_id,
            'name' => $term->name, 'starts_on' => $term->start_date?->toDateString(), 'ends_on' => $term->end_date?->toDateString(),
            'grade_from' => $term->grade_from, 'grade_to' => $term->grade_to,
            'marks_lock_on' => $term->marks_lock_on?->toDateString(), 'reports_on' => $term->reports_on?->toDateString(),
            'current' => $term->current, 'needs_review' => ! $term->start_date || ! $term->end_date || $term->start_date->gt($term->end_date)];
    }

    public function list(User $actor, int $yearId, ?int $grade = null): array
    {
        app(TermPolicy::class)->authorise($actor);
        $year = SessionYear::query()->findOrFail($yearId);
        $query = $year->terms()->orderBy('start_date')->orderBy('grade_from')->orderBy('id');
        if ($grade !== null) {
            $query->forGrade($grade);
        }

        return ['session_year_id' => (int) $year->id, 'terms' => $query->get()->map(fn ($term) => self::data($term))->all()];
    }

    public function save(User $actor, array $input, ?int $id = null): Term
    {
        $operation = $id === null ? 'create' : 'edit';
        app(TermPolicy::class)->authorise($actor, $operation);
        $data = Validator::make($input, ['session_year_id' => ['required', 'integer', 'min:1']] + self::rules())->validate();

        return DB::transaction(function () use ($actor, $operation, $id, $data) {
            $term = $id === null ? null : Term::query()->findOrFail($id);
            if ($term !== null && (int) $term->session_year_id !== (int) $data['session_year_id']) {
                throw ValidationException::withMessages(['session_year_id' => 'A term cannot be moved to another school year.']);
            }
            $year = SessionYear::query()->lockForUpdate()->findOrFail($data['session_year_id']);
            app(TermPolicy::class)->authorise($actor, $operation);
            $term = $id === null ? new Term : Term::query()->lockForUpdate()->findOrFail($id);
            $before = $term->exists ? self::data($term) : [];
            $row = $this->normalise($data + $before);
            if ($term->exists && ($row['grade_from'] !== $term->grade_from || $row['grade_to'] !== $term->grade_to)
                && $this->referenced((int) $term->id)) {
                throw ValidationException::withMessages(['grade_from' => 'Reassign academic records before changing an in-use term grade range.']);
            }
            $existing = $year->terms()->whereKeyNot($term->id ?? 0)->lockForUpdate()->get()->map(fn ($t) => self::data($t))->all();
            $this->validateRows($year, [$row], $existing);
            $term->fill($this->storage($row) + ['session_year_id' => $year->id]);
            if (! $term->exists || $term->isDirty()) {
                $term->save();
                Audit::record('academics.term_saved', $term, $before, self::data($term));
            }

            return $term;
        });
    }

    public function classSettings(User $actor, int $yearId): array
    {
        app(TermPolicy::class)->authoriseClassSettings($actor);
        SessionYear::query()->findOrFail($yearId);

        return ClassSchool::query()->whereIncludeTermsForSession($yearId)->with('medium')->get()
            ->map(fn ($class) => ['id' => (int) $class->id, 'name' => $class->name, 'medium_name' => $class->medium?->name ?? ''])->all();
    }

    public function enableClass(User $actor, int $yearId, int $classId): void
    {
        app(TermPolicy::class)->authorise($actor, 'edit');
        DB::transaction(function () use ($actor, $yearId, $classId) {
            SessionYear::query()->lockForUpdate()->findOrFail($yearId);
            $class = ClassSchool::query()->lockForUpdate()->findOrFail($classId);
            app(TermPolicy::class)->authorise($actor, 'edit');
            $authority = app(ProfilePolicy::class);
            abort_unless($authority->permission($authority->actor($actor), 'assign-class-subject'), 403);
            if (! Term::query()->where('session_year_id', $yearId)->forClass($classId)->exists()) {
                throw ValidationException::withMessages(['class_id' => __('Add calendar dates covering this class grade before enabling terms.')]);
            }
            $before = $class->getIncludeTermsForSession($yearId);
            if ($before !== true) {
                $class->syncIncludeTermsForSession($yearId, true);
                Audit::record('academics.class_terms_enabled', $class,
                    ['session_year_id' => $yearId, 'include_terms' => $before],
                    ['session_year_id' => $yearId, 'include_terms' => true]);
            }
        });
    }

    public function remove(User $actor, int $id): void
    {
        app(TermPolicy::class)->authorise($actor, 'delete');
        DB::transaction(function () use ($actor, $id) {
            $term = Term::query()->findOrFail($id);
            SessionYear::query()->lockForUpdate()->findOrFail($term->session_year_id);
            $term = Term::query()->lockForUpdate()->findOrFail($id);
            app(TermPolicy::class)->authorise($actor, 'delete');
            if ($this->referenced($id)) {
                throw ValidationException::withMessages(['term' => 'This term is in use and cannot be removed.']);
            }
            $before = self::data($term);
            $term->delete();
            Audit::record('academics.term_removed', $term, $before, ['deleted' => true]);
        });
    }

    private function referenced(int $id): bool
    {
        foreach (self::LINKS as $table) {
            // Include archived and anomalous references too: never orphan academic records.
            if (DB::table($table)->where('term_id', $id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function normalise(array $row): array
    {
        $data = Validator::make($row, self::rules())->validate();
        $data['grade_from'] = (int) ($data['grade_from'] ?? 0);
        $data['grade_to'] = (int) ($data['grade_to'] ?? 12);

        return $data + ['marks_lock_on' => null, 'reports_on' => null];
    }

    public function storage(array $row): array
    {
        return ['name' => $row['name'], 'start_date' => $row['starts_on'], 'end_date' => $row['ends_on'],
            'grade_from' => $row['grade_from'], 'grade_to' => $row['grade_to'],
            'marks_lock_on' => $row['marks_lock_on'], 'reports_on' => $row['reports_on']];
    }

    /** Parent year must be locked by every caller before checking/applying rows. */
    public function validateRows(SessionYear $year, array $rows, array $existing = []): void
    {
        if (count($rows) + count($existing) > 100) {
            throw ValidationException::withMessages(['terms' => 'A school year supports up to 100 calendar entries.']);
        }
        $start = substr((string) $year->start_date, 0, 10);
        $end = substr((string) $year->end_date, 0, 10);
        foreach ($rows as $index => $row) {
            if ($row['starts_on'] < $start || $row['ends_on'] > $end || $row['grade_from'] > $row['grade_to']) {
                throw ValidationException::withMessages(["terms.$index" => 'Term dates must be within the year and the grade range must be in order.']);
            }
            if ($row['marks_lock_on'] !== null && $row['reports_on'] !== null && $row['reports_on'] < $row['marks_lock_on']) {
                throw ValidationException::withMessages(["terms.$index.reports_on" => 'Reports must not precede the marks-lock date.']);
            }
            foreach ($existing as $other) {
                if ($row['grade_from'] <= $other['grade_to'] && $row['grade_to'] >= $other['grade_from']
                    && $row['starts_on'] <= $other['ends_on'] && $row['ends_on'] >= $other['starts_on']) {
                    throw ValidationException::withMessages(["terms.$index" => 'Term dates overlap for the same grades.']);
                }
            }
            $existing[] = $row;
        }
    }

    /** Trusted year-creation path; callers already enforce year-creation authority. */
    public function initialise(SessionYear $year, array $rows): void
    {
        $rows = array_map(fn ($row) => $this->normalise($row), $rows);
        $this->validateRows($year, $rows);
        foreach ($rows as $row) {
            $term = $year->terms()->create($this->storage($row));
            Audit::record('academics.term_saved', $term, [], self::data($term));
        }
    }
}
