<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning\Digests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Digests\DigestRequest;
use App\Services\Learning\Digests\DigestQueries;
use App\Services\Learning\Digests\DigestTemplates;
use Illuminate\Http\JsonResponse;

class DigestController extends Controller
{
    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => '', 'data' => $data, 'code' => 200]);
    }

    public function access(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestQueries::class)->access($r->user()));
    }

    public function list(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestQueries::class)->list($r->user(), (int) $r->route('learner'), $r->filled('before') ? (int) $r->input('before') : null));
    }

    public function show(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestQueries::class)->show($r->user(), (int) $r->route('digest')));
    }

    public function preferences(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestQueries::class)->preferences($r->user(), $r->validated()));
    }

    public function templates(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestQueries::class)->templates($r->user()));
    }

    public function createTemplate(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestTemplates::class)->create($r->user(), $r->validated()));
    }

    public function reviewTemplate(DigestRequest $r): JsonResponse
    {
        return $this->result(app(DigestTemplates::class)->review($r->user(), (int) $r->route('template'), $r->validated()));
    }
}
