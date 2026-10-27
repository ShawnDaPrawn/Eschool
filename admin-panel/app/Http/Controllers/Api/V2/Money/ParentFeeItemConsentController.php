<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\ParentFeeItemConsentRequest;
use App\Services\Money\FeeItemConsents;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class ParentFeeItemConsentController extends Controller
{
    public function index(ParentFeeItemConsentRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', app(FeeItemConsents::class)->page($request->user(), (int) $request->route('account'), $request->integer('page', 1), $request->integer('per_page', 20)));
    }

    public function change(ParentFeeItemConsentRequest $request): JsonResponse
    {
        $event = app(FeeItemConsents::class)->change($request->user(), (int) $request->route('account'), (int) $request->route('item'), $request->validated());
        $data = ['id' => (int) $event->id, 'school_id' => (int) $event->school_id, 'holder_user_id' => $event->actor_id, 'fee_account_id' => (int) $event->fee_account_id, 'fee_item_id' => (int) $event->fee_item_id, 'session_year_id' => (int) $event->session_year_id, 'learner_id' => $event->learner_id, 'revision' => $event->revision, 'active' => $event->active, 'recorded_at' => $event->created_at->toIso8601String()];

        return ResponseService::successResponse('data_fetched_successfully', $data);
    }
}
