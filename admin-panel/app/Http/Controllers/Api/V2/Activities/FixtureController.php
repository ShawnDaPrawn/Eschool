<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\FixtureRequest;
use App\Services\Activities\Fixtures\Competitions;
use App\Services\Activities\Fixtures\Fixtures;
use Illuminate\Http\JsonResponse;

/** /api/v2/activities/fixtures and /competitions: fixtures and leagues between schools (Action Plan 5.2). */
class FixtureController extends Controller
{
    private function response(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => trans('data_fetch_successfully'), 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function index(FixtureRequest $r): JsonResponse
    {
        return $this->response(app(Fixtures::class)->listing($r->user(), (int) $r->validated('page', 1)));
    }

    public function schools(FixtureRequest $r): JsonResponse
    {
        return $this->response(app(Fixtures::class)->schools($r->user(), $r->validated()));
    }

    public function create(FixtureRequest $r): JsonResponse
    {
        return $this->response(app(Fixtures::class)->create($r->user(), $r->validated()));
    }

    public function show(FixtureRequest $r, int $fixture): JsonResponse
    {
        return $this->response(app(Fixtures::class)->view($r->user(), $fixture));
    }

    public function schedule(FixtureRequest $r, int $fixture): JsonResponse
    {
        return $this->response(app(Fixtures::class)->schedule($r->user(), $fixture, $r->validated()));
    }

    public function decision(FixtureRequest $r, int $fixture): JsonResponse
    {
        return $this->response(app(Fixtures::class)->decide($r->user(), $fixture, $r->validated()));
    }

    public function side(FixtureRequest $r, int $fixture): JsonResponse
    {
        return $this->response(app(Fixtures::class)->saveSide($r->user(), $fixture, $r->validated()));
    }

    public function result(FixtureRequest $r, int $fixture): JsonResponse
    {
        return $this->response(app(Fixtures::class)->report($r->user(), $fixture, $r->validated()));
    }

    public function learner(FixtureRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(Fixtures::class)->forLearner($r->user(), $learner));
    }

    public function competitions(FixtureRequest $r): JsonResponse
    {
        return $this->response(app(Competitions::class)->listing($r->user()));
    }

    public function competitionCreate(FixtureRequest $r): JsonResponse
    {
        return $this->response(app(Competitions::class)->create($r->user(), $r->validated()));
    }

    public function competition(FixtureRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(Competitions::class)->view($r->user(), $competition));
    }

    public function competitionInvite(FixtureRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(Competitions::class)->invite($r->user(), $competition, $r->validated()));
    }

    public function competitionDecision(FixtureRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(Competitions::class)->decide($r->user(), $competition, $r->validated()));
    }
}
