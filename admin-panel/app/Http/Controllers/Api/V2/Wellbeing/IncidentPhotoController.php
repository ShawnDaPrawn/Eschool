<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Wellbeing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\IncidentPhotoRequest;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\IncidentPhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IncidentPhotoController extends Controller
{
    /** Anonymous transport must never consult the ambient authenticated user. */
    public function publicPhoto(IncidentPhotoRequest $request, string $schoolSlug): JsonResponse|Response
    {
        $school = School::active()->where('slug', $schoolSlug)->firstOrFail();

        return app(CurrentSchool::class)->run($school, fn () => $this->reporter($request, false));
    }

    public function own(IncidentPhotoRequest $request): JsonResponse|Response
    {
        return $this->reporter($request, true);
    }

    private function reporter(IncidentPhotoRequest $request, bool $identified): JsonResponse|Response
    {
        $input = $request->validated();
        $photos = app(IncidentPhotos::class);
        $actor = $identified ? $request->user() : null;
        if (str_ends_with((string) $request->route()->getName(), 'photo-read')) {
            return $this->download($photos->reporterDownload($input['secret'], $input['photo_id'], $actor));
        }
        $data = $photos->upload($input['secret'], $input['photo'], $input['operation_id'], $actor);

        return response()->json(['error' => false, 'message' => 'Private photo received.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }

    public function staff(IncidentPhotoRequest $request, int $report, string $photo): Response
    {
        return $this->download(app(IncidentPhotos::class)->staffDownload($request->user(), $report, $photo));
    }

    private function download(array $file): Response
    {
        return response($file['bytes'], 200, ['Content-Type' => $file['mime'], 'Content-Disposition' => 'attachment; filename="report-photo.jpg"',
            'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Content-Type-Options' => 'nosniff']);
    }
}
