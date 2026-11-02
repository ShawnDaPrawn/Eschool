<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SendingDomainRequest;
use App\Models\Platform\SchoolSendingDomain;
use App\Services\Platform\Mail\SendingDomains;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** /sending-domain: the school connects the domain its notices are sent from (Action Plan 2.2). */
class SendingDomainController extends Controller
{
    public function __construct(private readonly SendingDomains $domains) {}

    public function index(SendingDomainRequest $request): View
    {
        $domain = $this->domains->current($request->user());

        return view('school.sending-domain', [
            'enabled' => SendingDomains::enabled(),
            'domain' => $domain,
            'records' => $domain ? $this->domains->records($domain) : [],
            'sender' => $domain?->status === SchoolSendingDomain::VERIFIED
                ? config('mail.sending_domains.local_part', 'notices').'@'.$domain->domain : null,
            'schoolName' => (string) app(CurrentSchool::class)->school()?->name,
        ]);
    }

    public function store(SendingDomainRequest $request): RedirectResponse
    {
        $domain = $this->domains->connect($request->user(), (string) $request->validated('domain'));

        return redirect()->route('sending-domain.index')->with('success', __(':domain is connected. Add the DNS records below, then check it.', ['domain' => $domain->domain]));
    }

    public function check(SendingDomainRequest $request, int $sendingDomain): RedirectResponse
    {
        $domain = $this->domains->check($request->user(), $sendingDomain);

        return redirect()->route('sending-domain.index')->with('success', $domain->status === SchoolSendingDomain::VERIFIED
            ? __(':domain is verified. Your school\'s notices are now sent from it.', ['domain' => $domain->domain])
            : __(':domain is not verified yet.', ['domain' => $domain->domain]));
    }

    public function destroy(SendingDomainRequest $request, int $sendingDomain): RedirectResponse
    {
        $this->domains->disconnect($request->user(), $sendingDomain);

        return redirect()->route('sending-domain.index')->with('success', __('The domain is disconnected. Notices are sent from the NavigateEd address again.'));
    }
}
