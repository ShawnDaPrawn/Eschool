<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Discipline;

use App\Http\Controllers\Api\V2\Discipline\DisciplineController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discipline\DisciplinePanelRequest;
use App\Services\Discipline\DisciplineAuthority;
use App\Services\Discipline\DisciplineDetentions;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineQueries;
use App\Services\Discipline\DisciplineRecords;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DisciplinePanelController extends Controller
{
    private function id(mixed $raw, int $default = 0): int
    {
        return (is_int($raw) || (is_string($raw) && ctype_digit($raw))) && (int) $raw > 0 ? (int) $raw : $default;
    }

    private function model(DisciplinePanelRequest $r, array $messages = [], bool $conflict = false): array
    {
        $q = app(DisciplineQueries::class);
        $config = $q->configuration($r->user());
        $year = $this->id($r->input('session_year_id'), $config['session_year_id']);
        app(DisciplineAuthority::class)->year($year);
        $mode = $r->input('mode');
        $mode = is_string($mode) && in_array($mode, ['records', 'framework', 'detentions'], true) ? $mode : 'records';
        $page = $this->id($r->input('page'), 1);
        $learner = $this->id($r->route('learner') ?? $r->input('learner_id'));
        $recordId = $this->id($r->route('record') ?? $r->input('record'));
        $detentionId = $this->id($r->route('detention') ?? $r->input('detention'));
        $record = $detention = null;
        $history = [];
        $people = ['learners' => [], 'page' => 1, 'last_page' => 1];
        $staff = ['supervisors' => [], 'page' => 1, 'last_page' => 1];
        $rows = ['records' => [], 'sessions' => [], 'page' => 1, 'last_page' => 1];
        $framework = app(DisciplineFrameworks::class)->read($r->user(), $year)['framework'];
        if ($mode === 'records') {
            $rows = $q->records($r->user(), $year, $page);
            if ($recordId > 0) {
                $record = app(DisciplineRecords::class)->read($r->user(), $recordId);
                $history = app(DisciplineRecords::class)->versions($r->user(), $recordId);
                $learner = $record['learner_id'];
                $year = $record['session_year_id'];
            } elseif ($year === $config['session_year_id']) {
                $search = is_string($r->input('search')) ? mb_substr($r->input('search'), 0, 100) : null;
                $people = $q->learners($r->user(), $year, $this->id($r->input('learner_page'), 1), $search);
                if ($learner > 0) {
                    app(DisciplineAuthority::class)->record($r->user(), $learner, $year, $config['today']);
                }
            }
        } elseif ($mode === 'detentions') {
            $rows = $q->detentions($r->user(), $year, $page);
            if ($detentionId > 0) {
                $detention = app(DisciplineDetentions::class)->read($r->user(), $detentionId);
                $year = $detention['session_year_id'];

            }
            $grade = $this->id($r->input('grade_level_id'), (int) ($config['grades'][0]['id'] ?? 0));
            $date = is_string($r->input('on_date')) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $r->input('on_date')) ? $r->input('on_date') : $config['today'];
            if ($grade > 0 && $year === $config['session_year_id']) {
                $staff = $q->supervisors($r->user(), $year, $grade, $date, $this->id($r->input('staff_page'), 1));
            }
        }
        $draft = [];
        foreach (['description' => 2000, 'staff_note' => 2000, 'reason' => 1000, 'family_reason' => 1000, 'location' => 200, 'title' => 150] as $key => $limit) {
            if (! $r->isMethodSafe() && is_string($r->input($key))) {
                $draft[$key] = mb_substr($r->input($key), 0, $limit);
            }
        }
        $definition = $framework['definition'] ?? ['counter_scope' => '', 'reset' => '', 'rolling_days' => null, 'levels' => [], 'sanctions' => [], 'infractions' => []];
        if (! $r->isMethodSafe() && $r->operation() === 'configure') {
            foreach (['levels' => 8, 'sanctions' => 30, 'infractions' => 100] as $key => $limit) {
                if (is_array($r->input($key))) {
                    $definition[$key] = array_map(function ($row) {
                        $safe = [];
                        foreach (['code', 'name', 'threshold', 'kind', 'base_level'] as $field) {
                            $safe[$field] = is_scalar($row[$field] ?? null) ? mb_substr((string) $row[$field], 0, 200) : '';
                        }
                        $safe['sanctions'] = is_array($row['sanctions'] ?? null) ? array_values(array_filter($row['sanctions'], 'is_string')) : [];

                        return $safe;
                    }, array_slice(array_filter($r->input($key), 'is_array'), 0, $limit));
                }
            }
            foreach (['counter_scope', 'reset', 'rolling_days'] as $key) {
                if (is_scalar($r->input($key)) || $r->input($key) === null) {
                    $definition[$key] = $r->input($key);
                }
            }
        }
        $input = [];
        foreach (['occurred_on', 'infraction_code', 'sanction_code', 'decision', 'grade_level_id', 'on_date', 'starts_at', 'ends_at', 'capacity', 'supervisor_id', 'record_id', 'attendance'] as $key) {
            if (is_scalar($r->input($key))) {
                $input[$key] = mb_substr((string) $r->input($key), 0, 200);
            }
        }
        $sessionChoices = $record === null ? ['sessions' => [], 'page' => 1, 'last_page' => 1] : $q->detentions($r->user(), $record['session_year_id'], $this->id($r->input('session_page'), 1));
        $sessions = array_values(array_filter($sessionChoices['sessions'], fn ($item) => $item['grade_level_id'] === $record['grade_level_id'] && $item['status'] === 'planned'));
        $operationId = ! $conflict && is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid();
        $canWrite = ! app(SessionYearContext::class)->isReadOnly() && app(ModuleGate::class)->writable('discipline');
        $canReview = $record !== null && ($config['can_manage'] || in_array($record['grade_level_id'], $config['grade_ids'], true)) && ($record['hearing_decision'] === null || $config['can_hear']);
        $canRoster = $detention !== null && ($config['can_manage'] || in_array($detention['grade_level_id'], $config['grade_ids'], true));
        $selection = array_filter(['mode' => $mode, 'session_year_id' => $year, 'learner_id' => $learner ?: null], fn ($v) => $v !== null);

        return compact('input', 'sessions', 'sessionChoices', 'config', 'year', 'mode', 'page', 'learner', 'record', 'recordId', 'history', 'detention', 'detentionId', 'rows', 'people', 'staff', 'framework', 'definition', 'draft', 'operationId', 'messages', 'conflict', 'canWrite', 'canReview', 'canRoster', 'selection') +
            ['hasDraft' => ! $r->isMethodSafe(), 'selectedGrade' => $grade ?? 0, 'selectedDate' => $date ?? $config['today'], 'actorId' => (int) $r->user()->id, 'schoolId' => app(CurrentSchool::class)->id()];
    }

    public function index(DisciplinePanelRequest $r): Response
    {
        return response()->view('discipline.workbench', $this->model($r))->header('Cache-Control', 'private, no-store');
    }

    public function failure(DisciplinePanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('discipline.workbench', $this->model($r, $messages, $status === 409), $status)->header('Cache-Control', 'private, no-store');
    }

    public function write(DisciplinePanelRequest $r): Response|RedirectResponse
    {
        $controller = app(DisciplineController::class);
        $record = (int) $r->route('record');
        $detention = (int) $r->route('detention');
        try {
            $response = match ($r->operation()) {
                'configure' => $controller->configure($r), 'create' => $controller->create($r, (int) $r->route('learner')),
                'review' => $controller->review($r, $record), 'hearing' => $controller->hearing($r, $record),
                'detention_create' => $controller->detentionCreate($r), 'enroll' => $controller->enroll($r, $detention),
                'attendance' => $controller->attendance($r, $detention, (int) $r->route('entry')), 'cancel' => $controller->cancel($r, $detention), default => abort(404),
            };
            $result = $response->getData(true)['data'];
            $selection = ['mode' => $r->input('mode', 'records'), 'session_year_id' => $r->year()];
            if ($r->operation() === 'create') {
                $selection['record'] = $result['id'];
            } elseif ($record > 0) {
                $selection['record'] = $record;
            } elseif ($r->operation() === 'detention_create') {
                $selection['detention'] = $result['id'];
            } elseif ($detention > 0) {
                $selection['detention'] = $detention;
            }

            if ($r->operation() === 'enroll') {
                $selection['mode'] = 'detentions';
                $selection['detention'] = $detention;
            }

            return redirect()->route('discipline-panel.index', $selection)->with('success', __('Saved.'))->header('Cache-Control', 'private, no-store');
        } catch (ValidationException $error) {
            return $this->failure($r, $error->errors() === [] ? [__('Review the highlighted fields.')] : array_merge(...array_values($error->errors())), 422);
        } catch (HttpExceptionInterface $error) {
            if (in_array($error->getStatusCode(), [409, 422], true)) {
                return $this->failure($r, [$error->getMessage() ?: __('Reload the current entry before saving.')], $error->getStatusCode());
            }
            throw $error;
        }
    }
}
