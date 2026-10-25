<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\FamilyForms;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyForms\FamilyFormRequest;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\FamilyFormQueries;
use App\Services\FamilyForms\HealthForms;
use Illuminate\Http\JsonResponse;

class FamilyFormController extends Controller
{
    public function read(FamilyFormRequest $request): JsonResponse
    {
        $actor = $request->user();
        $queries = app(FamilyFormQueries::class);
        $id = (string) $request->route('consent');
        $learner = (int) $request->route('learner', $request->validated('learner_id', 0));
        $page = (int) $request->validated('page', 1);

        return $this->result(match ($request->action()) {
            'configuration' => $queries->configuration($actor),
            'listing' => $queries->listing($actor, $request->has('learner_id') ? (int) $request->validated('learner_id') : null, $request->has('activity_group_id') ? (int) $request->validated('activity_group_id') : null, $page),
            'read' => app(Consents::class)->read($actor, $id, $learner),
            'managed' => $queries->managed($actor, $id, $page),
            'health.read' => app(HealthForms::class)->read($actor, $learner),
        });
    }

    public function write(FamilyFormRequest $request): JsonResponse
    {
        $actor = $request->user();
        $service = app(Consents::class);
        $id = (string) $request->route('consent');
        $learner = (int) $request->route('learner');

        return $this->result(match ($request->action()) {
            'create' => $service->create($actor, $request->validated()),
            'respond' => $service->respond($actor, $id, $learner, $request->validated()),
            'cancel' => $service->cancel($actor, $id, $request->validated()),
            'health.save' => app(HealthForms::class)->save($actor, $learner, $request->profileValues()),
        });
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'OK', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
