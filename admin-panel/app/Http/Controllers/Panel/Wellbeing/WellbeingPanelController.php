<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Wellbeing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\WellbeingPanelRequest;
use App\Models\User;
use App\Models\Wellbeing\Category;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\Casework;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingPanelScope;
use App\Services\Wellbeing\WellbeingQueries;
use App\Services\Wellbeing\WellbeingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WellbeingPanelController extends Controller
{
    private function model(WellbeingPanelRequest $r, array $messages = [], bool $conflict = false): array
    {
        app(WellbeingPanelScope::class)->authorize($r);
        $queries = app(WellbeingQueries::class);
        $access = $queries->access($r->user());
        $operation = $r->operation();
        $configurationMode = $r->isMethodSafe() ? $r->query('mode', $access['professional'] ? 'clinical' : 'configuration') === 'configuration' : app(WellbeingPanelScope::class)->configurationOperation($operation);
        $page = max(1, min(10000, (int) $r->input('page', 1)));
        $search = is_string($r->input('search')) ? mb_substr($r->input('search'), 0, 100) : '';
        $configuration = $configurationMode ? $queries->configuration($r->user(), $search, $page) : null;
        $managedCases = $configurationMode ? $queries->managedCases($r->user(), $page) : null;
        $categoryId = $r->route('category') ?? $r->input('category');
        $category = ! $configurationMode || $categoryId === null ? null : Category::findOrFail((int) $categoryId)->only(['id', 'name', 'emergency_guidance', 'active', 'revision']);
        $managedCaseId = $r->route('case') ?? $r->input('managed_case');
        $managedCase = ! $configurationMode || $managedCaseId === null ? null : $queries->caseMetadata($r->user(), (int) $managedCaseId);
        $team = $category === null ? null : $queries->assignments($r->user(), 'team', $category['id'], $page);
        $assignments = $managedCase === null ? null : $queries->assignments($r->user(), 'case', $managedCase['id'], $page);
        $caseId = $r->route('case') ?? $r->input('case');
        $reportId = $r->route('report') ?? $r->input('report');
        $case = $configurationMode || $caseId === null ? null : $queries->case($r->user(), (int) $caseId, $r->input('before') === null ? null : (int) $r->input('before'));
        $report = $configurationMode || $reportId === null || $case !== null ? null : $queries->report($r->user(), (int) $reportId, $r->input('before') === null ? null : (int) $r->input('before'), true);
        $caseList = $configurationMode ? null : $queries->cases($r->user(), $page);
        $reportList = $configurationMode ? null : $queries->reports($r->user(), $page);
        $calendarFrom = $this->calendarDate($r->input('calendar_from'), $access['today']);
        $defaultTo = CarbonImmutable::parse($calendarFrom)->addDays(30)->toDateString();
        $calendarTo = $this->calendarDate($r->input('calendar_to'), $defaultTo);
        if ($calendarTo < $calendarFrom || CarbonImmutable::parse($calendarFrom)->diffInDays(CarbonImmutable::parse($calendarTo)) > 31) {
            $calendarTo = $defaultTo;
            $messages[] = __('Choose a calendar window of up to 31 days.');
        }
        $appointments = $case === null ? [] : app(Casework::class)->appointments($r->user(), $case['id'], $calendarFrom, $calendarTo);
        $appointmentNames = [];
        foreach ($appointments as $appointment) {
            $person = User::find($appointment['professional_user_id']);
            $appointmentNames[$appointment['professional_user_id']] = $person === null ? __('Former professional') : trim($person->first_name.' '.$person->last_name);
        }
        $draft = [];
        if (! $r->isMethodSafe()) {
            foreach (['name' => 191, 'emergency_guidance' => 2000, 'body' => 20000, 'reason' => 2000, 'notes' => 20000, 'location' => 500,
                'learner_id' => 20, 'user_id' => 20, 'professional_user_id' => 20, 'incident_report_id' => 20, 'supersedes_id' => 20,
                'starts_on' => 10, 'ends_on' => 10, 'starts_at' => 30, 'ends_at' => 30, 'happened_at' => 30, 'active' => 1, 'internal' => 1, 'status' => 20, 'kind' => 20] as $field => $limit) {
                if (is_scalar($r->input($field))) {
                    $draft[$field] = mb_substr((string) $r->input($field), 0, $limit);
                }
            }
        }
        $choiceDate = isset($draft['starts_at']) && preg_match('/^\d{4}-\d{2}-\d{2}T/', $draft['starts_at']) ? $this->calendarDate(substr($draft['starts_at'], 0, 10), $access['today']) : $access['today'];
        $professionalSearch = is_string($r->input('professional_search')) ? mb_substr($r->input('professional_search'), 0, 100) : '';
        $professionalPage = max(1, min(10000, (int) $r->input('professional_page', 1)));
        if ($operation === 'case-choices') {
            $professionalPage = match ($r->input('choice_direction')) {
                'next' => min(10000, $professionalPage + 1), 'previous' => max(1, $professionalPage - 1), default => 1
            };
        }
        $selectedProfessional = isset($draft['professional_user_id']) && ctype_digit($draft['professional_user_id']) ? (int) $draft['professional_user_id'] : null;
        $professionals = $case === null ? null : $queries->caseProfessionals($r->user(), $case['id'], $choiceDate, $professionalSearch, $professionalPage, $selectedProfessional);
        $incidentChoices = $case === null ? null : $queries->caseReportChoices($r->user(), $case['id'], $page, isset($draft['incident_report_id']) && ctype_digit($draft['incident_report_id']) ? (int) $draft['incident_report_id'] : $case['incident_report_id']);
        $target = $r->route('assignment') ?? $r->route('appointment') ?? $r->route('case') ?? $r->route('report') ?? $r->route('category') ?? 'new';
        $draftKey = $r->isMethodSafe() ? '' : ($operation === 'case-choices' ? 'appointment-create' : $operation).':'.$target;
        $operationId = ! $conflict && is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid();

        return compact('access', 'configurationMode', 'page', 'search', 'configuration', 'managedCases', 'category', 'managedCase', 'team', 'assignments', 'case', 'report', 'caseList', 'reportList', 'appointments', 'appointmentNames',
            'calendarFrom', 'calendarTo', 'professionals', 'professionalSearch', 'professionalPage', 'incidentChoices', 'draft', 'draftKey', 'operationId', 'messages', 'conflict')
            + ['canWrite' => $access['can_write'] && ! app(SessionYearContext::class)->isReadOnly(), 'actorId' => (int) $r->user()->id, 'schoolId' => app(CurrentSchool::class)->id(), 'timezone' => app(CurrentSchool::class)->school()->timezone];
    }

    public function index(WellbeingPanelRequest $r): Response
    {
        return response()->view('wellbeing.panel', $this->model($r))->header('Cache-Control', 'no-store, private');
    }

    public function failure(WellbeingPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('wellbeing.panel', $this->model($r, $messages, $status === 409), $status)->header('Cache-Control', 'no-store, private');
    }

    private function calendarDate(mixed $value, string $fallback): string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return $fallback;
        }
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

            return $date !== false && $date->toDateString() === $value ? $value : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function localTimestamp(string $value): string
    {
        $time = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $value, app(CurrentSchool::class)->school()->timezone);
        if ($time === false || $time->format('Y-m-d\TH:i') !== $value) {
            throw ValidationException::withMessages(['starts_at' => __('Choose a valid local appointment time.')]);
        }

        return $time->utc()->toISOString();
    }

    public function write(WellbeingPanelRequest $r): Response|RedirectResponse
    {
        if ($r->operation() === 'case-choices') {
            return $this->index($r);
        }
        $data = $r->validated();
        $settings = app(WellbeingSettings::class);
        $cases = app(Casework::class);
        $operation = $r->operation();
        $caseId = $r->route('case') === null ? null : (int) $r->route('case');
        $assignment = $r->route('assignment') === null ? null : (int) $r->route('assignment');
        try {
            if (in_array($operation, ['appointment-create', 'appointment-save', 'case-entry'], true)) {
                foreach ($operation === 'case-entry' ? ['happened_at'] : ['starts_at', 'ends_at'] as $field) {
                    $data[$field] = $this->localTimestamp($data[$field]);
                }
            }
            $result = match ($operation) {
                'category-create', 'category-save' => $settings->saveCategory($r->user(), $r->route('category') === null ? null : (int) $r->route('category'), $data),
                'team-create', 'team-save' => $settings->assignTeam($r->user(), (int) $r->route('category'), $assignment, $data),
                'case-create' => $settings->openCase($r->user(), $data),
                'case-assignment-create', 'case-assignment-save' => $settings->assignCase($r->user(), $caseId, $assignment, $data),
                'report-respond' => app(IncidentReporting::class)->respond($r->user(), (int) $r->route('report'), $data),
                'case-entry' => $cases->entry($r->user(), $caseId, $data), 'case-link' => $cases->linkIncident($r->user(), $caseId, $data), 'case-status' => $cases->status($r->user(), $caseId, $data),
                'appointment-create', 'appointment-save' => $cases->appointment($r->user(), $caseId, $r->route('appointment') === null ? null : (int) $r->route('appointment'), $data),
            };
        } catch (ValidationException $error) {
            return $this->failure($r, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($r, [$error->getMessage(), __('Your changes were not applied. Compare the current record before trying again.')], $error->getStatusCode());
        }
        $query = ['mode' => app(WellbeingPanelScope::class)->configurationOperation($operation) ? 'configuration' : 'clinical'];
        if ($query['mode'] === 'configuration') {
            if (str_starts_with($operation, 'category-') || str_starts_with($operation, 'team-')) {
                $query['category'] = $r->route('category') ?? $result['id'];
            } else {
                $query['managed_case'] = $caseId ?? $result['id'];
            }
        } elseif ($r->route('report') !== null) {
            $query['report'] = (int) $r->route('report');
        } else {
            $query['case'] = $caseId;
        }

        return redirect()->route('wellbeing.index', $query)->with('success', __('Wellbeing change saved'))->header('Cache-Control', 'no-store, private');
    }
}
