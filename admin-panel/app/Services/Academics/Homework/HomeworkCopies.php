<?php

declare(strict_types=1);

namespace App\Services\Academics\Homework;

use App\Models\Assignment;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\File;
use App\Models\Notification;
use App\Models\Students;
use App\Models\StudentSubject;
use App\Models\SubjectTeacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Notifications\Notifier;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reusing homework: an existing assignment is set again for other classes (or
 * the same class in a later year). Each copy is a new assignment in the current
 * session year with its own due date, submissions and copies of the files, so
 * marking, deleting or editing one class's homework never touches another's.
 *
 * Targets are the class sections (and term, where the class uses terms) that
 * study the assignment's subject this session year. A teacher may only copy to
 * classes they teach the subject in; a panel user with assignment rights who is
 * not a teacher may copy to any class in the school that studies the subject.
 * Every lookup goes through the school-scoped models, so another school's
 * classes are never offered or accepted.
 */
class HomeworkCopies
{
    /**
     * Where the assignment can be set: one entry per class section (per term for
     * classes that use terms) in the given session year.
     *
     * @return Collection<int, array{key: string, class_section_id: int, term_id: int|null, name: string}>
     */
    public function targets(Assignment $source, User $actor, int $sessionYearId): Collection
    {
        $teacherId = $this->teacherId($actor);

        $classIds = ClassSubject::query()
            ->where('subject_id', $source->subject_id)
            ->where('session_year_id', $sessionYearId)
            ->pluck('class_id')
            ->unique();

        $sections = ClassSection::query()
            ->with('class.medium', 'class.streams', 'section')
            ->whereIn('class_id', $classIds)
            ->when($teacherId !== null, fn ($q) => $q->whereIn('id', SubjectTeacher::query()
                ->where('teacher_id', $teacherId)
                ->where('subject_id', $source->subject_id)
                ->where('session_year_id', $sessionYearId)
                ->select('class_section_id')))
            ->get()
            ->filter(fn ($section) => $section->class && $section->section)
            ->sortBy(fn ($section) => $section->class->name.' '.$section->section->name);

        $targets = collect();
        foreach ($sections as $section) {
            $name = trim($section->class->name.' '.$section->section->name.' - '
                .($section->class->medium->name ?? '').' '.($section->class->streams->name ?? ''));
            $terms = $this->termsFor($section, $source->subject_id, $sessionYearId, $teacherId);

            if ($terms === null) {
                $targets->push(['key' => $section->id.':', 'class_section_id' => (int) $section->id, 'term_id' => null, 'name' => $name]);

                continue;
            }

            foreach ($terms as $term) {
                $targets->push([
                    'key' => $section->id.':'.$term->id,
                    'class_section_id' => (int) $section->id,
                    'term_id' => (int) $term->id,
                    'name' => $name.' - '.$term->name,
                ]);
            }
        }

        return $targets->values();
    }

    /**
     * Set the assignment for each target. Every target must be one of
     * targets(); nothing is created unless all of them are.
     *
     * @param  array<int, array{class_section_id: int|string, term_id?: int|string|null}>  $requested
     * @return Collection<int, Assignment>
     */
    public function copy(Assignment $source, User $actor, array $requested, CarbonInterface $dueDate, int $sessionYearId): Collection
    {
        if ($requested === []) {
            throw ValidationException::withMessages(['class_section_ids' => __('Choose at least one class.')]);
        }

        if ($dueDate->lessThanOrEqualTo(Carbon::now())) {
            throw ValidationException::withMessages(['due_date' => __('The due date must be in the future.')]);
        }

        $allowed = $this->targets($source, $actor, $sessionYearId)->keyBy('key');
        $chosen = collect($requested)
            ->map(fn ($target) => ((int) $target['class_section_id']).':'.(empty($target['term_id']) ? '' : (int) $target['term_id']))
            ->unique()
            ->values();

        $unknown = $chosen->reject(fn ($key) => $allowed->has($key));
        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages(['class_section_ids' => __('You cannot set this homework for one of the chosen classes.')]);
        }

        $source->loadMissing('file', 'subject');
        $copied = collect();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($source, $chosen, $allowed, $dueDate, $sessionYearId, $copied, &$storedPaths) {
                foreach ($chosen as $key) {
                    $target = $allowed->get($key);

                    $copy = new Assignment;
                    $copy->class_section_id = $target['class_section_id'];
                    $copy->term_id = $target['term_id'];
                    $copy->subject_id = $source->subject_id;
                    $copy->name = $source->name;
                    $copy->instructions = $source->instructions;
                    $copy->due_date = $dueDate->format('Y-m-d H:i:s');
                    $copy->points = $source->points;
                    $copy->resubmission = $source->resubmission ? 1 : 0;
                    $copy->extra_days_for_resubmission = $source->resubmission ? $source->extra_days_for_resubmission : null;
                    $copy->session_year_id = $sessionYearId;
                    $copy->save();

                    foreach ($source->file as $file) {
                        $storedPaths[] = $this->copyFile($file, $copy);
                    }

                    $copied->push($copy);
                }
            });
        } catch (\Throwable $e) {
            foreach (array_filter($storedPaths) as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        foreach ($copied as $copy) {
            $this->notifyLearners($copy, $source->subject?->name ?? '', $sessionYearId);
        }

        return $copied;
    }

    /** Null when the class does not use terms in the year; otherwise the terms the actor may set work in. */
    private function termsFor(ClassSection $section, int $subjectId, int $sessionYearId, ?int $teacherId): ?Collection
    {
        $class = ClassSchool::withSessionConfigFor($sessionYearId)->whereKey($section->class_id)->first();
        if (! $class || ! $class->getIncludeTermsForSession($sessionYearId)) {
            return null;
        }

        return Term::query()
            ->where('session_year_id', $sessionYearId)
            // Assignment::scopeAssignmentTeachers() lets a teacher see term-class homework only
            // through a subject_teachers row for that exact term, so only those terms are offered
            // (a copy in any other term would vanish from the teacher's own list).
            ->when($teacherId !== null, fn ($q) => $q->whereIn('id', SubjectTeacher::query()
                ->where('teacher_id', $teacherId)
                ->where('class_section_id', $section->id)
                ->where('subject_id', $subjectId)
                ->where('session_year_id', $sessionYearId)
                ->whereNotNull('term_id')
                ->select('term_id')))
            ->orderBy('id')
            ->get();
    }

    /** The actor's teacher id when they act as a teacher (and so only for their own classes). */
    private function teacherId(User $actor): ?int
    {
        if (! $actor->hasRole('Teacher')) {
            return null;
        }

        // A Teacher-role user without a teacher record teaches nothing.
        return (int) ($actor->teacher?->id ?? 0);
    }

    private function copyFile(File $file, Assignment $copy): ?string
    {
        $path = $file->getRawOriginal('file_url');
        $newPath = null;

        // Uploaded files (and videos) are stored per assignment, because deleting an
        // assignment deletes its stored files; links are copied as they are.
        if (in_array((int) $file->type, [1, 3], true) && $path && ! preg_match('#^https?://#i', $path)) {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $newPath = 'assignment/'.Str::random(40).($extension !== '' ? '.'.$extension : '');
            Storage::disk('public')->copy($path, $newPath);
        }

        $clone = new File;
        $clone->file_name = $file->file_name;
        $clone->type = $file->type;
        $clone->file_url = $newPath ?? $path;
        $clone->file_thumbnail = $file->getRawOriginal('file_thumbnail');
        $clone->modal()->associate($copy);
        $clone->save();

        return $newPath;
    }

    /** The same notice as setting new homework: the class's learners (only those taking an elective). */
    private function notifyLearners(Assignment $assignment, string $subjectName, int $sessionYearId): void
    {
        $classId = ClassSection::query()->whereKey($assignment->class_section_id)->value('class_id');
        $classSubject = ClassSubject::query()
            ->where('subject_id', $assignment->subject_id)
            ->where('class_id', $classId)
            ->where('session_year_id', $sessionYearId)
            ->first();

        $learners = Students::query()->where('class_section_id', $assignment->class_section_id);
        if ($classSubject?->type === 'Elective') {
            $learners->whereIn('id', StudentSubject::query()
                ->where('subject_id', $assignment->subject_id)
                ->where('session_year_id', $sessionYearId)
                ->select('student_id'));
        }
        $userIds = $learners->pluck('user_id')->filter()->values()->all();

        $notification = new Notification;
        $notification->send_to = 3;
        $notification->session_year_id = $sessionYearId;
        $notification->title = 'New assignment added in '.$subjectName;
        $notification->message = $assignment->name;
        $notification->type = 'assignment';
        $notification->date = Carbon::now();
        $notification->is_custom = 0;
        $notification->save();

        app(Notifier::class)->assign($notification, $userIds);
    }
}
