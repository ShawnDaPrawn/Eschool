<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Wellbeing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\WellbeingRequest;
use App\Http\Resources\Wellbeing\WellbeingResource;
use App\Services\Wellbeing\Casework;
use App\Services\Wellbeing\IncidentPhotos;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingQueries;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Http\JsonResponse;

class WellbeingController extends Controller
{
    public function read(WellbeingRequest $r): JsonResponse
    {
        $actor = $r->user();
        $queries = app(WellbeingQueries::class);
        $page = (int) $r->validated('page', 1);
        $before = $r->validated('before') === null ? null : (int) $r->validated('before');
        $data = match ($r->action()) {
            'access' => $queries->access($actor),
            'configuration' => $queries->configuration($actor, (string) $r->validated('search', ''), $page),
            'managed-cases' => $queries->managedCases($actor, $page),
            'team-assignments' => $queries->assignments($actor, 'team', (int) $r->route('category'), $page),
            'case-assignments' => $queries->assignments($actor, 'case', (int) $r->route('case'), $page),
            'reports' => $queries->reports($actor, $page),
            'report' => $queries->report($actor, (int) $r->route('report'), $before, true),
            'case-professionals' => $queries->caseProfessionals($actor, (int) $r->route('case'), $r->validated('on_date'), (string) $r->validated('search', ''), $page, $r->validated('selected') === null ? null : (int) $r->validated('selected')),
            'case-report-choices' => $queries->caseReportChoices($actor, (int) $r->route('case'), $page, $r->validated('selected') === null ? null : (int) $r->validated('selected')),
            'cases' => $queries->cases($actor, $page),
            'case' => $queries->case($actor, (int) $r->route('case'), $before),
            'appointments' => app(Casework::class)->appointments($actor, (int) $r->route('case'), $r->validated('from'), $r->validated('to')),
        };

        if ($r->action() === 'report') {
            $data['photos'] = app(IncidentPhotos::class)->staff($actor, (int) $r->route('report'));
        }

        return $this->response($r, $data);
    }

    public function write(WellbeingRequest $r): JsonResponse
    {
        $actor = $r->user();
        $input = $r->validated();
        $settings = app(WellbeingSettings::class);
        $reports = app(IncidentReporting::class);
        $cases = app(Casework::class);
        $id = $r->route('assignment') === null ? null : (int) $r->route('assignment');
        if ($r->action() === 'identified-submit') {
            abort_unless($input['anonymous'] === false || $input['anonymous'] === 0, 422, 'Use the anonymous reporting form to submit without identification.');
        }
        $data = match ($r->action()) {
            'category-create' => $settings->saveCategory($actor, null, $input),
            'category-save' => $settings->saveCategory($actor, (int) $r->route('category'), $input),
            'team-create', 'team-save' => $settings->assignTeam($actor, (int) $r->route('category'), $id, $input),
            'case-create' => $settings->openCase($actor, $input),
            'case-assignment-create', 'case-assignment-save' => $settings->assignCase($actor, (int) $r->route('case'), $id, $input),
            'identified-submit' => $reports->submit($input, $actor),
            'identified-read' => $reports->followUp($input['secret'], $actor, isset($input['before']) ? (int) $input['before'] : null),
            'identified-reply' => $reports->reply($input['secret'], $input, $actor),
            'identify' => $reports->identify($actor, $input['secret'], $input),
            'report-respond' => $reports->respond($actor, (int) $r->route('report'), $input),
            'case-entry' => $cases->entry($actor, (int) $r->route('case'), $input),
            'case-link' => $cases->linkIncident($actor, (int) $r->route('case'), $input),
            'case-status' => $cases->status($actor, (int) $r->route('case'), $input),
            'appointment-create' => $cases->appointment($actor, (int) $r->route('case'), null, $input),
            'appointment-save' => $cases->appointment($actor, (int) $r->route('case'), (int) $r->route('appointment'), $input),
        };

        if ($r->action() === 'identified-read') {
            $data['photos'] = app(IncidentPhotos::class)->reporter($input['secret'], $actor);
        }

        return $this->response($r, $data);
    }

    private function response(WellbeingRequest $r, array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Wellbeing request completed.', 'data' => (new WellbeingResource($data))->resolve($r), 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
