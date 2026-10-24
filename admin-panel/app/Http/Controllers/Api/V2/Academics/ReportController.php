<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ReportRequest;
use App\Http\Resources\Academics\ReportResource;
use App\Models\Academics\ReportCard;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\Academics\Reports\ReportBatches;
use App\Services\Academics\Reports\ReportDesigns;
use App\Services\Academics\Reports\ReportFamily;
use App\Services\Academics\Reports\ReportQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    private function response(ReportRequest $request, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new ReportResource($data))->resolve($request));
    }

    public function configuration(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportQueries::class)->configuration($request->user(), $request->validated('session_year_id') === null ? null : (int) $request->validated('session_year_id')));
    }

    public function roster(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportQueries::class)->roster($request->user(), (int) $request->validated('session_year_id'), (int) $request->validated('class_section_id'), $request->validated('term_id') === null ? null : (int) $request->validated('term_id')));
    }

    public function templates(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportDesigns::class)->save($request->user(), $request->validated()));
    }

    public function banks(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportDesigns::class)->bank($request->user(), $request->validated()));
    }

    public function preview(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->preview($request->user(), $request->validated()));
    }

    public function prepare(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->prepare($request->user(), $request->validated()));
    }

    public function batches(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportQueries::class)->batches($request->user(), (int) $request->validated('session_year_id'), (int) $request->validated('page', 1)));
    }

    public function show(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportQueries::class)->show($request->user(), $batch));
    }

    public function source(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->sourcePreview($request->user(), $batch));
    }

    public function refresh(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->refresh($request->user(), $batch, $request->validated()));
    }

    public function grade(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->approve($request->user(), $batch, 'grade_head', $request->validated()));
    }

    public function principal(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->approve($request->user(), $batch, 'principal', $request->validated()));
    }

    public function releasePreview(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->releasePreview($request->user(), $batch));
    }

    public function release(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->release($request->user(), $batch, $request->validated()));
    }

    public function retry(ReportRequest $request, int $batch): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->retry($request->user(), $batch, $request->validated()));
    }

    public function comments(ReportRequest $request): JsonResponse
    {
        return $this->response($request, app(ReportQueries::class)->comments($request->user(), (int) $request->validated('session_year_id'), (int) $request->validated('page', 1)));
    }

    public function comment(ReportRequest $request, int $card): JsonResponse
    {
        return $this->response($request, app(ReportBatches::class)->comment($request->user(), $card, $request->validated()));
    }

    public function family(ReportRequest $request, int $learner): JsonResponse
    {
        return $this->response($request, app(ReportFamily::class)->list($request->user(), $learner, (int) $request->validated('page', 1)));
    }

    public function access(ReportRequest $request, int $card): JsonResponse
    {
        return $this->response($request, app(ReportFamily::class)->access($request->user(), $card));
    }

    public function read(ReportRequest $request, int $card): JsonResponse
    {
        return $this->response($request, app(ReportFamily::class)->read($request->user(), $card));
    }

    public function download(ReportRequest $request, int $card): Response
    {
        abort_unless($request->hasValidSignature() && (int) $request->query('school') === app(CurrentSchool::class)->id() && (int) $request->query('reader') === (int) $request->user()->id, 403);
        if ((int) $request->query('staff') === 1) {
            $row = ReportCard::with('batch')->findOrFail($card);
            app(ReportPolicy::class)->batch($request->user(), (int) $row->report_batch_id);
        } else {
            $row = app(ReportPolicy::class)->family($request->user(), $card);
        }

        return response(app(ReportArtifacts::class)->read($row), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="report-'.$card.'.pdf"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }
}
