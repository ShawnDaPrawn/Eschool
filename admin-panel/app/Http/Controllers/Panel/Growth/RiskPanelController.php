<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\RiskPanelRequest;
use App\Models\Growth\Intervention;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\Interventions;
use App\Services\Growth\RiskAuthority;
use App\Services\Growth\RiskOperations;
use App\Services\Growth\RiskQueries;
use App\Services\Growth\RiskSettingsService;
use App\Services\Growth\RiskViews;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RiskPanelController extends Controller
{
    private function id(mixed $v, int $default = 0): int
    {
        return (is_int($v) || (is_string($v) && ctype_digit($v))) && (int) $v > 0 ? (int) $v : $default;
    }

    private function model(RiskPanelRequest $r, array $messages = [], bool $conflict = false): array
    {
        $q = app(RiskQueries::class);
        $config = $q->configuration($r->user());
        $a = app(RiskAuthority::class);
        $mode = $r->input('mode', 'queue');
        $mode = is_string($mode) && in_array($mode, ['queue', 'signals', 'classroom', 'assignments', 'settings'], true) ? $mode : 'queue';
        $year = $this->id($r->input('session_year_id'), $config['session_year_id']);
        $a->year($year);
        $search = is_string($r->input('search')) ? mb_substr($r->input('search'), 0, 100) : null;
        $page = $this->id($r->input('page'), 1);
        $learner = $this->id($r->route('learner') ?? $r->input('learner_id'));
        $group = $this->id($r->input('group_id'));
        $id = $this->id($r->route('intervention') ?? $r->input('intervention'));
        $rows = [];
        $people = ['learners' => [], 'page' => 1, 'last_page' => 1];
        $assignments = [];
        $staff = ['staff' => [], 'page' => 1, 'last_page' => 1];
        $owners = ['owners' => [], 'page' => 1, 'last_page' => 1];
        $record = null;
        $history = [];
        $handoff = null;
        if ($mode === 'signals') {
            abort_unless($config['can_read_signals'], 403);
            $rows = app(RiskViews::class)->list($r->user(), $year, $page);
        } elseif ($mode === 'classroom') {
            if ($group > 0) {
                $rows = app(RiskViews::class)->classroom($r->user(), $group);
            }
        } elseif ($mode === 'assignments') {
            abort_unless($config['can_manage'] || $config['grade_ids'] !== [], 403);
            $people = $q->learners($r->user(), $year, $page, $search);
            if ($learner > 0) {
                $assignments = $q->assignments($r->user(), $learner, $year)['assignments'];
                $staff = $q->professionals($r->user(), $learner, $year, $this->id($r->input('staff_page'), 1), $search);
            }
            if ($id > 0) {
                $meta = Intervention::select(Interventions::META)->findOrFail($id);
                $a->administer($r->user(), (int) $meta->learner_id, (int) $meta->session_year_id);
                $learner = (int) $meta->learner_id;
                $year = (int) $meta->session_year_id;
                $handoff = app(Interventions::class)->metadata($meta);
                $owners = $q->owners($r->user(), $learner, $year, $id, $this->id($r->input('owner_page'), 1), $search);
            }
        } elseif ($mode === 'settings') {
            $a->principal($r->user());
        } else {
            $rows = app(RiskViews::class)->interventions($r->user(), $year, $page);
            $people = $q->learners($r->user(), $year, $this->id($r->input('learner_page'), 1), $search);
            if ($id > 0) {
                $record = app(Interventions::class)->read($r->user(), $id);
                $learner = $record['learner_id'];
                $year = $record['session_year_id'];
                $history = app(Interventions::class)->history($r->user(), $id);
            } elseif ($learner > 0 && $year === $config['session_year_id']) {
                $owners = $q->owners($r->user(), $learner, $year, null, $this->id($r->input('owner_page'), 1), $search);
            }
        }
        $draft = [];
        foreach (['action' => 20000, 'outcome' => 20000, 'reason' => 1000] as $field => $limit) {
            $v = $r->input($field);
            if (! $r->isMethodSafe() && is_string($v)) {
                $draft[$field] = mb_substr($v, 0, $limit);
            }
        }
        $operationId = ! $conflict && is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid();
        $selection = array_filter(['mode' => $mode, 'session_year_id' => $year, 'learner_id' => $learner ?: null, 'group_id' => $group ?: null], fn ($v) => $v !== null);
        $canWrite = ! app(SessionYearContext::class)->isReadOnly() && app(ModuleGate::class)->writable('early_warning');
        $startedOn = is_string($r->input('started_on')) ? mb_substr($r->input('started_on'), 0, 10) : app(AcademicAuthority::class)->today();
        $reviewOn = is_string($r->input('review_on')) ? mb_substr($r->input('review_on'), 0, 10) : ($record['review_on'] ?? app(AcademicAuthority::class)->today());

        return compact('config', 'mode', 'year', 'page', 'learner', 'group', 'id', 'rows', 'people', 'assignments', 'staff', 'owners', 'record', 'history', 'handoff', 'draft', 'operationId', 'selection', 'messages', 'conflict', 'canWrite', 'startedOn', 'reviewOn') +
            ['actorId' => (int) $r->user()->id, 'schoolId' => app(CurrentSchool::class)->id()];
    }

    public function index(RiskPanelRequest $r): Response
    {
        return response()->view('growth.risk-interventions', $this->model($r))->header('Cache-Control', 'private, no-store');
    }

    public function failure(RiskPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('growth.risk-interventions', $this->model($r, $messages, $status === 409), $status)->header('Cache-Control', 'private, no-store');
    }

    public function write(RiskPanelRequest $r): Response|RedirectResponse
    {
        $d = $r->validated();
        $op = $r->operation();
        $learner = (int) $r->route('learner');
        $id = (int) $r->route('intervention');
        $year = $r->year();
        $a = app(RiskAuthority::class);
        $s = app(Interventions::class);
        $data = array_diff_key($d, array_flip(['mode', 'group_id', 'page', 'professional_id', 'expected_account_id', 'expected_school_id', 'review_required', 'confirm_current']));
        $data += ['learner_id' => $learner, 'intervention_id' => $id, 'professional_id' => (int) ($d['professional_id'] ?? 0)];
        $authority = match ($op) {
            'create' => function () use ($r, $a, $learner, $year, $d) {
                $a->year($year, true);
                $a->caseActor($r->user(), $learner, $year, $a->currentGrade($learner, $year), true);
                if ((int) $d['owner_id'] !== (int) $r->user()->id) {
                    $a->administer($r->user(), $learner, $year);
                }
            },
            'update' => fn () => $s->authorized($r->user(), $id, true),
            'assign','revoke' => fn () => $a->administer($r->user(), $learner, $year),
            'configure' => fn () => $a->principal($r->user(), true),
            'transfer' => function () use ($r, $a, $id) {
                $meta = Intervention::select(Interventions::META)->findOrFail($id);
                $a->administer($r->user(), (int) $meta->learner_id, (int) $meta->session_year_id);
            },
        };
        try {
            $result = app(RiskOperations::class)->once($r->user(), 'panel.risk.'.$op, $data, $authority, fn () => match ($op) {
                'create' => $s->create($r->user(), $learner, $year, $d),'update' => $s->update($r->user(), $id, array_diff_key($d, ['session_year_id' => true])),
                'assign' => $s->assign($r->user(), $learner, $year, (int) $d['professional_id'], $d),
                'revoke' => $s->revoke($r->user(), $learner, $year, (int) $d['professional_id'], (int) $d['expected_revision']),
                'configure' => app(RiskSettingsService::class)->configure($r->user(), $d),
                'transfer' => $s->transferOwner($r->user(), $id, (int) $d['owner_id'], (int) $d['expected_revision'], $d['reason'])
            },
                $op === 'create' ? fn (array $receipt) => $a->caseActor(User::findOrFail($receipt['owner_id']), $learner, $year, (int) $receipt['grade_level_id'], true, $receipt['owner_authority']) : null);
        } catch (ValidationException $e) {
            return $this->failure($r, $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, [$e->getMessage()], $e->getStatusCode());
        }
        $selection = ['mode' => in_array($op, ['assign', 'revoke', 'transfer'], true) ? 'assignments' : ($op === 'configure' ? 'settings' : 'queue'), 'session_year_id' => $year];
        if (($result['learner_id'] ?? $learner) > 0) {
            $selection['learner_id'] = $result['learner_id'] ?? $learner;
        }if (in_array($op, ['create', 'update'], true)) {
            $selection['intervention'] = $result['id'];
        }

        return redirect()->route('risk-interventions.index', $selection)->with('success', __('Intervention change saved'))->header('Cache-Control', 'private, no-store');
    }
}
