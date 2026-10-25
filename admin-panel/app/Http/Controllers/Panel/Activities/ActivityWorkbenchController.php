<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\ActivityPanelRequest;
use App\Models\Activities\ActivityGroup;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\CommunityAuthority;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ActivityWorkbenchController extends Controller
{
    public function index(ActivityPanelRequest $r): Response
    {
        return response()->view('activities.workbench', $this->model($r));
    }

    private function model(ActivityPanelRequest $r, array $extra = []): array
    {
        // Resolve authority before loading any group, name, allocation or draft.
        app(CommunityAuthority::class)->manage($r->user());
        $service = app(ActivityGroups::class);
        $id = $r->route('group') ?? $r->query('group_id');
        $detail = $id === null ? null : $service->detail($r->user(), (int) $id);
        $years = SessionYear::orderByDesc('start_date')->get();
        $yearId = $detail['group']['session_year_id'] ?? $r->input('session_year_id') ?? getSettings('session_year')['session_year'] ?? null;
        $year = $yearId === null ? $years->first() : $years->firstWhere('id', (int) $yearId);
        abort_if($year === null, 422, __('Set up a school year first.'));
        $groups = ActivityGroup::where('session_year_id', $year->id)->orderBy('name')->limit(1001)->get();
        abort_if($groups->count() > 1000, 422, __('Narrow the activity group list before continuing.'));
        $currentYear = app(SessionYearContext::class)->currentId();
        $readOnly = $currentYear !== null && $currentYear !== (int) $year->id;
        $roster = $detail === null ? ['memberships' => [], 'staff' => [], 'attendance' => []] : $service->roster($r->user(), (int) $id);
        $staffIds = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereIn('role', ['Teacher', 'Facilitator'])->select('user_id');
        $staffNames = User::whereIn('id', $staffIds)->where('status', 1)->orderBy('first_name')->get()->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all();
        $learnerNames = Students::with('user')->whereIn('id', Enrolment::where('session_year_id', $year->id)->select('learner_id'))->get()->filter(fn ($s) => $s->user?->status === 1)->mapWithKeys(fn ($s) => [(int) $s->id => trim($s->user->first_name.' '.$s->user->last_name)])->sort()->all();
        $edit = $r->query('edit') ?? ($r->route('record') === null ? null : str_replace('-save', '', $r->action()));
        $record = null;
        if ($edit !== null && $detail !== null) {
            $rows = match ($edit) {
                'membership' => $roster['memberships'], 'staff' => $roster['staff'], 'session' => $detail['sessions'], 'material' => $detail['materials'], default => []
            };
            $record = collect($rows)->firstWhere('id', (int) ($r->route('record') ?? $r->query('record_id')));
            abort_if($record === null, 404);
        }
        $sessionId = $r->route('session') ?? $r->query('session_id');
        $session = $sessionId === null || $detail === null ? null : collect($detail['sessions'])->firstWhere('id', (int) $sessionId);
        abort_if($sessionId !== null && $session === null, 404);
        $attendance = [];
        if ($session !== null) {
            $saved = collect($roster['attendance'])->where('activity_session_id', $session['id'])->keyBy('learner_id');
            foreach ($roster['memberships'] as $member) {
                if (($member['active'] && $member['starts_on'] <= $session['on_date'] && $member['ends_on'] >= $session['on_date']) || isset($saved[$member['learner_id']])) {
                    $attendance[$member['learner_id']] = ['learner_id' => $member['learner_id'], 'learner_name' => $member['learner_name'], 'status' => $saved[$member['learner_id']]['status'] ?? '', 'revision' => $saved[$member['learner_id']]['revision'] ?? 0];
                }
            }
        }

        return compact('readOnly', 'years', 'year', 'groups', 'detail', 'roster', 'staffNames', 'learnerNames', 'edit', 'record', 'session', 'attendance') + $extra + ['restored' => null, 'failedAction' => null, 'conflict' => false];
    }

    public function invalid(ActivityPanelRequest $r, array $errors, int $status): Response
    {
        $data = array_filter($r->only(array_keys(ActivityGroups::rules($r->action()))), fn ($v, $k) => is_scalar($v) || $v === null || $k === 'entries', ARRAY_FILTER_USE_BOTH);
        if (isset($data['entries'])) {
            $data['entries'] = is_array($data['entries']) ? array_map(fn ($row) => is_array($row) ? array_filter($row, fn ($v) => is_scalar($v) || $v === null) : [], array_slice($data['entries'], 0, 500)) : [];
        }
        $bag = new ViewErrorBag;
        $bag->put('default', new MessageBag($errors));

        // Failed submissions stay in this response, never a shared session or cache.
        return response()->view('activities.workbench', $this->model($r, ['restored' => $data, 'failedAction' => $r->action(), 'conflict' => $status === 409, 'errors' => $bag]), $status);
    }

    public function write(ActivityPanelRequest $r): BaseResponse
    {
        $s = app(ActivityGroups::class);
        $group = $r->route('group') === null ? null : (int) $r->route('group');
        $record = $r->route('allocation') ?? $r->route('record');
        $record = $record === null ? null : (int) $record;
        try {
            $result = match ($r->action()) {
                'create', 'save' => $s->save($r->user(), $group, $r->validated()),
                'membership-save', 'staff-save' => $s->allocation($r->user(), (int) $group, $record, $r->action() === 'staff-save', $r->validated()),
                'session-save' => $s->session($r->user(), (int) $group, $record, $r->validated()),
                'material-save' => $s->material($r->user(), (int) $group, $record, $r->validated()),
                'attendance-save' => $s->attendance($r->user(), (int) $r->route('session'), $r->validated()),
            };
        } catch (ValidationException $e) {
            return $this->invalid($r, $e->errors(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->invalid($r, ['activity' => [$e->getMessage()]], $e->getStatusCode());
        }

        return redirect()->route('activity-groups.index', ['group_id' => $group ?? $result['id']])->with('success', __('Activity saved'));
    }
}
