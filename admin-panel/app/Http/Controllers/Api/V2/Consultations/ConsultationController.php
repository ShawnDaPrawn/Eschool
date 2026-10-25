<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Consultations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\ConsultationRequest;
use App\Services\Consultations\ConsultationQueries;
use App\Services\Consultations\Consultations;
use Illuminate\Http\JsonResponse;

class ConsultationController extends Controller
{
    public function read(ConsultationRequest $request): JsonResponse
    {
        $queries = app(ConsultationQueries::class);
        $service = app(Consultations::class);
        $actor = $request->user();
        $id = (int) $request->route('consultation');
        $page = (int) $request->validated('page', 1);
        $year = (int) ($request->validated('session_year_id') ?? schoolSetting('session_year'));
        $data = match ($request->action()) {
            'access' => $queries->access($actor),
            'mine' => $queries->listing($actor, $year, $page), 'managed' => $queries->listing($actor, $year, $page, true),
            'audiences' => $queries->audiences($actor, $year, $request->validated('kind'), $request->validated('search') ?? '', $page, $request->validated('selected') === null ? null : (int) $request->validated('selected')),
            'ballot' => $service->ballot($actor, $id), 'configuration' => $service->configuration($actor, $id),
            'results' => $service->results($actor, $id), 'manager-results' => $service->results($actor, $id, true),
            'responses' => $service->responses($actor, $id, $page), 'history' => $queries->history($actor, $id, $page),
            'history-entry' => $queries->historyEntry($actor, $id, (int) $request->route('event')),
        };

        return $this->result($data);
    }

    public function write(ConsultationRequest $request): JsonResponse
    {
        $service = app(Consultations::class);
        $actor = $request->user();
        $id = (int) $request->route('consultation');
        $data = $request->validated();

        return $this->result(match ($request->action()) {
            'create' => $service->save($actor, null, $data), 'edit' => $service->save($actor, $id, $data),
            'publish' => $service->publish($actor, $id, $data), 'close', 'cancel' => $service->end($actor, $id, $request->action(), $data),
            'vote' => $service->vote($actor, $id, $data['answers']),
        });
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Consultation request completed.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
