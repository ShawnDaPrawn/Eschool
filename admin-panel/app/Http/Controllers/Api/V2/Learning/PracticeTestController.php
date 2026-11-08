<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\PracticeRequest;
use App\Services\Learning\Tests\PracticeTests;
use App\Services\Learning\Tests\TestBlueprints;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/** Practice tests (Action Plan 3.17): teachers set cycle tests; learners sit timed tests. */
class PracticeTestController extends Controller
{
    private function result(callable $action): JsonResponse
    {
        try {
            return ResponseService::successResponse(data: $action())->header('Cache-Control', 'private, no-store');
        } catch (\InvalidArgumentException $error) {
            throw ValidationException::withMessages(['practice_test' => $error->getMessage()]);
        }
    }

    public function options(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(TestBlueprints::class)->options($r->user(), $group));
    }

    public function blueprints(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(TestBlueprints::class)->index($r->user(), $group, (int) $r->validated('page', 1)));
    }

    public function create(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(TestBlueprints::class)->create($r->user(), $group, $r->all()));
    }

    public function results(PracticeRequest $r, int $group, int $blueprint): JsonResponse
    {
        return $this->result(fn () => app(TestBlueprints::class)->results($r->user(), $group, $blueprint));
    }

    public function withdraw(PracticeRequest $r, int $group, int $blueprint): JsonResponse
    {
        return $this->result(fn () => app(TestBlueprints::class)->withdraw($r->user(), $group, $blueprint, $r->all()));
    }

    public function tests(PracticeRequest $r, int $learner): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->index($r->user(), $learner));
    }

    public function quizTopics(PracticeRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->quizTopics($r->user(), $learner, $group));
    }

    public function quickQuiz(PracticeRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->quickQuiz($r->user(), $learner, $group, $r->all()));
    }

    public function start(PracticeRequest $r, int $learner, int $blueprint): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->start($r->user(), $learner, $blueprint, $r->all()));
    }

    public function show(PracticeRequest $r, int $learner, int $test): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->read($r->user(), $learner, $test));
    }

    public function answers(PracticeRequest $r, int $learner, int $test): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->save($r->user(), $learner, $test, $r->all()));
    }

    public function submit(PracticeRequest $r, int $learner, int $test): JsonResponse
    {
        return $this->result(fn () => app(PracticeTests::class)->submit($r->user(), $learner, $test, $r->all()));
    }
}
