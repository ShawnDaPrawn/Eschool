<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\TeachingDraftRequest;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Learning\TaskInsights;
use App\Services\Learning\TeachingDraftAccess;
use App\Services\Learning\TeachingDraftExports;
use App\Services\Learning\TeachingDraftOperations;
use App\Services\Learning\TeachingDraftPhotos;
use App\Services\Learning\TeachingDraftQueries;
use App\Services\Learning\TeachingDrafts;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TeachingDraftController extends Controller
{
    private function module(TeachingDraftRequest $r): string
    {
        return in_array($r->route()->getActionMethod(), ['insightConfiguration', 'insightChoices', 'insights'], true) ? 'test_insights' : 'teaching_assistant';
    }

    private function result(TeachingDraftRequest $r, callable $action): JsonResponse
    {
        $school = app(CurrentSchool::class)->id();
        try {
            $data = $action();
            app(TeachingDraftAccess::class)->actor($r->user(), $this->module($r));
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);

            return ResponseService::successResponse(data: array_merge($data, ['school_id' => $school, 'reader_id' => (int) $r->user()->id]));
        } catch (AiUnavailable $error) {
            $status = str_ends_with($error->reason, '_invalid') || str_ends_with($error->reason, '_required') ? 422 : 503;

            return ApiV2ExceptionRenderer::envelope($status, data: ['reason' => $error->reason]);
        }
    }

    public function configuration(TeachingDraftRequest $r): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->configuration($r->user()));
    }

    public function insightConfiguration(TeachingDraftRequest $r): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->configuration($r->user(), 'test_insights'));
    }

    public function choices(TeachingDraftRequest $r, int $group): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->choices($r->user(), $group));
    }

    public function insightChoices(TeachingDraftRequest $r, int $group): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->choices($r->user(), $group, 'test_insights'));
    }

    public function insights(TeachingDraftRequest $r, int $group, int $task): JsonResponse
    {
        return $this->result($r, fn () => app(TaskInsights::class)->read($r->user(), $group, $task));
    }

    public function learners(TeachingDraftRequest $r, int $group): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->learners($r->user(), $group, (int) $r->validated('page', 1)));
    }

    public function index(TeachingDraftRequest $r): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->drafts($r->user(), (int) $r->validated('group_id'), (int) $r->validated('page', 1)));
    }

    public function generate(TeachingDraftRequest $r, int $group): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDrafts::class)->generate($r->user(), $group, $r->validated()));
    }

    public function show(TeachingDraftRequest $r, string $draft): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDrafts::class)->view($r->user(), $draft));
    }

    public function receipt(TeachingDraftRequest $r, string $operation): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftQueries::class)->receipt($r->user(), $operation));
    }

    public function review(TeachingDraftRequest $r, string $draft): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDrafts::class)->change($r->user(), $draft, $r->validated()));
    }

    public function upload(TeachingDraftRequest $r, int $group): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftPhotos::class)->upload($r->user(), $group, (int) $r->validated('learner_id'), $r->validated('operation_id'), $r->file('photo'),
            ['x' => (int) $r->validated('crop_x'), 'y' => (int) $r->validated('crop_y'), 'width' => (int) $r->validated('crop_width'), 'height' => (int) $r->validated('crop_height')], true));
    }

    public function preview(TeachingDraftRequest $r, string $photo): Response
    {
        $image = app(TeachingDraftPhotos::class)->preview($r->user(), $photo);

        return response($image['bytes'], 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'inline; filename="script-working.png"', 'Cache-Control' => 'private, no-store']);
    }

    public function revoke(TeachingDraftRequest $r, string $photo): JsonResponse
    {
        return $this->result($r, fn () => app(TeachingDraftOperations::class)->run($r->user(), ['operation_id' => $r->validated('operation_id'), 'photo_id' => $photo, 'action' => 'revoke_photo'],
            fn () => app(TeachingDraftPhotos::class)->authorizeOwner($r->user(), $photo, true), function () use ($r, $photo) {
                app(TeachingDraftPhotos::class)->revoke($r->user(), $photo);

                return ['id' => $photo, 'revoked' => true, 'write_operation_id' => $r->validated('operation_id')];
            }));
    }

    public function pdf(TeachingDraftRequest $r, string $draft): Response
    {
        $bytes = app(TeachingDraftExports::class)->output($r->user(), $draft, $r->boolean('include_answers'));

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="reviewed-teaching-material.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
