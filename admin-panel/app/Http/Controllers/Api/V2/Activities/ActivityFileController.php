<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\ActivityFileRequest;
use App\Services\Activities\ActivityFiles;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ActivityFileController extends Controller
{
    private function envelope(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => trans('data_fetch_successfully'), 'data' => $data, 'code' => 200]);
    }

    public function listing(ActivityFileRequest $request, int $group, int $material): JsonResponse
    {
        return $this->envelope(app(ActivityFiles::class)->listing($request->user(), $group, $material));
    }

    public function upload(ActivityFileRequest $request, int $group, int $material): JsonResponse
    {
        return $this->envelope(app(ActivityFiles::class)->upload($request->user(), $group, $material,
            $request->safe()->except('document'), $request->file('document')));
    }

    public function read(ActivityFileRequest $request, int $group, int $material, string $file): Response
    {
        $result = app(ActivityFiles::class)->read($request->user(), $group, $material, $file);
        $extension = $result['mime'] === 'application/pdf' ? 'pdf' : 'jpg';

        return response($result['bytes'], 200, ['Content-Type' => $result['mime'],
            'Content-Disposition' => 'attachment; filename="'.$file.'.'.$extension.'"',
            'Content-Security-Policy' => "sandbox; default-src 'none'"]);
    }

    public function remove(ActivityFileRequest $request, int $group, int $material, string $file): JsonResponse
    {
        return $this->envelope(app(ActivityFiles::class)->remove($request->user(), $group, $material, $file, $request->validated()));
    }
}
