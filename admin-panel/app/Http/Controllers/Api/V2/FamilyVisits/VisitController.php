<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\FamilyVisits;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyVisits\VisitRequest;
use App\Services\FamilyVisits\GatePasses;
use App\Services\FamilyVisits\Meetings;
use App\Services\FamilyVisits\VisitQueries;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function read(VisitRequest $request): JsonResponse
    {
        $queries = app(VisitQueries::class);
        $learner = $request->has('learner_id') ? (int) $request->validated('learner_id') : null;
        $page = (int) $request->validated('page', 1);

        return $this->result(match ($request->action()) {
            'configuration' => $queries->configuration($request->user()),
            'meetings.list' => $queries->meetings($request->user(), $learner, $page),
            'passes.list' => $queries->passes($request->user(), $learner, $page),
            'device.lookup' => app(GatePasses::class)->lookup($request->bearerToken() ?? '', (string) $request->route('scan')),
        });
    }

    public function write(VisitRequest $request): JsonResponse
    {
        $meetings = app(Meetings::class);
        $passes = app(GatePasses::class);
        $input = $request->validated();
        $slot = (string) $request->route('slot');
        $pass = (string) $request->route('pass');

        return $this->result(match ($request->action()) {
            'meetings.create' => $meetings->create($request->user(), $input),
            'meetings.book' => $meetings->book($request->user(), $slot, $input),
            'meetings.unbook' => $meetings->unbook($request->user(), $slot, $input),
            'meetings.cancel' => $meetings->cancel($request->user(), $slot, $input),
            'passes.request' => $passes->request($request->user(), $input),
            'passes.approve' => $passes->approve($request->user(), $pass, $input),
            'passes.cancel' => $passes->cancel($request->user(), $pass, $input),
            'rules.save' => app(VisitQueries::class)->changeRules($request->user(), $input),
            'device.collect' => $passes->collect($request->bearerToken() ?? '', $pass, $input),
        });
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Visit request completed.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
