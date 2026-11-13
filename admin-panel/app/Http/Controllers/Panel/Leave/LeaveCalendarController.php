<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Leave;

use App\Http\Controllers\Controller;
use App\Models\ClassTeacher;
use App\Models\Leave;
use App\Models\Leave\FacilitatorAbsence;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Leave\FacilitatorLeave;
use App\Services\Panel\SessionYearContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class LeaveCalendarController extends Controller
{
    private function legacyQuery(User $actor, int $year)
    {
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        $staff = $policy->permission($fresh, 'leave-approve');
        $history = $policy->permission($fresh, 'staff-leave-list');
        $learners = $policy->permission($fresh, 'student-leave-approve');
        $own = $policy->permission($fresh, 'leave-create');
        $teacherAllocations = collect();
        $learnerIds = Students::whereHas('studentSessions', fn ($q) => $q->where('session_year_id', $year));
        if ($teacher = Teacher::where('user_id', $fresh->id)->first()) {
            $teacherAllocations = ClassTeacher::with('term')->where('class_teacher_id', $teacher->id)->where('session_year_id', $year)->get();
            $classes = $teacherAllocations->pluck('class_section_id');
            $learnerIds->whereHas('studentSessions', fn ($q) => $q->where('session_year_id', $year)->whereIn('class_section_id', $classes));
        }
        $studentUsers = Students::select('user_id');

        return Leave::where('session_year_id', $year)->where(function ($scope) use ($fresh, $staff, $history, $learners, $own, $learnerIds, $studentUsers, $teacherAllocations, $year) {
            $scope->whereRaw('1=0');
            if ($own) {
                $scope->orWhere('user_id', $fresh->id);
            }
            if ($staff || $history) {
                $scope->orWhere(fn ($q) => $q->whereNotIn('user_id', $studentUsers)->when(! $staff, fn ($q) => $q->where('status', 1)));
            }
            if ($learners) {
                if ($teacherAllocations->isNotEmpty()) {
                    foreach ($teacherAllocations as $allocation) {
                        $scope->orWhere(function ($q) use ($allocation, $year) {
                            $q->whereIn('user_id', Students::whereHas('studentSessions', fn ($sessions) => $sessions->where('session_year_id', $year)->where('class_section_id', $allocation->class_section_id))->select('user_id'));
                            if ($allocation->term) {
                                $q->whereDate('from_date', '>=', $allocation->term->start_date->toDateString())->whereDate('from_date', '<=', $allocation->term->end_date->toDateString());
                            }
                        });
                    }
                } else {
                    $scope->orWhereIn('user_id', $learnerIds->select('user_id'));
                }
            }
        });
    }

    public function index(Request $request)
    {
        $data = $request->validate(['week' => ['sometimes', 'date_format:Y-m-d'], 'person_type' => ['sometimes', 'in:all,staff,learners,facilitators'], 'status' => ['sometimes', 'in:all,pending,approved,rejected']]);
        $actor = app(ProfilePolicy::class)->actor($request->user());
        $service = app(FacilitatorLeave::class);
        $permissions = ['leave-create', 'leave-approve', 'staff-leave-list', 'student-leave-approve'];
        abort_unless(collect($permissions)->contains(fn ($permission) => app(ProfilePolicy::class)->permission($actor, $permission)) || $service->available($actor), 403);
        $year = SessionYear::findOrFail(app(SessionYearContext::class)->selectedId());
        $from = CarbonImmutable::parse($data['week'] ?? now()->toDateString())->startOfWeek();
        $to = $from->addDays(6);
        $type = $data['person_type'] ?? 'all';
        $status = $data['status'] ?? 'all';
        $statuses = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
        $rows = [];
        if ($type !== 'facilitators') {
            $query = $this->legacyQuery($actor, (int) $year->id)->whereDate('from_date', '<=', $to->toDateString())->whereDate('to_date', '>=', $from->toDateString());
            if ($status !== 'all') {
                $query->where('status', $statuses[$status]);
            }
            if ($type === 'learners') {
                $query->whereIn('user_id', Students::select('user_id'));
            }
            if ($type === 'staff') {
                $query->whereNotIn('user_id', Students::select('user_id'));
            }
            $legacy = $query->with(['user', 'leave_detail'])->orderBy('from_date')->limit(501)->get();
            abort_if($legacy->count() > 500, 422, 'Choose a narrower person type for this week.');
            $studentIds = Students::whereIn('user_id', $legacy->pluck('user_id'))->pluck('user_id')->all();
            foreach ($legacy as $row) {
                $rows[] = ['id' => $row->id, 'name' => $row->user?->full_name ?? __('Former person'), 'kind' => in_array($row->user_id, $studentIds) ? 'Learner' : 'Staff', 'from' => CarbonImmutable::parse($row->from_date)->toDateString(), 'to' => CarbonImmutable::parse($row->to_date)->toDateString(), 'status' => array_search((int) $row->status, $statuses, true) ?: 'pending', 'url' => route('leave-calendar.show', $row->id), 'portions' => $row->leave_detail->map(fn ($day) => CarbonImmutable::parse($day->date)->toDateString().' · '.$day->type)->all()];
            }
        }
        if (in_array($type, ['all', 'facilitators'], true) && $service->available($actor)) {
            $facilitators = FacilitatorAbsence::where('session_year_id', $year->id)->when(! $service->canManage($actor), fn ($q) => $q->where('user_id', $actor->id))->whereDate('from_date', '<=', $to)->whereDate('to_date', '>=', $from)->when($status !== 'all', fn ($q) => $q->where('status', $status))->limit(501)->get();
            abort_if($facilitators->count() > 500, 422, 'Choose a narrower person type for this week.');
            $names = User::whereIn('id', $facilitators->pluck('user_id'))->get()->keyBy('id');
            foreach ($facilitators as $row) {
                $rows[] = ['id' => $row->id, 'name' => $names->get($row->user_id)?->full_name ?? __('Former facilitator'), 'kind' => 'Facilitator', 'from' => $row->from_date->toDateString(), 'to' => $row->to_date->toDateString(), 'status' => $row->status, 'url' => route('facilitator-leave.show', $row->id), 'portions' => collect($row->portions)->map(fn ($portion, $date) => $date.' · '.str_replace('_', ' ', $portion))->values()->all()];
            }
        }
        usort($rows, fn ($a, $b) => [$a['from'], $a['name']] <=> [$b['from'], $b['name']]);

        return response()->view('leave.calendar', compact('rows', 'year', 'from', 'to', 'type', 'status'))->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, int $leave)
    {
        $year = (int) app(SessionYearContext::class)->selectedId();
        $record = $this->legacyQuery($request->user(), $year)->with(['user', 'leave_detail', 'file'])->findOrFail($leave);

        return response()->view('leave.calendar-detail', compact('record'))->header('Cache-Control', 'no-store, private');
    }
}
