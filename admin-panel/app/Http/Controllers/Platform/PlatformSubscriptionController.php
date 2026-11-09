<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SubscriptionAdminRequest;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Subscriptions\SubscriptionAdmin;
use App\Services\Platform\Subscriptions\SubscriptionRevenue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** HQ: learner subscriptions and their revenue (Action Plan 3.20, SA.8 Build 3). */
class PlatformSubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionAdmin $admin) {}

    public function index(Request $request): View
    {
        $filters = array_filter($request->only(['status', 'plan', 'school', 'reference', 'month']), fn ($value) => $value !== null && $value !== '');

        return view('saas.subscriptions', $this->admin->page($request->user(), $filters, (int) $request->query('page', '1')));
    }

    /** The month's revenue as JSON, for exports and reporting (billing.view). */
    public function revenue(Request $request): JsonResponse
    {
        $month = Validator::make($request->query(), ['month' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/D']])->validate()['month'] ?? null;

        return response()->json(app(SubscriptionRevenue::class)->summary($request->user(), PlatformBilling::month($month)))->header('Cache-Control', 'no-store, private');
    }

    public function payment(SubscriptionAdminRequest $request, int $school, int $subscription): RedirectResponse
    {
        $this->admin->recordEft($school, $subscription, $request->validated(), $request->user());

        return back()->with('success', __('Payment recorded. The learning plan runs to the end of its new period.'));
    }

    public function decline(SubscriptionAdminRequest $request, int $school, int $subscription): RedirectResponse
    {
        $this->admin->decline($school, $subscription, $request->validated(), $request->user());

        return back()->with('success', __('Request declined.'));
    }

    public function end(SubscriptionAdminRequest $request, int $school, int $subscription): RedirectResponse
    {
        $this->admin->end($school, $subscription, $request->validated(), $request->user());

        return back()->with('success', __('Subscription ended.'));
    }

    public function licence(SubscriptionAdminRequest $request, int $school): RedirectResponse
    {
        $this->admin->createLicence($school, $request->validated(), $request->user());

        return back()->with('success', __('School licence set up. Record its EFT to start it.'));
    }

    public function sponsorship(SubscriptionAdminRequest $request, int $school): RedirectResponse
    {
        $this->admin->createSponsorship($school, $request->validated(), $request->user());

        return back()->with('success', __('Sponsored places set up. Record the EFT to start them; the school names the learners.'));
    }
}
