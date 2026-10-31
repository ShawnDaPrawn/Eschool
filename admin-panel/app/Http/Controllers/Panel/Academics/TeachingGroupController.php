<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ClassGradeRequest;
use App\Http\Requests\Academics\FillTeachingGroupRequest;
use App\Http\Requests\Academics\TeachingGroupRequest;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\AcademicReferences;
use App\Services\Academics\TeachingGroupQueries;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeachingGroupController extends Controller
{
    public function index(TeachingGroupRequest $request, TeachingGroupQueries $queries): View
    {
        $manage = app(AcademicAuthority::class)->manages($request->user());

        return view('academics.groups-index', ['groups' => $queries->groups($request->user(), $request->validated()), 'manage' => $manage,
            'classes' => $manage ? ClassSchool::query()->with('gradeLevel')->orderBy('name')->get() : collect(),
            'grades' => $manage ? GradeLevel::query()->orderBy('sort_order')->pluck('name', 'id')->all() : []]);
    }

    public function edit(TeachingGroupRequest $request, ?int $group = null): View
    {
        app(AcademicAuthority::class)->manage($request->user());

        return view('academics.group-edit', ['group' => $group === null ? null : TeachingGroup::query()->findOrFail($group)] + $this->choices($request->user()));
    }

    public function show(TeachingGroupRequest $request, TeachingGroupQueries $queries, int $group): View
    {
        $record = app(AcademicAuthority::class)->group($request->user(), $group)->load('subject', 'gradeLevel', 'year', 'homeroom');
        $manage = app(AcademicAuthority::class)->manages($request->user());

        return view('academics.group-show', ['group' => $record, 'manage' => $manage,
            'memberships' => $queries->rows($request->user(), 'memberships', ['page_name' => 'memberships_page'], $group)->withQueryString(),
            'teachers' => $queries->rows($request->user(), 'teachers', ['page_name' => 'teachers_page'], $group)->withQueryString(),
            'today' => app(AcademicAuthority::class)->today()] + ($manage ? $this->choices($request->user()) : []));
    }

    public function save(TeachingGroupRequest $request, TeachingGroups $service, ?int $group = null): RedirectResponse
    {
        $record = $service->save($request->user(), $request->validated(), $group);

        return redirect()->route('teaching-groups.show', $record->id)->with('success', __('Teaching group saved'));
    }

    public function allocations(TeachingGroupRequest $request, TeachingGroupQueries $queries): View
    {
        app(AcademicAuthority::class)->manage($request->user());

        return view('academics.allocations', ['homerooms' => $queries->rows($request->user(), 'homerooms', ['page_name' => 'homerooms_page'])->withQueryString(),
            'leadership' => $queries->rows($request->user(), 'leadership', ['page_name' => 'leadership_page'])->withQueryString(),
            'today' => app(AcademicAuthority::class)->today()] + $this->choices($request->user()));
    }

    public function row(TeachingGroupRequest $request, TeachingGroups $service): RedirectResponse
    {
        $group = $request->route('group') === null ? null : (int) $request->route('group');
        $id = $request->route('entry') === null ? null : (int) $request->route('entry');
        $service->dated($request->user(), (string) $request->route('kind'), $request->validated(), $group, $id);

        return ($group === null ? redirect()->route('academic-allocations.index') : redirect()->route('teaching-groups.show', $group))->with('success', __('Dated assignment saved'));
    }

    public function fill(FillTeachingGroupRequest $request, TeachingGroups $service, int $group): RedirectResponse
    {
        $data = $request->validated();
        $result = $service->fillFromHomeroom($request->user(), $group, $data['starts_on'], $data['mark_rule']);

        return redirect()->route('teaching-groups.show', $group)->with('success', __(':added learners added from the homeroom; :skipped were already members.', $result));
    }

    public function map(ClassGradeRequest $request, AcademicReferences $references, int $class): RedirectResponse
    {
        $references->map($request->user(), $class, $request->validated());

        return redirect()->route('teaching-groups.index')->with('success', __('Class grade mapping saved'));
    }

    private function choices(User $actor): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $name = fn ($person) => trim($person->first_name.' '.$person->last_name);
        $staff = SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Student', 'Parent'])->select('user_id');

        return ['subjects' => Subject::query()->orderBy('name')->pluck('name', 'id')->all(),
            'grades' => GradeLevel::query()->orderBy('sort_order')->pluck('name', 'id')->all(),
            'years' => SessionYear::query()->orderByDesc('start_date')->get(),
            'rooms' => ClassSection::query()->with('class', 'section')->get()->mapWithKeys(fn ($r) => [$r->id => $r->class->name.' '.$r->section->name])->all(),
            'teacherChoices' => Teacher::query()->with('user')->whereIn('user_id', $staff)->whereHas('user', fn ($q) => $q->where('status', 1))->get()->mapWithKeys(fn ($t) => [$t->id => $name($t->user)])->all(),
            // Current learners only: leavers keep an active account (Action Plan 1.21).
            'learners' => Students::query()->with('user')->whereHas('user', fn ($q) => $q->where('status', 1))
                ->whereIn('user_id', SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')->select('user_id'))->get()->mapWithKeys(fn ($s) => [$s->id => $name($s->user)])->all(),
            'staff' => User::query()->where('status', 1)->whereIn('id', $staff)->orderBy('first_name')->get()->mapWithKeys(fn ($u) => [$u->id => $name($u)])->all()];
    }
}
