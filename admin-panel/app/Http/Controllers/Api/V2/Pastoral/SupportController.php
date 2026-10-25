<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Pastoral;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\SupportRequest;
use App\Models\Pastoral\SupportPlan;
use App\Services\Pastoral\SupportPlans;
use App\Services\Pastoral\SupportQueries;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    private function response(array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $data);
    }

    private function receipt(Model $row, string $kind, ?int $revision = null): JsonResponse
    {
        return $this->response(['id' => (int) $row->id, 'revision' => $revision ?? (int) $row->revision, 'kind' => $kind]);
    }

    public function access(SupportRequest $r): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->access($r->user()));
    }

    public function configuration(SupportRequest $r, string $audience): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->configuration($r->user(), $audience, (string) $r->validated('search', ''), (int) $r->validated('page', 1)));
    }

    public function cases(SupportRequest $r): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->cases($r->user(), (int) $r->validated('page', 1)));
    }

    public function plan(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->plan($r->user(), $plan));
    }

    public function reviews(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->reviews($r->user(), $plan, (int) $r->validated('page', 1)));
    }

    public function versions(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->versions($r->user(), $plan, (int) $r->validated('page', 1)));
    }

    public function disclosures(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->disclosures($r->user(), $plan, (int) $r->validated('page', 1)));
    }

    public function assignments(SupportRequest $r): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->assignments($r->user(), (int) $r->validated('page', 1)));
    }

    public function teacher(SupportRequest $r): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->teacher($r->user(), (int) $r->validated('page', 1)));
    }

    public function disclosure(SupportRequest $r, int $disclosure): JsonResponse
    {
        return $this->response(app(SupportQueries::class)->disclosure($r->user(), $disclosure));
    }

    public function assign(SupportRequest $r): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->assign($r->user(), $r->validated()), 'assignment');
    }

    public function create(SupportRequest $r): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->save($r->user(), $r->validated()), 'plan');
    }

    public function save(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->save($r->user(), $r->validated(), $plan), 'plan');
    }

    public function review(SupportRequest $r, int $plan): JsonResponse
    {
        $row = app(SupportPlans::class)->review($r->user(), $plan, $r->validated());

        return $this->receipt($row, 'review', (int) SupportPlan::findOrFail($plan)->revision);
    }

    public function close(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->close($r->user(), $plan, $r->validated()), 'plan');
    }

    public function disclose(SupportRequest $r, int $plan): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->disclose($r->user(), $plan, $r->validated()), 'disclosure');
    }

    public function revokeAssignment(SupportRequest $r, int $assignment): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->revoke($r->user(), 'assignment', $assignment, $r->validated()), 'assignment');
    }

    public function revokeDisclosure(SupportRequest $r, int $disclosure): JsonResponse
    {
        return $this->receipt(app(SupportPlans::class)->revoke($r->user(), 'disclosure', $disclosure, $r->validated()), 'disclosure');
    }
}
