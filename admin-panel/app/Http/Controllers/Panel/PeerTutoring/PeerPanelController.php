<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\PeerTutoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeerTutoring\PeerPanelRequest;
use App\Services\PeerTutoring\PeerMatches;
use App\Services\PeerTutoring\PeerPanelScope;
use App\Services\PeerTutoring\PeerQueries;
use App\Services\PeerTutoring\PeerSessions;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PeerPanelController extends Controller
{
    public function index(PeerPanelRequest $request): Response
    {
        return $this->failure($request);
    }

    public function failure(PeerPanelRequest $request, array $messages = [], int $status = 200): Response
    {
        app(PeerPanelScope::class)->authorize($request);
        $selection = Validator::make($request->query->all(), ['group' => ['nullable', 'integer', 'min:1'], 'learner' => ['nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000']])->validate();
        $queries = app(PeerQueries::class);
        $configuration = $queries->configuration($request->user(), 'staff');
        $listing = $queries->listing($request->user(), 'staff', (int) ($selection['page'] ?? 1));
        $group = (int) ($selection['group'] ?? 0);
        $learner = (int) ($selection['learner'] ?? 0);
        $roster = $group > 0 ? $queries->roster($request->user(), $group) : [];
        $matches = $group > 0 && $learner > 0 ? app(PeerMatches::class)->forLearner($request->user(), $group, $learner) : [];
        $school = app(CurrentSchool::class)->id();
        $account = (int) $request->user()->id;

        return response()->view('peer-tutoring.index', compact('configuration', 'listing', 'group', 'learner', 'roster', 'matches', 'school', 'account', 'messages'), $status);
    }

    public function write(PeerPanelRequest $request): Response|RedirectResponse
    {
        app(PeerPanelScope::class)->authorize($request);
        $action = (string) $request->route('action');
        $input = $request->safe()->except(['expected_school_id', 'expected_account_id']);
        abort_if(array_diff(array_keys($input), array_keys(PeerSessions::rules($action))) !== [], 422);
        try {
            if ($action === 'create') {
                app(PeerSessions::class)->create($request->user(), $input);
            } else {
                app(PeerSessions::class)->change($request->user(), (string) $request->route('session'), $action, $input);
            }

            return redirect()->route('peer-tutoring-panel.index');
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($request, [$error->getMessage() ?: __('Refresh peer sessions before changing this record.')], $error->getStatusCode());
        }
    }
}
