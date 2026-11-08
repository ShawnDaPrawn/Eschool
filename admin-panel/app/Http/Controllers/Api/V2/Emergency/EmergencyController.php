<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Emergency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emergency\EmergencyRequest;
use App\Services\Emergency\EmergencyAlerts;
use App\Services\Emergency\EmergencyQueries;
use App\Services\Emergency\EmergencyReunification;
use Illuminate\Http\JsonResponse;

/** Emergency alerts, roll call and reunification for the apps. engineering/specs/community/emergency-management.md. */
class EmergencyController extends Controller
{
    public function read(EmergencyRequest $request): JsonResponse
    {
        $alert = (string) $request->route('alert');

        return $this->result(match ($request->action()) {
            'status' => app(EmergencyQueries::class)->status($request->user(), $request->has('learner_id') ? (int) $request->validated('learner_id') : null),
            'roll-call.show' => app(EmergencyReunification::class)->rollCall($request->user(), $alert, $request->has('class_section_id') ? (int) $request->validated('class_section_id') : null),
        });
    }

    public function write(EmergencyRequest $request): JsonResponse
    {
        $alerts = app(EmergencyAlerts::class);
        $reunification = app(EmergencyReunification::class);
        $input = $request->validated();
        $alert = (string) $request->route('alert');

        return $this->result(match ($request->action()) {
            'alerts.prepare' => $alerts->prepare($request->user(), $input),
            'alerts.confirm' => $alerts->confirm($request->user(), $alert, $input),
            'alerts.all-clear' => $alerts->allClear($request->user(), $alert, $input),
            'roll-call.mark' => $reunification->mark($request->user(), $alert, $input),
            'releases.create' => $reunification->release($request->user(), $alert, $input),
        });
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Emergency request completed.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
