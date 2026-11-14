<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\AcademicContestRequest;
use App\Services\Activities\Fixtures\AcademicAwards;
use App\Services\Activities\Fixtures\AcademicContests;
use App\Services\Activities\Fixtures\ReasoningReviews;
use Illuminate\Http\JsonResponse;

/**
 * /api/v2/activities/academic-competitions: debates, quizzes and speech
 * contests between schools (Action Plan 5.3). Answering an invitation and
 * keeping the team sheet of entrants use the fixture endpoints.
 */
class AcademicContestController extends Controller
{
    private function response(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => trans('data_fetch_successfully'), 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function index(AcademicContestRequest $r): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->listing($r->user(), (int) $r->validated('page', 1)));
    }

    public function create(AcademicContestRequest $r): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->create($r->user(), $r->validated()));
    }

    public function show(AcademicContestRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->view($r->user(), $competition));
    }

    public function details(AcademicContestRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->details($r->user(), $competition, $r->validated()));
    }

    public function adjudicators(AcademicContestRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->adjudicators($r->user(), $competition, $r->validated()));
    }

    public function result(AcademicContestRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->result($r->user(), $competition, $r->validated()));
    }

    public function review(AcademicContestRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(ReasoningReviews::class)->request($r->user(), $competition, $r->validated()));
    }

    public function reviewShow(AcademicContestRequest $r, int $competition, int $review): JsonResponse
    {
        return $this->response(app(ReasoningReviews::class)->show($r->user(), $competition, $review));
    }

    public function share(AcademicContestRequest $r, int $competition, int $review): JsonResponse
    {
        return $this->response(app(ReasoningReviews::class)->share($r->user(), $competition, $review, $r->validated()));
    }

    public function awards(AcademicContestRequest $r, int $competition): JsonResponse
    {
        return $this->response(app(AcademicAwards::class)->link($r->user(), $competition, $r->validated()));
    }

    public function learner(AcademicContestRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(AcademicContests::class)->forLearner($r->user(), $learner));
    }
}
