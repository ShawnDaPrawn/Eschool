<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Wellbeing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\WellbeingRequest;
use App\Http\Resources\Wellbeing\WellbeingResource;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\IncidentPhotos;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Http\JsonResponse;

/** Stateless public adapter: no authenticated cookie/user is consulted or bound. */
class PublicReportingController extends Controller
{
    public function action(WellbeingRequest $request, string $schoolSlug): JsonResponse
    {
        $school = School::active()->where('slug', $schoolSlug)->firstOrFail();

        return app(CurrentSchool::class)->run($school, function () use ($request) {
            $data = $request->validated();
            $service = app(IncidentReporting::class);
            if ($request->action() === 'public.submit') {
                abort_unless($data['anonymous'] === true || $data['anonymous'] === 1, 422, 'Identify yourself only through your signed-in school account.');
            }
            $result = match ($request->action()) {
                'public.categories' => app(WellbeingSettings::class)->categories(),
                'public.submit' => $service->submit($data),
                'public.read' => $service->followUp($data['secret'], null, isset($data['before']) ? (int) $data['before'] : null),
                'public.reply' => $service->reply($data['secret'], $data),
            };

            if ($request->action() === 'public.read') {
                $result['photos'] = app(IncidentPhotos::class)->reporter($data['secret']);
            }

            return response()->json(['error' => false, 'message' => 'Private reporting request completed.', 'data' => (new WellbeingResource($result))->resolve($request), 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
        });
    }
}
