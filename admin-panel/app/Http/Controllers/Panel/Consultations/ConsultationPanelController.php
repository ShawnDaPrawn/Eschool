<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Consultations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\ConsultationPanelRequest;
use App\Services\Consultations\ConsultationQueries;
use App\Services\Consultations\Consultations;
use App\Services\Panel\SessionYearContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ConsultationPanelController extends Controller
{
    private function common(ConsultationPanelRequest $request, array $messages = []): array
    {
        $access = app(ConsultationQueries::class)->access($request->user());
        $year = (int) $request->input('session_year_id', app(SessionYearContext::class)->selectedId() ?? $access['session_year_id']);
        $canWrite = $access['writable'] && ! app(SessionYearContext::class)->isReadOnly() && $year === $access['session_year_id'];

        return compact('access', 'year', 'canWrite', 'messages');
    }

    public function index(ConsultationPanelRequest $request): Response
    {
        $model = $this->common($request);
        $managed = $model['access']['can_manage'];
        $listing = app(ConsultationQueries::class)->listing($request->user(), $model['year'], (int) $request->input('page', 1), $managed);

        return response()->view('consultations.index', $model + compact('listing', 'managed'));
    }

    /** Return only bounded authoring fields; never copy vote answers to session flash. */
    private function draft(ConsultationPanelRequest $request, ?array $configuration): array
    {
        $timezone = app(ConsultationQueries::class)->access($request->user())['timezone'];
        $base = $configuration ?? ['type' => 'poll', 'title' => '', 'instructions' => '', 'anonymous' => true, 'results_visibility' => 'after_close', 'audience' => ['kind' => 'school', 'parents' => false, 'include_staff' => false],
            'questions' => [['kind' => 'single', 'label' => '', 'required' => true, 'max_choices' => 1, 'options' => [['label' => '', 'statement' => ''], ['label' => '', 'statement' => '']]]]];
        $base['opens_at_local'] = CarbonImmutable::parse($configuration['opens_at'] ?? now()->toISOString())->setTimezone($timezone)->format('Y-m-d\TH:i');
        $base['closes_at_local'] = CarbonImmutable::parse($configuration['closes_at'] ?? now()->addDay()->toISOString())->setTimezone($timezone)->format('Y-m-d\TH:i');
        if ($request->isMethodSafe() || ! in_array($request->operation(), ['create', 'save'], true)) {
            return $base;
        }
        foreach (['title' => 191, 'instructions' => 5000, 'type' => 20, 'results_visibility' => 20, 'opens_at_local' => 16, 'closes_at_local' => 16] as $key => $limit) {
            if (is_string($request->input($key))) {
                $base[$key] = mb_substr($request->input($key), 0, $limit);
            }
        }
        $base['anonymous'] = $request->boolean('anonymous');
        $audience = $request->input('audience');
        if (is_array($audience)) {
            $base['audience'] = ['kind' => is_string($audience['kind'] ?? null) ? mb_substr($audience['kind'], 0, 30) : 'school',
                'target_id' => is_numeric($audience['target_id'] ?? null) ? (int) $audience['target_id'] : null,
                'parents' => filter_var($audience['parents'] ?? false, FILTER_VALIDATE_BOOL), 'include_staff' => filter_var($audience['include_staff'] ?? false, FILTER_VALIDATE_BOOL)];
        }
        if (is_array($request->input('questions'))) {
            $base['questions'] = [];
            foreach (array_slice($request->input('questions'), 0, 20) as $q) {
                if (! is_array($q)) {
                    continue;
                }
                $item = ['kind' => is_string($q['kind'] ?? null) ? mb_substr($q['kind'], 0, 20) : 'single', 'label' => is_string($q['label'] ?? null) ? mb_substr($q['label'], 0, 1000) : '',
                    'required' => filter_var($q['required'] ?? false, FILTER_VALIDATE_BOOL), 'max_choices' => is_numeric($q['max_choices'] ?? null) ? min(50, max(1, (int) $q['max_choices'])) : 1, 'options' => []];
                foreach (array_slice(is_array($q['options'] ?? null) ? $q['options'] : [], 0, 50) as $o) {
                    if (is_array($o)) {
                        $item['options'][] = ['label' => is_string($o['label'] ?? null) ? mb_substr($o['label'], 0, 191) : '', 'statement' => is_string($o['statement'] ?? null) ? mb_substr($o['statement'], 0, 2000) : ''];
                    }
                }
                $base['questions'][] = $item;
            }
        }

        return $base;
    }

    public function form(ConsultationPanelRequest $request): Response
    {
        return $this->configurationResponse($request);
    }

    private function configurationResponse(ConsultationPanelRequest $request, array $messages = [], int $status = 200): Response
    {
        $model = $this->common($request, $messages);
        $id = $request->route('consultation') === null ? null : (int) $request->route('consultation');
        $configuration = $id === null ? null : app(Consultations::class)->configuration($request->user(), $id);
        $canManageChange = $model['canWrite'] && ($configuration['writable'] ?? true);
        if ($configuration !== null) {
            $model['year'] = $configuration['session_year_id'];
            $model['canWrite'] = $model['canWrite'] && $configuration['writable'] && $configuration['status'] === 'draft';
        }
        $draft = $this->draft($request, $configuration);
        $kind = (string) $request->input('kind', $draft['audience']['kind']);
        $selected = $request->input('selected', $draft['audience']['target_id'] ?? null);
        $choices = $kind === 'school' ? null : app(ConsultationQueries::class)->audiences($request->user(), $model['year'], $kind,
            (string) $request->input('search', ''), (int) $request->input('audience_page', 1), $selected === null ? null : (int) $selected);
        $draft['audience']['kind'] = $kind;
        if ($choices !== null) {
            $draft['audience']['target_id'] = $choices['selected_id'];
        }
        $operationId = (string) Str::uuid();

        return response()->view('consultations.form', $model + compact('id', 'configuration', 'draft', 'choices', 'operationId', 'canManageChange'), $status);
    }

    public function ballot(ConsultationPanelRequest $request): Response
    {
        return $this->ballotResponse($request);
    }

    private function ballotResponse(ConsultationPanelRequest $request, array $messages = [], int $status = 200): Response
    {
        $ballot = app(Consultations::class)->ballot($request->user(), (int) $request->route('consultation'));
        // Failed answers remain only in this private response to their authorized author.
        $answers = [];
        if (! $request->isMethodSafe() && is_array($request->input('answers'))) {
            foreach (array_slice($request->input('answers'), 0, 20) as $answer) {
                if (! is_array($answer) || ! is_numeric($answer['question_id'] ?? null)) {
                    continue;
                }
                $answers[(int) $answer['question_id']] = ['text' => is_string($answer['text'] ?? null) ? mb_substr($answer['text'], 0, 2000) : '',
                    'option_ids' => array_values(array_filter(array_slice(is_array($answer['option_ids'] ?? null) ? $answer['option_ids'] : [], 0, 50), 'is_numeric'))];
            }
        }

        return response()->view('consultations.ballot', $this->common($request, $messages) + compact('ballot', 'answers'), $status);
    }

    public function results(ConsultationPanelRequest $request): Response
    {
        $managed = $request->boolean('managed');
        $result = app(Consultations::class)->results($request->user(), (int) $request->route('consultation'), $managed);
        $responses = $managed && $result['closed'] ? app(Consultations::class)->responses($request->user(), $result['id'], (int) $request->input('page', 1)) : null;

        return response()->view('consultations.results', $this->common($request) + compact('result', 'responses', 'managed'));
    }

    public function history(ConsultationPanelRequest $request): Response
    {
        $id = (int) $request->route('consultation');
        $queries = app(ConsultationQueries::class);
        $history = $queries->history($request->user(), $id, (int) $request->input('history_page', 1));
        $entry = $request->filled('event') ? $queries->historyEntry($request->user(), $id, (int) $request->input('event')) : null;

        return response()->view('consultations.history', $this->common($request) + compact('history', 'entry', 'id'));
    }

    public function failure(ConsultationPanelRequest $request, array $messages, int $status): Response
    {
        return $request->operation() === 'vote' ? $this->ballotResponse($request, $messages, $status) : $this->configurationResponse($request, $messages, $status);
    }

    public function write(ConsultationPanelRequest $request): Response|RedirectResponse
    {
        $service = app(Consultations::class);
        $actor = $request->user();
        $id = (int) $request->route('consultation');
        try {
            $result = match ($request->operation()) {
                'create', 'save' => $service->save($actor, $id === 0 ? null : $id, $request->validated()),
                'publish' => $service->publish($actor, $id, $request->validated()),
                'close', 'cancel' => $service->end($actor, $id, $request->operation(), $request->validated()),
                'vote' => $service->vote($actor, $id, $request->validated('answers')),
                default => abort(404),
            };
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($request, [__('Review the current consultation before trying again.')], $error->getStatusCode());
        }
        if ($request->operation() === 'vote') {
            return redirect()->route('consultations-panel.ballot', ['consultation' => $id])->with('success', __('Your response is recorded. You can participate only once.'));
        }

        return redirect()->route('consultations-panel.edit', ['consultation' => $result['id']])->with('success', __('Consultation change saved'));
    }
}
