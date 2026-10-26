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

        return response()->view('classroom.management', compact('data'));
    }

    public function write(FoundationRequest $request): Response|RedirectResponse
    {
        $result = app(FoundationController::class)->staffResult($request);
        $action = $request->route('foundation_action');
        if (in_array($action, ['register', 'grant'], true)) {
            // A secret is displayed only in this private response: never session flash.
            return response()->view('classroom.authorization', ['result' => $result, 'registration' => $action === 'register']);
        }

        return redirect()->route('foundation-panel.index');
    }
}
