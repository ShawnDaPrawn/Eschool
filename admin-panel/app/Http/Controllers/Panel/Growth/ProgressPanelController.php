<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\ProgressPanelRequest;
use App\Models\SessionYear;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\LearnerNoteOperations;
use App\Services\Growth\LearnerNotes;
use App\Services\Growth\ProgressBands;
use App\Services\Growth\ProgressQueries;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ProgressPanelController extends Controller
{
    private function model(ProgressPanelRequest $r, array $messages = [], bool $conflict = false): array
    {
        $queries = app(ProgressQueries::class);
        $configuration = $queries->configuration($r->user());
        $rawMode = $r->input('mode');
        $mode = is_string($rawMode) && in_array($rawMode, ['academic', 'counselling', 'assignments'], true) ? $rawMode
            : ($configuration['groups'] === [] && $configuration['can_read_caseload'] ? 'counselling' : 'academic');
        $page = max(1, (int) $r->input('page', 1));
        $groupId = $this->id($r->input('group_id')) ?: null;
        $group = null;
        $roster = ['learners' => [], 'current_page' => 1, 'last_page' => 1];
        if ($mode === 'counselling') {
            abort_unless($configuration['can_read_caseload'], 403);
            $roster = $queries->caseload($r->user(), $page);
        } elseif ($mode === 'assignments') {
            abort_unless($configuration['can_assign_counselling'], 403);
            $roster = $queries->assignmentLearners($r->user(), $page);
        } elseif ($groupId !== null) {
            $group = $queries->group($r->user(), $groupId);
            $roster = $queries->roster($r->user(), $groupId, $page);
        }
        $learnerId = $this->id($r->route('learner') ?? $r->input('learner_id'));
        $year = $this->id($r->input('session_year_id'), $configuration['session_year_id']);
        SessionYear::withTrashed()->findOrFail($year);
        $subject = $this->id($r->input('subject_id')) ?: null;
        $rawVisibility = $r->input('visibility');
        $visibility = $mode === 'counselling' ? 'counselling' : (is_string($rawVisibility) && in_array($rawVisibility, ['staff', 'tutor'], true) ? $rawVisibility : 'staff');
        abort_unless($mode === 'counselling' || in_array($visibility, ['staff', 'tutor'], true), 403);
        $noteId = $mode === 'assignments' ? null : ($r->route('note') ?? $r->input('note'));
        $record = is_scalar($noteId) && (int) $noteId > 0 ? app(LearnerNotes::class)->read($r->user(), (int) $noteId) : null;
        if ($record !== null) {
            $learnerId = $record['learner_id'];
            $year = $record['session_year_id'];
            $subject = $record['subject_id'];
            $visibility = $record['visibility'];
            abort_unless(($visibility === 'counselling') === ($mode === 'counselling'), 404);
        }
        $listing = ['notes' => [], 'next_after' => null];
        $assignments = [];
        if ($learnerId > 0) {
            if ($mode === 'assignments') {
                $assignments = app(LearnerNotes::class)->assignments($r->user(), $learnerId);
            } else {
                $listing = app(LearnerNotes::class)->listing($r->user(), $learnerId, $subject, $year, $visibility, $this->id($r->input('after')));
            }
        }
        $band = null;
        $termIds = $group === null ? [] : (collect($configuration['groups'])->firstWhere('id', (int) $group->id)['term_ids'] ?? []);
        $availableTerms = array_values(array_filter($configuration['terms'], fn ($option) => in_array($option['id'], $termIds, true)));
        $term = $this->id($r->input('term_id'), $availableTerms[0]['id'] ?? 0);
        if (! in_array($term, $termIds, true)) {
            $term = $availableTerms[0]['id'] ?? 0;
        }
        if ($mode === 'academic' && $learnerId > 0 && $group !== null && $term > 0 && $year === $configuration['session_year_id']) {
            try {
                $band = app(ProgressBands::class)->staff($r->user(), (int) $group->id, $learnerId, $term);
            } catch (ValidationException $error) {
                $band = null;
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [404, 409, 422], true)) {
                    throw $error;
                }
            }
        }
        $history = $record === null ? [] : app(LearnerNotes::class)->history($r->user(), $record['id']);
        $draft = ! $r->isMethodSafe() && is_string($r->input('body')) ? mb_substr($r->input('body'), 0, 20000) : null;
        $operationId = ! $conflict && is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid();
        $canWrite = ! app(SessionYearContext::class)->isReadOnly() && $year === $configuration['session_year_id'];
        $selection = array_filter(['mode' => $mode, 'group_id' => $groupId, 'learner_id' => $learnerId ?: null, 'subject_id' => $subject,
            'session_year_id' => $year, 'visibility' => $visibility, 'term_id' => $term ?: null], fn ($v) => $v !== null);

        return compact('configuration', 'mode', 'groupId', 'group', 'roster', 'learnerId', 'year', 'subject', 'visibility', 'record', 'listing',
            'assignments', 'band', 'term', 'availableTerms', 'history', 'draft', 'operationId', 'canWrite', 'selection', 'messages', 'conflict') + ['actorId' => (int) $r->user()->id, 'schoolId' => app(CurrentSchool::class)->id()];
    }

    private function id(mixed $value, int $default = 0): int
    {
        return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0 ? (int) $value : $default;
    }

    public function index(ProgressPanelRequest $r): Response
    {
        return response()->view('growth.progress-notes', $this->model($r))->header('Cache-Control', 'no-store, private');
    }

    public function failure(ProgressPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('growth.progress-notes', $this->model($r, $messages, $status === 409), $status)->header('Cache-Control', 'no-store, private');
    }

    public function write(ProgressPanelRequest $r): Response|RedirectResponse
    {
        $d = $r->validated();
        $op = $r->operation();
        $learner = (int) $r->route('learner');
        $note = (int) $r->route('note');
        $subject = isset($d['subject_id']) ? (int) $d['subject_id'] : null;
        $data = array_diff_key($d, array_flip(['expected_account_id', 'expected_school_id', 'review_required', 'confirm_current', 'mode', 'group_id', 'term_id', 'page']));
        if ($op === 'configure') {
            foreach (['high_pct' => 'high_bp', 'secure_pct' => 'secure_bp', 'minimum_coverage_pct' => 'minimum_coverage_bp'] as $field => $key) {
                $parts = explode('.', (string) $d[$field], 2);
                $d[$key] = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
                $data[$key] = $d[$key];
            }
        }
        $notes = app(LearnerNotes::class);
        $policy = app(LearnerNotesPolicy::class);
        $authority = match ($op) {
            'create' => fn () => $policy->scope($r->user(), $learner, $subject, (int) $d['session_year_id'], $d['visibility']),
            'update' => fn () => $notes->authorize($r->user(), $note, true),
            'assign', 'revoke-assignment' => fn () => $policy->assign($r->user(), $learner),
            'configure' => fn () => app(AcademicAuthority::class)->manage($r->user()),
        };
        try {
            $result = app(LearnerNoteOperations::class)->once($r->user(), 'panel.'.$op, array_replace($data, ['learner_id' => $learner, 'note_id' => $note]), $authority,
                fn () => match ($op) {
                    'create' => $notes->create($r->user(), $learner, $subject, (int) $d['session_year_id'], $d['visibility'], $d['body']),
                    'update' => $notes->update($r->user(), $note, (int) $d['expected_revision'], $d['body'], (bool) ($d['archive'] ?? false)),
                    'assign' => $notes->assign($r->user(), $learner, (int) $d['staff_user_id'], $d),
                    'revoke-assignment' => $notes->revokeAssignment($r->user(), $learner, (int) $d['staff_user_id'], (int) $d['expected_revision']),
                    'configure' => app(ProgressBands::class)->configure($r->user(), $d),
                });
        } catch (ValidationException $e) {
            return $this->failure($r, $op === 'configure' ? [__('Choose a secure threshold below the high threshold and a positive minimum assessed share.')] : $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, [$e->getMessage(), __('Compare the current note before trying again.')], $e->getStatusCode());
        }
        $selection = array_intersect_key($d, array_flip(['mode', 'group_id', 'subject_id', 'session_year_id', 'visibility', 'term_id']));
        $selection['learner_id'] = $learner ?: ($d['learner_id'] ?? null);
        if (in_array($op, ['create', 'update'], true)) {
            $selection = array_replace($selection, ['note' => $result['id'], 'learner_id' => $result['learner_id'], 'subject_id' => $result['subject_id'],
                'session_year_id' => $result['session_year_id'], 'visibility' => $result['visibility']]);
        }

        return redirect()->route('learner-notes.index', $selection)->with('success', __('Learner note change saved'))->header('Cache-Control', 'no-store, private');
    }
}
