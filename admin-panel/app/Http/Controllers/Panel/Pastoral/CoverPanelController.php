<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Pastoral;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\CoverPanelRequest;
use App\Models\Pastoral\CoverAssignment;
use App\Models\SessionYear;
use App\Services\Academics\AcademicAuthority;
use App\Services\Panel\SessionYearContext;
use App\Services\Pastoral\CoverQueries;
use App\Services\Pastoral\ReliefCover;
use App\Services\Platform\Modules\ModuleGate;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CoverPanelController extends Controller
{
    private function model(CoverPanelRequest $r, array $messages = []): array
    {
        $today = app(AcademicAuthority::class)->today();
        $year = SessionYear::find((is_scalar($r->input('session_year_id')) ? (int) $r->input('session_year_id') : 0)) ?? SessionYear::findOrFail(app(SessionYearContext::class)->selectedId());
        $from = is_string($r->input('from')) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $r->input('from')) ? (string) $r->input('from') : $today;
        try {
            $from = CarbonImmutable::createFromFormat('!Y-m-d', $from)->toDateString();
        } catch (\Throwable) {
            $from = $today;
        }
        $to = is_string($r->input('to')) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $r->input('to')) ? (string) $r->input('to') : CarbonImmutable::parse($from)->addDays(30)->toDateString();
        try {
            $to = CarbonImmutable::createFromFormat('!Y-m-d', $to)->toDateString();
        } catch (\Throwable) {
            $to = $from;
        }
        if ($to < $from || CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 30) {
            $to = CarbonImmutable::parse($from)->addDays(30)->toDateString();
        }
        $q = app(CoverQueries::class);
        $absenceId = $r->route('absence');
        if ($absenceId === null && $r->route('occurrence') !== null) {
            $absenceId = CoverAssignment::findOrFail((int) $r->route('occurrence'))->cover_absence_id;
        }
        $record = $absenceId === null ? null : $q->absence($r->user(), (int) $absenceId);
        if ($record !== null) {
            $year = SessionYear::findOrFail($record['session_year_id']);
        }
        $configuration = $q->configuration($r->user(), (int) $year->id, $from, $to, max(1, (int) $r->input('page', 1)));
        $choices = null;
        $choose = $r->input('choose', $r->route('occurrence'));
        if ($configuration['can_manage'] && $record !== null && $choose !== null) {
            $selected = collect($record['occurrences'])->firstWhere('id', (int) $choose);
            abort_if($selected === null, 404);
            if (in_array($selected['state'], ['open', 'assigned'], true)) {
                $choices = $q->candidates($r->user(), $selected['id']);
            }
        }
        $draft = [];
        foreach ($r->except('_token') as $key => $value) {
            $draft[$key] = is_scalar($value) ? substr((string) $value, 0, 500) : '';
        }
        $operationId = is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid();

        return ['configuration' => $configuration, 'record' => $record, 'choices' => $choices, 'mine' => $q->mine($r->user(), $from, $to, (int) $year->id),
            'years' => SessionYear::orderByDesc('start_date')->get(), 'year' => $year, 'from' => $from, 'to' => $to, 'today' => $today,
            'canWrite' => (int) $year->id === app(SessionYearContext::class)->currentId() && app(ModuleGate::class)->writable('relief_cover'),
            'messages' => $messages, 'draft' => $r->isMethodSafe() ? [] : $draft, 'operationId' => $operationId];
    }

    public function index(CoverPanelRequest $r): Response
    {
        return response()->view('pastoral.cover', $this->model($r))->header('Cache-Control', 'no-store, private');
    }

    public function failure(CoverPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('pastoral.cover', $this->model($r, $messages), $status)->header('Cache-Control', 'no-store, private');
    }

    public function write(CoverPanelRequest $r): Response|RedirectResponse
    {
        $op = substr((string) $r->route()->getName(), strlen('relief-cover.'));
        $s = app(ReliefCover::class);
        try {
            if ($op === 'request') {
                $id = (int) $s->request($r->user(), $r->safe()->only(['session_year_id', 'teacher_id', 'starts_on', 'ends_on', 'reason']), $r->validated('operation_id'))->id;
            } elseif ($op === 'assign') {
                $row = $s->assign($r->user(), (int) $r->route('occurrence'), (int) $r->validated('substitute_teacher_id'), (int) $r->validated('revision'), $r->validated('operation_id'));
                $id = (int) $row->cover_absence_id;
            } else {
                $id = (int) $s->change($r->user(), (int) $r->route('absence'), (int) $r->validated('revision'), $op, $r->validated('reason'), $r->validated('operation_id'))->id;
            }
        } catch (ValidationException $e) {
            return $this->failure($r, array_merge(...array_values($e->errors())), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, [$e->getMessage(), __('Your changes were not applied. Compare the current record before trying again.')], $e->getStatusCode());
        }

        return redirect()->route('relief-cover.show', ['absence' => $id, 'session_year_id' => $r->validated('session_year_id'), 'from' => $r->validated('from'), 'to' => $r->validated('to')])->with('success', __('Relief cover saved'))->header('Cache-Control', 'no-store, private');
    }
}
