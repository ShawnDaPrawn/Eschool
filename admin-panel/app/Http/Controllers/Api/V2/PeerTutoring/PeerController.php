<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\PeerTutoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeerTutoring\PeerRequest;
use App\Services\PeerTutoring\PeerMatches;
use App\Services\PeerTutoring\PeerQueries;
use App\Services\PeerTutoring\PeerSessions;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\JsonResponse;

class PeerController extends Controller
{
    private function response(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'OK', 'code' => 200, 'data' => $data]);
    }

    public function configuration(PeerRequest $request): JsonResponse
    {
        return $this->response(app(PeerQueries::class)->configuration($request->user(), $request->persona()));
    }

    public function learners(PeerRequest $request, int $group): JsonResponse
    {
        return $this->response(['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id,
            'teaching_group_id' => $group, 'items' => app(PeerQueries::class)->roster($request->user(), $group)]);
    }

    public function matches(PeerRequest $request, int $group, int $learner): JsonResponse
    {
        return $this->response(['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id,
            'teaching_group_id' => $group, 'learner_id' => $learner, 'items' => app(PeerMatches::class)->forLearner($request->user(), $group, $learner)]);
    }

    public function index(PeerRequest $request): JsonResponse
    {
        return $this->response(app(PeerQueries::class)->listing($request->user(), $request->persona(), $request->integer('page', 1)));
    }

    public function show(PeerRequest $request, string $session): JsonResponse
    {
        return $this->response(['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id,
            'session' => app(PeerQueries::class)->read($request->user(), $session, $request->persona())]);
    }

    public function store(PeerRequest $request): JsonResponse
    {
        $row = app(PeerSessions::class)->create($request->user(), $request->safe()->except('persona'));

        return $this->response(['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id,
            'session' => app(PeerQueries::class)->read($request->user(), $row->id, 'staff')]);
    }

    public function complete(PeerRequest $request, string $session): JsonResponse
    {
        return $this->change($request, $session, 'complete');
    }

    public function cancel(PeerRequest $request, string $session): JsonResponse
    {
        return $this->change($request, $session, 'cancel');
    }

    public function reverse(PeerRequest $request, string $session): JsonResponse
    {
        return $this->change($request, $session, 'reverse');
    }

    private function change(PeerRequest $request, string $id, string $action): JsonResponse
    {
        $row = app(PeerSessions::class)->change($request->user(), $id, $action, $request->safe()->except('persona'));

        return $this->response(['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id,
            'session' => app(PeerQueries::class)->read($request->user(), $row->id, 'staff')]);
    }
}
