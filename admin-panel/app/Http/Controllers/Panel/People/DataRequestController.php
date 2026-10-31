<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\AnswerDataRequestRequest;
use App\Services\Platform\Privacy\DataRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** /data-requests: POPIA requests Navigate Ed passed to the school, and the school's answers (Action Plan SA.11). */
class DataRequestController extends Controller
{
    public function __construct(private readonly DataRequests $requests) {}

    public function index(Request $request): View
    {
        return view('people.data-requests', ['requests' => $this->requests->forSchool($request->user())]);
    }

    public function answer(AnswerDataRequestRequest $request, int $dataRequest): RedirectResponse
    {
        $this->requests->answer($request->user(), $dataRequest, $request->validated());

        return redirect()->route('data-requests.index')->with('success', __('Your answer has been recorded. Navigate Ed will close the request.'));
    }
}
