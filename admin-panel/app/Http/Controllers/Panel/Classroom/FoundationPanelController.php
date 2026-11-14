<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Classroom;

use App\Http\Controllers\Api\V2\Classroom\FoundationController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classroom\FoundationRequest;
use App\Services\Panel\SessionYearContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class FoundationPanelController extends Controller
{
    public function index(FoundationRequest $request): Response
    {
        $data = app(FoundationController::class)->configurationData($request->user(), (int) $request->validated('page', 1));
        $data['writable'] = $data['writable'] && ! app(SessionYearContext::class)->isReadOnly();

        $view = (string) $request->validated('view', 'lessons');
        abort_if(in_array($view, ['eligibility', 'register'], true) && ! $data['can_manage'], 403);
        abort_if($view === 'authorize' && ! $data['can_grant'], 403);
        $selectedGroup = null;
        if ($request->filled('group')) {
            abort_unless(in_array($view, ['eligibility', 'authorize'], true), 422);
            $selectedGroup = collect($data['groups'])->firstWhere('id', (int) $request->validated('group'));
            abort_unless($selectedGroup, 404);
            abort_if($view === 'authorize' && (! $selectedGroup['eligible'] || ! $selectedGroup['can_authorize']), 404);
        }

        return response()->view('classroom.management', compact('data', 'view', 'selectedGroup'));
    }

    public function write(FoundationRequest $request): Response|RedirectResponse
    {
        $result = app(FoundationController::class)->staffResult($request);
        $action = $request->route('foundation_action');
        if (in_array($action, ['register', 'grant'], true)) {
            // A secret is displayed only in this private response: never session flash.
            return response()->view('classroom.authorization', ['result' => $result, 'registration' => $action === 'register']);
        }

        if ($action === 'eligibility') {
            return redirect()->route('foundation-panel.index', ['view' => 'eligibility', 'group' => (int) $request->route('group'), 'page' => (int) $request->validated('return_page', 1)]);
        }

        return redirect()->route('foundation-panel.index', ['view' => match ($action) {
            'device-revoke' => 'devices', default => 'lessons',
        }]);
    }
}
