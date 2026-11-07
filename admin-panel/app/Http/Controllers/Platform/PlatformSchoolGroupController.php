<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SchoolGroupRequest;
use App\Services\Platform\Groups\SchoolGroupBilling;
use App\Services\Platform\Groups\SchoolGroupDashboard;
use App\Services\Platform\Groups\SchoolGroups;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: school groups, their schools, administrators, figures and invoices (Action Plan 5.7, SA.18). */
class PlatformSchoolGroupController extends Controller
{
    public function __construct(
        private readonly SchoolGroups $groups,
        private readonly SchoolGroupBilling $billing,
    ) {}

    public function index(Request $request): View
    {
        return view('saas.groups', ['groups' => $this->groups->list($request->user())]);
    }

    public function store(SchoolGroupRequest $request): RedirectResponse
    {
        $group = $this->groups->create($request->user(), $request->validated());

        return redirect()->route('saas.groups.show', $group->id)->with('success', __('Group created. Invite its schools: each school\'s Super Admin decides whether to join.'));
    }

    public function show(Request $request, int $group): View
    {
        $data = $this->groups->show($request->user(), $group);

        return view('saas.group', $data + ['dashboard' => app(SchoolGroupDashboard::class)->read($group),
            'canManage' => app(PlatformStaffAccess::class)->can($request->user(), 'groups.manage'),
            'canBill' => app(PlatformStaffAccess::class)->can($request->user(), 'billing.manage'),
            'canSeeBilling' => app(PlatformStaffAccess::class)->can($request->user(), 'billing.view')]);
    }

    public function invite(SchoolGroupRequest $request, int $group): RedirectResponse
    {
        $this->groups->invite($request->user(), $group, $request->validated());

        return redirect()->route('saas.groups.show', $group)->with('success', __('Invitation recorded. The school\'s Super Admin sees it on their School groups page; nothing is shared until they agree.'));
    }

    public function end(SchoolGroupRequest $request, int $group, int $member): RedirectResponse
    {
        $this->groups->endByHq($request->user(), $group, $member, $request->validated());

        return redirect()->route('saas.groups.show', $group)->with('success', __('Done. The group no longer sees this school.'));
    }

    public function billing(SchoolGroupRequest $request, int $group, int $member): RedirectResponse
    {
        $this->groups->setBilling($request->user(), $group, $member, $request->validated());

        return redirect()->route('saas.groups.show', $group)->with('success', __('Billing changed. It applies from the group\'s next draft.'));
    }

    public function addAdmin(SchoolGroupRequest $request, int $group): RedirectResponse
    {
        $this->groups->addAdmin($request->user(), $group, $request->validated());

        return redirect()->route('saas.groups.show', $group)->with('success', __('Group administrator added. They see the group\'s figures only, never a record inside a school.'));
    }

    public function revokeAdmin(SchoolGroupRequest $request, int $group, int $admin): RedirectResponse
    {
        $this->groups->revokeAdmin($request->user(), $group, $admin, $request->validated());

        return redirect()->route('saas.groups.show', $group)->with('success', __('Group administrator revoked.'));
    }

    public function invoices(Request $request, int $group): View
    {
        return view('saas.group-billing', $this->billing->overview($request->user(), $group)
            + ['canManage' => app(PlatformStaffAccess::class)->can($request->user(), 'billing.manage')]);
    }

    public function invoice(Request $request, int $group, int $invoice): View
    {
        return view('saas.group-invoice', $this->billing->show($request->user(), $group, $invoice)
            + ['canManage' => app(PlatformStaffAccess::class)->can($request->user(), 'billing.manage')]);
    }

    public function compose(SchoolGroupRequest $request, int $group): RedirectResponse
    {
        $invoice = $this->billing->compose($request->user(), $group, $request->validated());

        return redirect()->route('saas.groups.invoices.show', [$group, $invoice->id])->with('success', __('Group draft created from its schools\' own monthly drafts.'));
    }

    public function recompute(SchoolGroupRequest $request, int $group, int $invoice): RedirectResponse
    {
        $this->billing->recompute($request->user(), $group, $invoice);

        return redirect()->route('saas.groups.invoices.show', [$group, $invoice])->with('success', __('Group draft recomputed.'));
    }

    public function issue(SchoolGroupRequest $request, int $group, int $invoice): RedirectResponse
    {
        $reference = $this->billing->issue($request->user(), $group, $invoice);

        return redirect()->route('saas.groups.invoices.show', [$group, $invoice])->with('success', __('Group invoice :reference issued. Its schools\' months are billed through it.', ['reference' => $reference]));
    }

    public function discard(SchoolGroupRequest $request, int $group, int $invoice): RedirectResponse
    {
        $this->billing->discard($request->user(), $group, $invoice);

        return redirect()->route('saas.groups.invoices', $group)->with('success', __('Group draft discarded. Its schools\' own drafts are free again.'));
    }

    public function pay(SchoolGroupRequest $request, int $group, int $invoice): RedirectResponse
    {
        $this->billing->recordPayment($request->user(), $group, $invoice, $request->validated());

        return redirect()->route('saas.groups.invoices.show', [$group, $invoice])->with('success', __('Payment recorded.'));
    }

    public function reverse(SchoolGroupRequest $request, int $group, int $invoice, int $payment): RedirectResponse
    {
        $this->billing->reversePayment($request->user(), $group, $invoice, $payment, $request->validated());

        return redirect()->route('saas.groups.invoices.show', [$group, $invoice])->with('success', __('Payment reversed. It stays on the invoice with its reason.'));
    }
}
