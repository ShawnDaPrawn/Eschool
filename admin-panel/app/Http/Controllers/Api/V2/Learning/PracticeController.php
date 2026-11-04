<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\PracticeRequest;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\PracticeAccess;
use App\Services\Learning\PracticeBank;
use App\Services\Learning\PracticeChoices;
use App\Services\Learning\PracticeContract;
use App\Services\Learning\PracticeMarking;
use App\Services\Learning\PracticeMedia;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
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
        return $this->result(fn () => app(PracticeBank::class)->catalogue($r->user(), $group, (int) $r->validated('page', 1), $this->contract($r)));
    }

    public function approve(PracticeRequest $r, int $group, ?int $item = null): JsonResponse
    {
        return $this->result(fn () => app(PracticeBank::class)->approve($r->user(), $group, $item, $r->all()));
    }

    public function issue(PracticeRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result(fn () => app(DailyPractice::class)->issue($r->user(), $learner, $group, $r->all(), [], $this->contract($r)));
    }

    public function read(PracticeRequest $r, int $learner, int $set): JsonResponse
    {
        return $this->result(fn () => app(DailyPractice::class)->read($r->user(), $learner, $set, $this->contract($r)));
    }

    public function review(PracticeRequest $r, int $learner, int $set, int $entry): JsonResponse
    {
        return $this->result(fn () => app(DailyPractice::class)->review($r->user(), $learner, $set, $entry, $r->except('audio'), $r->file('audio'), $this->contract($r)));
    }

    /** A labelling question's diagram picture, for the learner whose set it is (Action Plan 3.11). */
    public function entryDiagram(PracticeRequest $r, int $learner, int $set, int $entry): Response
    {
        return $this->bytes(app(DailyPractice::class)->diagram($r->user(), $learner, $set, $entry), 'image/jpeg');
    }

    /** A teacher uploads a diagram picture for a labelling question in one of their groups. */
    public function diagram(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(function () use ($r, $group) {
            app(PracticeAccess::class)->staff($r->user(), $group, true);
            $file = $r->file('photo');
            Validator::make(['photo' => $file], PracticeMedia::diagramRules())->validate();
            $row = app(PracticeMedia::class)->storeSchoolDiagram($r->user(), $file);

            return ['id' => (string) $row->id, 'width' => (int) $row->width, 'height' => (int) $row->height];
        });
    }

    public function marking(PracticeRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(PracticeMarking::class)->queue($r->user(), $group, (int) $r->validated('page', 1)));
    }

    public function markingAudio(PracticeRequest $r, int $group, int $review): Response
    {
        $audio = app(PracticeMarking::class)->audio($r->user(), $group, $review);

        return $this->bytes($audio['bytes'], $audio['mime']);
    }

    public function mark(PracticeRequest $r, int $group, int $review): JsonResponse
    {
        return $this->result(fn () => app(PracticeMarking::class)->mark($r->user(), $group, $review, $r->all()));
    }

    /** `X-Practice-Contract: 2` opts in to answer-free sets (Action Plan 3.11); no header is the legacy contract. */
    private function contract(PracticeRequest $r): int
    {
        return app(PracticeContract::class)->fromRequest($r);
    }

    private function bytes(string $bytes, string $mime): Response
    {
        return response($bytes, 200, ['Content-Type' => $mime, 'Content-Length' => (string) strlen($bytes), 'Content-Disposition' => 'inline', 'Cache-Control' => 'private, no-store']);
    }
}
