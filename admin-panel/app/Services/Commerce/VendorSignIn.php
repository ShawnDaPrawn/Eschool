<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Http\Middleware\RequirePasswordChange;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VendorSignIn
{
    public function __construct(private VendorOperatorAccess $access, private ParentPolicyService $policies) {}

    private function text(string $html): string
    {
        $html = preg_replace('#<(?:br\\s*/?|/(?:p|div|h[1-6]|li))\\s*>#i', "\n", $html);

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function requirements(User $user, bool $password): array
    {
        $status = $this->policies->status($user);
        $documents = [];
        foreach (['navigateed', 'school'] as $key) {
            $document = $status[$key];
            if ($document['required'] && ! $document['accepted']) {
                $documents[] = ['document' => $key, 'version' => $document['version'], 'title' => $key === 'navigateed' ? 'NavigateEd' : $status['school_name'],
                    'text' => $key === 'navigateed' ? $document['body'] : $this->text($document['privacy_policy']."\n\n".$document['terms'])];
            }
        }

        return ['authenticated' => false, 'token' => null, 'profile' => null, 'password_change_required' => $password, 'documents' => $documents];
    }

    /** No session exists until all credential, role, password and policy checks pass. */
    public function signIn(Request $request, array $input, bool $panel = false): array
    {
        $school = School::active()->where('slug', $input['school'])->first();
        $identity = app(StudentNumbers::class)->resolveLogin($input['email']);
        // Use the same account-wide events/key as legacy sign-in without
        // authenticating a guard before school, role and setup checks pass.
        $guard = $panel || PortalSession::isPortal($request) ? 'web' : 'sanctum';
        $credentials = ['email' => $identity?->email ?: $input['email'], 'password' => $input['password']];
        Event::dispatch(new Attempting($guard, $credentials, false));
        if ($school === null || $identity === null || $identity->status !== 1 || ! Hash::check($input['password'], $identity->getAuthPassword())) {
            Event::dispatch(new Failed($guard, $identity, $credentials));
            abort(401, 'Invalid sign-in details.');
        }

        return app(CurrentSchool::class)->run($school, function () use ($identity, $request, $input, $guard, $credentials, $panel) {
            $result = DB::transaction(function () use ($identity, $request, $input, $guard, $credentials, $panel) {
                $user = User::whereKey($identity->id)->lockForUpdate()->firstOrFail();
                // Recheck after the lock; a concurrent password reset cannot sign in.
                if (! Hash::check($input['password'], $user->getAuthPassword())) {
                    Event::dispatch(new Failed($guard, $user, $credentials));
                    abort(401);
                }
                $user = $this->access->actor($user, ready: false);
                $password = RequirePasswordChange::mustChange($user, $input['password']);
                $requirements = $this->requirements($user, $password);
                $acceptances = $input['acceptances'] ?? [];
                $complete = ! $password || ! empty($input['new_password']);
                foreach ($requirements['documents'] as $document) {
                    $complete = $complete && isset($acceptances[$document['document']]) && hash_equals($document['version'], $acceptances[$document['document']]);
                }
                if (! $complete) {
                    return $requirements;
                }
                if ($password) {
                    $new = $input['new_password'];
                    if (strlen($new) > 72) {
                        throw ValidationException::withMessages(['new_password' => 'Choose a shorter password.']);
                    }
                    if (RequirePasswordChange::isDefaultPassword($user, $new) || Hash::check($new, $user->getAuthPassword())) {
                        throw ValidationException::withMessages(['new_password' => 'Choose a new password that is not your date of birth or the previous password.']);
                    }
                }
                foreach ($requirements['documents'] as $document) {
                    $accepted = $this->policies->accept($user, $document['document'], $document['version']);
                    if (! $accepted['ok']) {
                        throw ValidationException::withMessages(['acceptances' => 'These terms changed. Refresh sign-in and read them again.']);
                    }
                }
                if ($password) {
                    $user->forceFill(['password' => Hash::make($input['new_password']), 'password_change_required' => false])->save();
                    // An explicit password change ends all previous app sessions.
                    AppTokens::revokeAll($user);
                }
                $profile = $this->access->profile($user);
                if ($panel) {
                    abort_unless($request->hasSession(), 403);
                    Auth::guard('web')->login($user);
                    $request->session()->regenerate();
                    $request->session()->put('password_hash_web', $user->getAuthPassword());
                    $request->session()->put('portal_app', AppTokens::VENDOR);
                    $request->session()->put((string) config('tenancy.session_key'), app(CurrentSchool::class)->id());
                    $token = '';
                } else {
                    $token = PortalSession::signIn($request, $user, AppTokens::VENDOR);
                }

                return ['authenticated' => true, 'token' => $token === '' ? null : $token, 'profile' => $profile, 'password_change_required' => false, 'documents' => []];
            }, 3);
            if ($result['authenticated'] && ! $panel && ! PortalSession::isPortal($request)) {
                // Cookie sign-in emits Login through the web guard already.
                // Emit once after transaction retries, never during setup refusal.
                Event::dispatch(new Login($guard, $identity, false));
            }

            return $result;
        });
    }
}
