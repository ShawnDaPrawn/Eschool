<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\FamilyForms;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyForms\FamilyPanelRequest;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\FamilyFormQueries;
use App\Services\FamilyForms\FamilyPanelScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FamilyPanelController extends Controller
{
    private function common(FamilyPanelRequest $request, array $messages = []): array
    {
        app(FamilyPanelScope::class)->authorize($request);
        $access = app(FamilyFormQueries::class)->configuration($request->user());
        $account = (int) $request->user()->id;
        $school = app(CurrentSchool::class)->id();
        $operation = Str::isUuid($request->input('operation_id', '')) ? $request->input('operation_id') : (string) Str::uuid();

        return compact('access', 'account', 'school', 'operation', 'messages');
    }

    public function index(FamilyPanelRequest $request): Response
    {
        $model = $this->common($request);
        $group = $request->integer('activity_group_id') ?: ($model['access']['groups'][0]['id'] ?? null);
        $learner = null;
        $listing = ($group === null && $learner === null) ? ['items' => [], 'next_page' => null] : app(FamilyFormQueries::class)->listing($request->user(), $learner, $group, $request->integer('page', 1));

        return response()->view('family-forms.index', $model + compact('listing', 'group', 'learner'));
    }

    public function form(FamilyPanelRequest $request): Response
    {
        return $this->renderForm($request);
    }

    public function show(FamilyPanelRequest $request): Response
    {
        return $this->renderShow($request);
    }

    private function renderForm(FamilyPanelRequest $request, array $messages = [], int $status = 200): Response
    {
        $model = $this->common($request, $messages);
        $draft = ['activity_group_id' => $request->integer('activity_group_id') ?: ($model['access']['groups'][0]['id'] ?? null), 'title' => '', 'wording' => ''];
        foreach (['title' => 191, 'wording' => 20000, 'event_starts_at_local' => 16, 'event_ends_at_local' => 16, 'respond_by_local' => 16] as $field => $limit) {
            $draft[$field] = is_string($request->input($field)) ? mb_substr($request->input($field), 0, $limit) : '';
        }
        $eligible = [];
        if (in_array($request->operation(), ['prepare', 'create'], true) && $request->input('event_starts_at') !== null && $request->input('event_ends_at') !== null && $request->input('respond_by') !== null) {
            try {
                $eligible = app(Consents::class)->eligible($request->user(), $request->all());
            } catch (ValidationException $error) {
                $messages = array_merge($messages, $error->validator->errors()->all());
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [404, 409, 422, 423], true)) {
                    throw $error;
                }
                $messages[] = $error->getMessage() ?: __('Refresh the activity dates and participants.');
            }
        }
        $selected = is_array($request->input('learner_ids')) ? array_map('intval', array_filter(array_slice($request->input('learner_ids'), 0, 500), 'is_scalar')) : [];
        $model['messages'] = $messages;

        return response()->view('family-forms.form', $model + compact('draft', 'eligible', 'selected'), $status);
    }

    private function renderShow(FamilyPanelRequest $request, array $messages = [], int $status = 200): Response
    {
        $model = $this->common($request, $messages);
        $managed = true;
        $learner = 0;
        $id = (string) $request->route('consent');
        $record = app(FamilyFormQueries::class)->managed($request->user(), $id, $request->integer('page', 1));
        $zone = $model['access']['timezone'];
        $dates = [];
        foreach (['event_starts_at', 'event_ends_at', 'respond_by'] as $field) {
            $dates[$field] = CarbonImmutable::parse($record[$field])->setTimezone($zone)->format('Y-m-d H:i');
        }

        return response()->view('family-forms.show', $model + compact('record', 'managed', 'learner', 'dates'), $status);
    }

    public function write(FamilyPanelRequest $request): Response|RedirectResponse
    {
        try {
            if ($request->operation() === 'prepare') {
                return $this->renderForm($request);
            }
            $service = app(Consents::class);
            if ($request->operation() === 'create') {
                $record = $service->create($request->user(), $request->validated());

                return redirect()->route('family-forms-panel.managed', ['consent' => $record['id']]);
            }
            $id = (string) $request->route('consent');
            if ($request->operation() === 'cancel') {
                $service->cancel($request->user(), $id, $request->validated());

                return redirect()->route('family-forms-panel.managed', ['consent' => $id]);
            }
            abort(404);
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return $this->failure($request, [$error->getMessage()], $error->getStatusCode());
        }
    }

    public function failure(FamilyPanelRequest $request, array $messages, int $status): Response
    {
        app(FamilyPanelScope::class)->authorize($request);
        if (in_array($request->operation(), ['new', 'prepare', 'create'], true)) {
            return $this->renderForm($request, $messages, $status);
        }
        if ($request->route('consent') !== null) {
            return $this->renderShow($request, $messages, $status);
        }

        return response()->view('family-forms.invalid', $this->common($request, $messages), $status);
    }
}
