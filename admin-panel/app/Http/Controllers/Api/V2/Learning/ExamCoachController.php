<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\ExamCoachRequest;
use App\Services\Learning\ExamCoachDates;
use App\Services\Learning\ExamCoachQueries;
use App\Services\Learning\ExamStudyPlans;
use App\Services\Learning\PastPaperLibrary;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExamCoachController extends Controller
{
    public function configuration(ExamCoachRequest $r): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamCoachQueries::class)->configuration($r->user()));
    }

    public function choices(ExamCoachRequest $r, int $group): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamCoachQueries::class)->choices($r->user(), $group));
    }

    public function staffPapers(ExamCoachRequest $r, int $group): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamCoachQueries::class)->papers($r->user(), $group, true, (int) $r->validated('page', 1)));
    }

    public function learnerPapers(ExamCoachRequest $r, int $group): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamCoachQueries::class)->papers($r->user(), $group, false, (int) $r->validated('page', 1)));
    }

    public function draft(ExamCoachRequest $r, int $group, ?int $paper = null): JsonResponse
    {
        return ResponseService::successResponse(data: app(PastPaperLibrary::class)->draft($r->user(), $group, $paper, $r->all()));
    }

    public function publish(ExamCoachRequest $r, int $group, int $paper, string $version): JsonResponse
    {
        return ResponseService::successResponse(data: app(PastPaperLibrary::class)->publish($r->user(), $group, $paper, $version, $r->all()));
    }

    public function withdraw(ExamCoachRequest $r, int $group, int $paper): JsonResponse
    {
        return ResponseService::successResponse(data: app(PastPaperLibrary::class)->withdraw($r->user(), $group, $paper, $r->all()));
    }

    public function staffVersion(ExamCoachRequest $r, int $group, string $version): JsonResponse
    {
        return ResponseService::successResponse(data: app(PastPaperLibrary::class)->data($r->user(), $group, $version, true));
    }

    public function learnerVersion(ExamCoachRequest $r, int $group, string $version): JsonResponse
    {
        return ResponseService::successResponse(data: app(PastPaperLibrary::class)->data($r->user(), $group, $version));
    }

    private function pdf(ExamCoachRequest $r, int $group, string $version, bool $staff, bool $memo): Response
    {
        $bytes = app(PastPaperLibrary::class)->document($r->user(), $group, $version, $memo, $staff);

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="exam-'.($memo ? 'memo' : 'paper').'.pdf"']);
    }

    public function learnerPaper(ExamCoachRequest $r, int $group, string $version): Response
    {
        return $this->pdf($r, $group, $version, false, false);
    }

    public function staffPaper(ExamCoachRequest $r, int $group, string $version): Response
    {
        return $this->pdf($r, $group, $version, true, false);
    }

    public function staffMemo(ExamCoachRequest $r, int $group, string $version): Response
    {
        return $this->pdf($r, $group, $version, true, true);
    }

    public function dates(ExamCoachRequest $r, int $group): JsonResponse
    {
        return ResponseService::successResponse(data: ['group_id' => $group, 'exams' => app(ExamCoachDates::class)->choices($r->user(), $group)]);
    }

    public function createPlan(ExamCoachRequest $r, int $group): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamStudyPlans::class)->create($r->user(), $group, $r->all()));
    }

    public function plans(ExamCoachRequest $r): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamCoachQueries::class)->plans($r->user(), (int) $r->validated('page', 1)));
    }

    public function plan(ExamCoachRequest $r, string $plan): JsonResponse
    {
        return ResponseService::successResponse(data: app(ExamStudyPlans::class)->read($r->user(), $plan));
    }
}
