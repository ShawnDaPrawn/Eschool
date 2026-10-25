<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\RiskRequest;
use App\Models\Growth\Intervention;
use App\Models\User;
use App\Services\Growth\Interventions;
use App\Services\Growth\RiskAuthority;
use App\Services\Growth\RiskOperations;
use App\Services\Growth\RiskQueries;
use App\Services\Growth\RiskSettingsService;
use App\Services\Growth\RiskViews;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class RiskController extends Controller
{
    public function configuration(RiskRequest $r): JsonResponse
    {
        return $this->response(app(RiskQueries::class)->configuration($r->user()));
    }

    public function signals(RiskRequest $r): JsonResponse
    {
        return $this->response(app(RiskViews::class)->list($r->user(), $r->year(), (int) $r->validated('page', 1)));
    }

    public function classroom(RiskRequest $r, int $group): JsonResponse
    {
        return $this->response(app(RiskViews::class)->classroom($r->user(), $group));
    }

    public function learners(RiskRequest $r): JsonResponse
    {
        return $this->response(app(RiskQueries::class)->learners($r->user(), $r->year(), (int) $r->validated('page', 1), $r->validated('search')));
    }

    public function professionals(RiskRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(RiskQueries::class)->professionals($r->user(), $learner, $r->year(), (int) $r->validated('page', 1), $r->validated('search')));
    }

    public function owners(RiskRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(RiskQueries::class)->owners($r->user(), $learner, $r->year(),
            $r->validated('intervention_id') === null ? null : (int) $r->validated('intervention_id'), (int) $r->validated('page', 1), $r->validated('search')));
    }

    public function assignments(RiskRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(RiskQueries::class)->assignments($r->user(), $learner, $r->year()));
    }

    public function interventions(RiskRequest $r): JsonResponse
    {
        return $this->response(app(RiskViews::class)->interventions($r->user(), $r->year(), (int) $r->validated('page', 1)));
    }

    public function detail(RiskRequest $r, int $intervention): JsonResponse
    {
        return $this->response(app(Interventions::class)->read($r->user(), $intervention));
    }

    public function history(RiskRequest $r, int $intervention): JsonResponse
    {
        return $this->response(['intervention_id' => $intervention, 'versions' => app(Interventions::class)->history($r->user(), $intervention)]);
    }

    public function create(RiskRequest $r, int $learner): JsonResponse
    {
        $data = $r->validated() + ['learner_id' => $learner];
        $a = app(RiskAuthority::class);
        $authority = function () use ($r, $learner, $data, $a) {
            $a->year($r->year(), true);
            $grade = $a->currentGrade($learner, $r->year());
            $a->caseActor($r->user(), $learner, $r->year(), $grade, true);
            if ((int) $data['owner_id'] !== (int) $r->user()->id) {
                $a->administer($r->user(), $learner, $r->year());
            }
            $a->caseActor(User::findOrFail($data['owner_id']), $learner, $r->year(), $grade, true, $data['owner_authority'] ?? null);
        };

        return $this->response(app(RiskOperations::class)->once($r->user(), 'intervention.create', $data, $authority,
            fn () => app(Interventions::class)->create($r->user(), $learner, $r->year(), $data),
            fn (array $receipt) => $a->caseActor(User::findOrFail($receipt['owner_id']), $learner, $r->year(), (int) $receipt['grade_level_id'], true, $receipt['owner_authority'])));
    }

    public function update(RiskRequest $r, int $intervention): JsonResponse
    {
        $data = $r->validated() + ['intervention_id' => $intervention];

        return $this->response(app(RiskOperations::class)->once($r->user(), 'intervention.update', $data,
            fn () => app(Interventions::class)->authorized($r->user(), $intervention, true),
            fn () => app(Interventions::class)->update($r->user(), $intervention, $data)));
    }

    public function transfer(RiskRequest $r, int $intervention): JsonResponse
    {
        $data = $r->validated() + ['intervention_id' => $intervention];
        $authority = function () use ($r, $intervention) {
            app(RiskAuthority::class)->actor($r->user());
            $row = Intervention::select(Interventions::META)->findOrFail($intervention);
            app(RiskAuthority::class)->administer($r->user(), (int) $row->learner_id, (int) $row->session_year_id);
        };

        return $this->response(app(RiskOperations::class)->once($r->user(), 'intervention.transfer', $data, $authority,
            fn () => app(Interventions::class)->transferOwner($r->user(), $intervention, (int) $data['owner_id'], (int) $data['expected_revision'], $data['reason'])));
    }

    public function assign(RiskRequest $r, int $learner, int $professional): JsonResponse
    {
        $data = $r->validated() + ['learner_id' => $learner, 'professional_id' => $professional];

        return $this->response(app(RiskOperations::class)->once($r->user(), 'risk.assign', $data,
            fn () => app(RiskAuthority::class)->administer($r->user(), $learner, $r->year()),
            fn () => app(Interventions::class)->assign($r->user(), $learner, $r->year(), $professional, $data)));
    }

    public function revoke(RiskRequest $r, int $learner, int $professional): JsonResponse
    {
        $data = $r->validated() + ['learner_id' => $learner, 'professional_id' => $professional];

        return $this->response(app(RiskOperations::class)->once($r->user(), 'risk.revoke', $data,
            fn () => app(RiskAuthority::class)->administer($r->user(), $learner, $r->year()),
            fn () => app(Interventions::class)->revoke($r->user(), $learner, $r->year(), $professional, (int) $data['expected_revision'])));
    }

    public function configure(RiskRequest $r): JsonResponse
    {
        return $this->response(app(RiskOperations::class)->once($r->user(), 'risk.configure', $r->validated(),
            fn () => app(RiskAuthority::class)->principal($r->user(), true),
            fn () => app(RiskSettingsService::class)->configure($r->user(), $r->validated())));
    }

    private function response(array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $data);
    }
}
