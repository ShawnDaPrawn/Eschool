<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\RegisterPanelRequest;
use App\Services\Academics\Attendance\RegisterOverview;
use App\Services\Academics\Attendance\RegisterQueries;
use App\Services\Academics\Attendance\Registers;
use App\Services\Academics\Attendance\RegisterSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RegisterPanelController extends Controller
{
    private function model(RegisterPanelRequest $request, ?int $id = null): array
    {
        $id ??= $request->validated('register') === null ? null : (int) $request->validated('register');
        $matrix = $id === null ? null : app(Registers::class)->matrix($request->user(), $id);
        $choices = app(RegisterQueries::class)->choices($request->user(), $matrix['on_date'] ?? $request->validated('on_date'), isset($matrix['session_year_id']) ? (int) $matrix['session_year_id'] : ($request->validated('session_year_id') === null ? null : (int) $request->validated('session_year_id')));

        return ['choices' => $choices, 'overview' => app(RegisterOverview::class)->rows($choices), 'matrix' => $matrix, 'draft' => [], 'conflict' => null, 'submitted' => null, 'operationId' => (string) Str::uuid(), 'reason' => ''];
    }

    public function index(RegisterPanelRequest $request): View
    {
        return view('academics.register-workbench', $this->model($request));
    }

    public function open(RegisterPanelRequest $request): RedirectResponse
    {
        $matrix = app(Registers::class)->open($request->user(), $request->validated());

        return redirect()->route('academic-registers.index', ['register' => $matrix['register_id']]);
    }

    public function sync(RegisterPanelRequest $request): RedirectResponse|Response
    {
        $id = (int) $request->validated('register_id');
        try {
            app(Registers::class)->sync($request->user(), $id, $request->validated());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return response()->view('academics.register-workbench', array_replace($this->model($request, $id), ['conflict' => $error->getMessage(), 'submitted' => $request->validated('rows'), 'reason' => $request->validated('reason', '')]), 409, ['Cache-Control' => 'private, no-store']);
        }

        return redirect()->route('academic-registers.index', ['register' => $id])->with('success', __('Register saved'));
    }

    public function review(RegisterPanelRequest $request): Response
    {
        $model = $this->model($request, (int) $request->validated('register_id'));
        $eligible = array_column($model['matrix']['learners'], 'learner_id');
        $draft = [];
        foreach ($request->validated('rows') as $row) {
            if (in_array((int) $row['learner_id'], $eligible, true)) {
                $draft[(int) $row['learner_id']] = $row['state'];
            }
        }

        return response()->view('academics.register-workbench', array_replace($model, ['draft' => $draft, 'reason' => $request->validated('reason', '')]), 200, ['Cache-Control' => 'private, no-store']);
    }

    public function settings(RegisterPanelRequest $request): RedirectResponse
    {
        app(RegisterSettings::class)->save($request->user(), $request->validated());

        return redirect()->route('academic-registers.index')->with('success', __('Register settings saved'));
    }
}
