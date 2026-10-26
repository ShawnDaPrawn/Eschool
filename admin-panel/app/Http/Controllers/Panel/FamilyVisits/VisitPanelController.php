<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\FamilyVisits;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyVisits\VisitPanelRequest;
use App\Services\FamilyVisits\GatePasses;
use App\Services\FamilyVisits\Meetings;
use App\Services\FamilyVisits\VisitPanelScope;
use App\Services\FamilyVisits\VisitQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VisitPanelController extends Controller
{
    public function index(VisitPanelRequest $request): Response
    {
        return $this->failure($request, [], 200);
    }

    public function failure(VisitPanelRequest $request, array $messages, int $status): Response
    {
        app(VisitPanelScope::class)->authorize($request);
        $access = app(VisitQueries::class)->configuration($request->user());
        $meetings = app(VisitQueries::class)->meetings($request->user(), null, $request->integer('page', 1));
        $passes = $access['can_manage'] ? app(VisitQueries::class)->passes($request->user(), null, $request->integer('pass_page', 1)) : ['items' => [], 'next_page' => null];
        $account = (int) $request->user()->id;
        $school = app(CurrentSchool::class)->id();

        return response()->view('family-visits.index', compact('access', 'meetings', 'passes', 'account', 'school', 'messages'), $status);
    }

    public function write(VisitPanelRequest $request): Response|RedirectResponse
    {
        try {
            $input = $request->validated();
            $id = (string) $request->validated('source_id', '');
            match ($request->route('operation')) {
                'meeting-create' => app(Meetings::class)->create($request->user(), $input),
                'meeting-cancel' => app(Meetings::class)->cancel($request->user(), $id, $input),
                'pass-approve' => app(GatePasses::class)->approve($request->user(), $id, $input),
                'pass-cancel' => app(GatePasses::class)->cancel($request->user(), $id, $input),
                'rules' => app(VisitQueries::class)->changeRules($request->user(), $input),
            };

            return redirect()->route('family-visits-panel.index');
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return $this->failure($request, [$error->getMessage() ?: __('Refresh the meeting or collection request.')], $error->getStatusCode());
        }
    }
}
