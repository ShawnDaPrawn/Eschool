<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SubscriptionRequest;
use App\Services\Platform\Subscriptions\LearnerSubscriptions;
use App\Services\Platform\Subscriptions\SponsoredPlaces;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/**
 * Learning plans (Action Plan 3.20). A guardian sees the plans and their own
 * children's entitlement, asks for a plan for a child and cancels their own;
 * a school's Super Admin names the learners who hold a sponsor's places.
 */
class SubscriptionController extends Controller
{
    public function overview(SubscriptionRequest $r): JsonResponse
    {
        return ResponseService::successResponse(data: app(LearnerSubscriptions::class)->overview($r->user()));
    }

    public function learner(SubscriptionRequest $r, int $learner): JsonResponse
    {
        return ResponseService::successResponse(data: app(LearnerSubscriptions::class)->learner($r->user(), $learner));
    }

    public function request(SubscriptionRequest $r, int $learner): JsonResponse
    {
        return ResponseService::successResponse(data: app(LearnerSubscriptions::class)->request($r->user(), $learner, (string) $r->validated('operation_id')));
    }

    public function cancel(SubscriptionRequest $r, int $subscription): JsonResponse
    {
        return ResponseService::successResponse(data: app(LearnerSubscriptions::class)->cancel($r->user(), $subscription, (string) $r->validated('operation_id')));
    }

    public function sponsored(SubscriptionRequest $r): JsonResponse
    {
        return ResponseService::successResponse(data: app(SponsoredPlaces::class)->index($r->user()));
    }

    public function addLearner(SubscriptionRequest $r, int $subscription): JsonResponse
    {
        return ResponseService::successResponse(data: app(SponsoredPlaces::class)->add($r->user(), $subscription, (int) $r->validated('learner_id')));
    }

    public function removeLearner(SubscriptionRequest $r, int $subscription, int $learner): JsonResponse
    {
        return ResponseService::successResponse(data: app(SponsoredPlaces::class)->remove($r->user(), $subscription, $learner));
    }
}
