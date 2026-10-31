<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\DataRequestRequest;
use App\Services\Platform\Privacy\DataRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: POPIA data subject requests that reach Navigate Ed (Action Plan SA.11). */
class PlatformDataRequestController extends Controller
{
    public function __construct(private readonly DataRequests $requests) {}

    public function index(Request $request): View
    {
        return view('saas.data-requests', $this->requests->list($request->user()) + ['schoolChoices' => $this->requests->schoolChoices($request->user())]);
    }

    public function store(DataRequestRequest $request): RedirectResponse
    {
        $id = $this->requests->log($request->user(), $request->validated());

        return redirect()->route('saas.data-requests.show', $id)->with('success', __('Request :reference logged.', ['reference' => DataRequests::reference($id)]));
    }

    public function show(Request $request, int $dataRequest): View
    {
        return view('saas.data-request', $this->requests->show($request->user(), $dataRequest));
    }

    public function pass(DataRequestRequest $request, int $dataRequest): RedirectResponse
    {
        $this->requests->pass($request->user(), $dataRequest);

        return redirect()->route('saas.data-requests.show', $dataRequest)->with('success', __('Passed to the school. Its Super Admins have been told.'));
    }

    public function close(DataRequestRequest $request, int $dataRequest): RedirectResponse
    {
        $this->requests->close($request->user(), $dataRequest, $request->validated());

        return redirect()->route('saas.data-requests.show', $dataRequest)->with('success', __('Request closed.'));
    }

    public function withdraw(DataRequestRequest $request, int $dataRequest): RedirectResponse
    {
        $this->requests->withdraw($request->user(), $dataRequest, $request->validated());

        return redirect()->route('saas.data-requests.show', $dataRequest)->with('success', __('Recorded as withdrawn.'));
    }
}
