<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\AnswerJoinInvitationRequest;
use App\Services\Platform\Identity\SchoolJoinInvitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The page behind an emailed invitation to join another school with an
 * existing account. The invitee answers only while signed in as that account;
 * a guest is sent to sign in with their existing password and comes back here.
 */
class SchoolJoinInvitationController extends Controller
{
    public function __construct(private readonly SchoolJoinInvitations $invitations) {}

    public function show(Request $request, string $token): View|RedirectResponse|Response
    {
        $user = $request->user();
        if ($user === null) {
            // The same for every link, valid or not: a guest learns nothing here.
            return redirect()->guest(route('login'))->with('success', __('Sign in with your existing NavigateEd password to see your invitation.'));
        }
        try {
            $invitation = $this->invitations->preview($token, $user);
        } catch (HttpException $e) {
            if ($e->getStatusCode() !== 403) {
                throw $e;
            }

            return response()->view('invitations.join', ['invitation' => null, 'token' => $token], 403);
        }

        return view('invitations.join', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(AnswerJoinInvitationRequest $request, string $token): RedirectResponse
    {
        $school = $this->invitations->accept($token, $request->user());
        // Open the panel in the school just joined, as the school switcher does.
        $request->session()->put((string) config('tenancy.session_key'), (int) $school->id);
        $request->session()->forget('session_year');

        return redirect()->route('home')->with('success', __('You have joined :school. Choose a school with the school switcher at the top of the page.', ['school' => $school->name]));
    }

    public function decline(AnswerJoinInvitationRequest $request, string $token): RedirectResponse
    {
        $this->invitations->decline($token, $request->user());

        return redirect()->route('home')->with('success', __('You declined the invitation. Nothing has changed on your account.'));
    }
}
