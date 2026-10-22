<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Parents;
use App\Models\Staff;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The panel's global search: learners, parents, teachers, staff, grades and
 * homerooms, subjects, and the panel's pages and settings (from the navigation
 * registry). Each group appears only for people who may open what it finds, and
 * learners follow the year the panel shows. A teacher without the right to add
 * learners finds only the learners of their own homerooms, as on the learner
 * list. Results are plain data; the page and the header box escape them.
 */
class GlobalSearch
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 100;

    public function __construct(
        private readonly Navigation $navigation,
        private readonly SessionYearContext $years,
    ) {}

    /**
     * @return list<array{key:string, label:string, items:list<array{type:string, title:string, subtitle:?string, url:string}>}>
     */
    public function search(User $user, string $query, int $perGroup): array
    {
        $query = trim(Str::limit(preg_replace('/\s+/', ' ', $query) ?? '', self::MAX_LENGTH, ''));
        if (mb_strlen($query) < self::MIN_LENGTH) {
            return [];
        }
        $like = '%'.addcslashes($query, '%_\\').'%';

        $groups = [
            'pages' => fn () => $this->pages($user, $query, $perGroup),
            'learners' => fn () => $this->learners($user, $like, $perGroup),
            'parents' => fn () => $this->parents($user, $like, $perGroup),
            'teachers' => fn () => $this->teachers($user, $like, $perGroup),
            'staff' => fn () => $this->staff($user, $like, $perGroup),
            'homerooms' => fn () => $this->homerooms($user, $like, $perGroup),
            'subjects' => fn () => $this->subjects($user, $like, $perGroup),
        ];

        $results = [];
        foreach ($groups as $key => $find) {
            $items = $find();
            if ($items !== []) {
                $results[] = ['key' => $key, 'label' => __('search_group_'.$key), 'items' => $items];
            }
        }

        return $results;
    }

    /** Panel pages and settings the person can open, matched on their words and keywords. */
    private function pages(User $user, string $query, int $limit): array
    {
        $words = array_filter(explode(' ', mb_strtolower($query)));
        $matches = [];
        foreach ($this->navigation->items($user) as $item) {
            $haystack = mb_strtolower(implode(' ', array_merge(
                [$item['label'], $item['group_label'], $item['section_label']],
                $item['keywords']
            )));
            $title = mb_strtolower($item['label']);
            if (! collect($words)->every(fn ($word) => str_contains($haystack, $word))) {
                continue;
            }
            // Pages whose own name matches come first.
            $score = str_starts_with($title, mb_strtolower($query)) ? 0 : (str_contains($title, mb_strtolower($query)) ? 1 : 2);
            $matches[] = [$score, [
                'type' => 'page',
                'title' => $item['label'],
                'subtitle' => $item['section_label'].' / '.$item['group_label'],
                'url' => $item['url'],
            ]];
        }
        usort($matches, fn ($a, $b) => $a[0] <=> $b[0]);

        return array_slice(array_column($matches, 1), 0, $limit);
    }

    private function learners(User $user, string $like, int $limit): array
    {
        if (! $user->canAny(['student-list', 'student-report'])) {
            return [];
        }
        $yearId = $this->years->selectedId();
        $restrictedTeacher = $user->hasRole('Teacher') && ! $user->can('student-create');

        $rows = StudentSessions::query()
            ->where('session_year_id', $yearId)
            ->where('status', 1)
            ->whereHas('student.user', fn (Builder $q) => $q->where('status', 1))
            ->whereHas('student', function (Builder $student) use ($like) {
                $student->where(fn (Builder $q) => $q
                    ->where('admission_no', 'like', $like)
                    ->orWhereHas('user', fn (Builder $u) => $this->matchName($u, $like)));
            })
            ->when($restrictedTeacher, function (Builder $q) use ($user, $yearId) {
                $q->whereIn('class_section_id', ClassTeacher::where('class_teacher_id', $user->teacher?->id)
                    ->where('session_year_id', $yearId)->select('class_section_id'));
            })
            ->with(['student.user', 'class_section.class', 'class_section.section'])
            ->limit($limit)
            ->get();

        $canReport = $user->can('student-report');

        return $rows->filter(fn ($row) => $row->student && $row->student->user)->map(fn ($row) => [
            'type' => 'learner',
            'title' => $row->student->user->full_name,
            'subtitle' => trim(($row->class_section?->full_name ?? '').' · '.$row->student->admission_no, ' ·'),
            'url' => $canReport
                ? route('student.report', $row->student->id)
                : route('students.index', ['search' => $row->student->admission_no]),
        ])->values()->all();
    }

    private function parents(User $user, string $like, int $limit): array
    {
        if (! $user->can('parents-list')) {
            return [];
        }

        return Parents::query()
            ->where(fn (Builder $q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                ->orWhereRaw("concat(first_name, ' ', last_name) like ?", [$like])
                ->orWhere('email', 'like', $like)->orWhere('mobile', 'like', $like))
            ->limit($limit)
            ->get()
            ->map(fn (Parents $parent) => [
                'type' => 'parent',
                'title' => trim($parent->first_name.' '.$parent->last_name),
                'subtitle' => $parent->email,
                'url' => route('parents.index', ['search' => $parent->email ?: $parent->first_name]),
            ])->all();
    }

    private function teachers(User $user, string $like, int $limit): array
    {
        if (! $user->canAny(['teacher-list', 'teacher-create'])) {
            return [];
        }
        $route = $user->can('teacher-list') ? 'teacher.details' : 'teachers.index';

        return Teacher::query()
            ->whereHas('user', fn (Builder $u) => $this->matchName($u, $like))
            ->with('user')
            ->limit($limit)
            ->get()
            ->map(fn (Teacher $teacher) => [
                'type' => 'teacher',
                'title' => $teacher->user->full_name,
                'subtitle' => $teacher->user->email,
                'url' => route($route, ['search' => $teacher->user->email]),
            ])->all();
    }

    private function staff(User $user, string $like, int $limit): array
    {
        if (! $user->can('staff-list')) {
            return [];
        }

        return Staff::query()
            ->whereHas('user', fn (Builder $u) => $this->matchName($u, $like)->whereDoesntHave('teacher'))
            ->with('user.roles')
            ->limit($limit)
            ->get()
            ->map(fn (Staff $staff) => [
                'type' => 'staff',
                'title' => $staff->user->full_name,
                'subtitle' => $staff->user->roles->pluck('name')->implode(', ') ?: $staff->user->email,
                'url' => route('staff.index', ['search' => $staff->user->email]),
            ])->all();
    }

    private function homerooms(User $user, string $like, int $limit): array
    {
        if (! $user->canAny(['class-list', 'class-create'])) {
            return [];
        }

        return ClassSection::query()
            ->where(fn (Builder $q) => $q
                ->whereHas('class', fn (Builder $c) => $c->where('name', 'like', $like))
                ->orWhereHas('section', fn (Builder $s) => $s->where('name', 'like', $like))
                ->orWhereRaw('exists (select 1 from classes c join sections s on s.id = class_sections.section_id where c.id = class_sections.class_id and concat(c.name, \' \', s.name) like ?)', [$like]))
            ->with(['class.medium', 'section'])
            ->limit($limit)
            ->get()
            ->filter(fn (ClassSection $homeroom) => $homeroom->class && $homeroom->section)
            ->map(fn (ClassSection $homeroom) => [
                'type' => 'homeroom',
                'title' => trim($homeroom->class->name.' '.$homeroom->section->name),
                'subtitle' => $homeroom->class->medium?->name,
                'url' => route('class.index', ['search' => $homeroom->class->name]),
            ])->values()->all();
    }

    private function subjects(User $user, string $like, int $limit): array
    {
        if (! $user->canAny(['subject-list', 'subject-create'])) {
            return [];
        }

        return Subject::query()
            ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like))
            ->with('medium')
            ->limit($limit)
            ->get()
            ->map(fn (Subject $subject) => [
                'type' => 'subject',
                'title' => $subject->name,
                'subtitle' => trim(($subject->code ?? '').' · '.($subject->medium?->name ?? ''), ' ·'),
                'url' => route('subject.index', ['search' => $subject->name]),
            ])->all();
    }

    private function matchName(Builder $users, string $like): Builder
    {
        return $users->where(fn (Builder $q) => $q
            ->where('first_name', 'like', $like)
            ->orWhere('last_name', 'like', $like)
            ->orWhereRaw("concat(first_name, ' ', last_name) like ?", [$like])
            ->orWhere('email', 'like', $like));
    }
}
