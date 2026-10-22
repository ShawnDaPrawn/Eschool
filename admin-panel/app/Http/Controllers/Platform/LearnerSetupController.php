<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SetLearnerPasswordRequest;
use App\Models\Platform\LearnerInvitation;
use App\Services\Platform\Identity\LearnerInvitations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The public pages where a learner's password is chosen: from a guardian's
 * emailed link (signed, expiring, single use) or with a set-up code the
 * school printed. Neither needs a signed-in session.
 */
class LearnerSetupController extends Controller
{
    public function __construct(private readonly LearnerInvitations $invitations) {}

    public function showLink(Request $request, int $invitation, string $token): View
    {
        $found = $this->openFromLink($request, $invitation, $token);
        if ($found === null) {
            return view('learner-setup.invalid');
        }

        return view('learner-setup.form', [
            'learner' => $found->learner,
            'action' => $request->fullUrl(),
            'withCode' => false,
        ]);
    }

    public function storeLink(SetLearnerPasswordRequest $request, int $invitation, string $token): View|RedirectResponse
    {
        $found = $this->openFromLink($request, $invitation, $token);
        if ($found === null || ! $this->invitations->complete($found, (string) $request->input('password'))) {
            return view('learner-setup.invalid');
        }

        return view('learner-setup.done', ['learner' => $found->learner]);
    }

    public function showCode(): View
    {
        return view('learner-setup.form', [
            'learner' => null,
            'action' => route('learner-setup.code.store'),
            'withCode' => true,
        ]);
    }

    public function storeCode(SetLearnerPasswordRequest $request): View|RedirectResponse
    {
        $found = $this->invitations->findOpenByCode((string) $request->input('gr_number'), (string) $request->input('code'));
        if ($found === null || ! $this->invitations->complete($found, (string) $request->input('password'))) {
            // Same answer for an unknown GR number and a wrong, used or expired code.
            return back()->withInput($request->only('gr_number'))
                ->withErrors(['code' => trans('learner_setup_code_invalid')]);
        }

        return view('learner-setup.done', ['learner' => $found->learner]);
    }

    /**
     * The link's invitation when its signature, expiry and token are good and it
     * is still open. The signature is relative: links start with APP_URL.
     */
    private function openFromLink(Request $request, int $invitation, string $token): ?LearnerInvitation
    {
        if (! $request->hasValidRelativeSignature()) {
            return null;
        }
        $found = $this->invitations->findByLink($invitation, $token);

        return $found !== null && $found->isOpen() ? $found : null;
    }
}
