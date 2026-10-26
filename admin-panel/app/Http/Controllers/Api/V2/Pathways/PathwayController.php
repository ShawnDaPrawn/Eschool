<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Pathways;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pathways\PathwayRequest;
use App\Services\Pathways\PathwayQueries;
use App\Services\Pathways\PathwayReferences;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\JsonResponse;

class PathwayController extends Controller
{
    public function configuration(PathwayRequest $request): JsonResponse
    {
        return $this->result(app(PathwayQueries::class)->configuration($request->user()));
    }

    public function index(PathwayRequest $request): JsonResponse
    {
        return $this->result(app(PathwayQueries::class)->catalogue($request->user(), (int) $request->validated('page', 1), $request->boolean('manage')));
    }

    public function show(PathwayRequest $request): JsonResponse
    {
        $data = app(PathwayQueries::class)->read($request->user(), (string) $request->route('reference'), $request->boolean('manage'));

        return $this->result(['school_id' => $data['school_id'], 'reader_id' => $data['reader_id'], 'reference' => array_diff_key($data, ['school_id' => true, 'reader_id' => true])]);
    }

    public function store(PathwayRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(PathwayRequest $request): JsonResponse
    {
        return $this->save($request, (string) $request->route('reference'));
    }

    private function save(PathwayRequest $request, ?string $id): JsonResponse
    {
        $reference = app(PathwayReferences::class)->save($request->user(), $id, $request->validated());

        return $this->result(['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id, 'reference' => $reference]);
    }

    public function project(PathwayRequest $request): JsonResponse
    {
        return $this->result(app(PathwayQueries::class)->project($request->user(), (string) $request->route('reference'), $request->validated()));
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Pathway request completed.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
