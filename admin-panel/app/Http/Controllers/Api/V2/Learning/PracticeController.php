<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\PracticeRequest;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\PracticeBank;
use App\Services\Learning\PracticeChoices;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PracticeController extends Controller
{
    private function result(callable $action): JsonResponse
    {
        try {
            return ResponseService::successResponse(data: $action())->header('Cache-Control', 'private, no-store');
        } catch (\InvalidArgumentException $error) {
            throw ValidationException::withMessages(['practice' => $error->getMessage()]);
        }
    }

    public function access(PracticeRequest $r): JsonResponse
    {
        return $this->result(fn () => app(PracticeChoices::class)->access($r->user()));
    }

    public function choices(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(PracticeChoices::class)->staff($r->user(), $group));
    }

    public function bank(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(PracticeBank::class)->catalogue($r->user(), $group, (int) $r->validated('page', 1)));
    }

    public function approve(PracticeRequest $r, int $group, ?int $item = null): JsonResponse
    {
        return $this->result(fn () => app(PracticeBank::class)->approve($r->user(), $group, $item, $r->all()));
    }

    public function issue(PracticeRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result(fn () => app(DailyPractice::class)->issue($r->user(), $learner, $group, $r->all()));
    }

    public function read(PracticeRequest $r, int $learner, int $set): JsonResponse
    {
        return $this->result(fn () => app(DailyPractice::class)->read($r->user(), $learner, $set));
    }

    public function review(PracticeRequest $r, int $learner, int $set, int $entry): JsonResponse
    {
        return $this->result(fn () => app(DailyPractice::class)->review($r->user(), $learner, $set, $entry, $r->all()));
    }
}
