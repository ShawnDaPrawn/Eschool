<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ContentRequest;
use App\Http\Resources\Academics\ContentResource;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\Content\ContentFlow;
use App\Services\Academics\Content\ContentQueries;
use App\Services\Academics\Content\ContentSettings;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    private function response(ContentRequest $r, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new ContentResource($data))->resolve($r));
    }

    public function configuration(ContentRequest $r): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->configuration($r->user(), $r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id')));
    }

    public function index(ContentRequest $r): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->staff($r->user(), $r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id'), (int) $r->validated('page', 1)));
    }

    public function show(ContentRequest $r, int $item): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->show($r->user(), $item, (int) $r->validated('page', 1)));
    }

    public function version(ContentRequest $r, int $version): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->versionData(app(ContentPolicy::class)->version($r->user(), $version)));
    }

    public function create(ContentRequest $r): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->create($r->user(), $r->selectionInput(), $r->file('source')));
    }

    public function upload(ContentRequest $r, int $item): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->upload($r->user(), $item, $r->selectionInput(), $r->file('source')));
    }

    public function save(ContentRequest $r, int $version): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->save($r->user(), $version, $r->selectionInput()));
    }

    public function retry(ContentRequest $r, int $version): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->retry($r->user(), $version, $r->validated()));
    }

    public function preview(ContentRequest $r, int $version): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->preview($r->user(), $version));
    }

    public function publish(ContentRequest $r, int $version): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->publish($r->user(), $version, $r->validated()));
    }

    public function archive(ContentRequest $r, int $item): JsonResponse
    {
        return $this->response($r, app(ContentFlow::class)->archive($r->user(), $item, $r->validated()));
    }

    public function settings(ContentRequest $r): JsonResponse
    {
        return $this->response($r, app(ContentSettings::class)->save($r->user(), $r->validated()));
    }

    public function library(ContentRequest $r, int $learner): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->library($r->user(), $learner, (int) $r->validated('page', 1)));
    }

    public function reader(ContentRequest $r, int $learner, int $version): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->reader($r->user(), $learner, $version));
    }

    public function read(ContentRequest $r, int $learner, int $version): JsonResponse
    {
        return $this->response($r, app(ContentQueries::class)->read($r->user(), $learner, $version));
    }
}
