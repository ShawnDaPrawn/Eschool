<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TransitionRequest;
use App\Http\Resources\Academics\TransitionResource;
use App\Policies\Academics\TransitionPolicy;
use App\Services\Academics\Transitions\ExitArtifacts;
use App\Services\Academics\Transitions\ExitPackages;
use App\Services\Academics\Transitions\TransitionFlow;
use App\Services\Academics\Transitions\TransitionPolicies;
use App\Services\Academics\Transitions\TransitionQueries;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TransitionController extends Controller
{
    private function response(TransitionRequest $r, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new TransitionResource($data))->resolve($r));
    }

    public function configuration(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(TransitionQueries::class)->configuration($r->user(), $r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id')));
    }

    public function learners(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(TransitionQueries::class)->search($r->user(), (string) $r->validated('search', ''), (int) $r->validated('page', 1)));
    }

    public function learner(TransitionRequest $r, int $learner): JsonResponse
    {
        return $this->response($r, app(TransitionQueries::class)->learner($r->user(), $learner, (int) $r->validated('page', 1)));
    }

    public function preview(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(TransitionFlow::class)->preview($r->user(), $r->validated()));
    }

    public function commit(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(TransitionFlow::class)->commit($r->user(), $r->validated()));
    }

    public function policy(TransitionRequest $r, int $grade): JsonResponse
    {
        return $this->response($r, app(TransitionPolicies::class)->save($r->user(), $grade, $r->validated()));
    }

    public function retention(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(TransitionPolicies::class)->saveRetention($r->user(), $r->validated()));
    }

    public function exportPreview(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(ExitPackages::class)->preview($r->user(), $r->validated()));
    }

    public function exportPrepare(TransitionRequest $r): JsonResponse
    {
        return $this->response($r, app(ExitPackages::class)->prepare($r->user(), $r->validated()));
    }

    public function exportStatus(TransitionRequest $r, int $package): JsonResponse
    {
        return $this->response($r, app(ExitPackages::class)->data($r->user(), app(TransitionPolicy::class)->package($r->user(), $package)));
    }

    public function exportRetry(TransitionRequest $r, int $package): JsonResponse
    {
        return $this->response($r, app(ExitPackages::class)->retry($r->user(), $package));
    }

    public function exportDownload(TransitionRequest $r, int $package): Response
    {
        return response(app(ExitArtifacts::class)->read($r->user(), $package), 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="academic-exit-'.$package.'.zip"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }
}
