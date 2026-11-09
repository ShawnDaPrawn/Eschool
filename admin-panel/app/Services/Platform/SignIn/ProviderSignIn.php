<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use App\Exceptions\TooManyLoginFailures;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureTeacherActive;
use App\Http\Middleware\RequirePasswordChange;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SignIn\SignInProviderChallenge;
use App\Models\SignIn\SignInProviderLink;
use App\Models\SignIn\SignInProviderSetting;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\LoginIdentity;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Google and Microsoft sign-in (Action Plan 5.9): the panel's code flow
 * (PKCE, state, nonce), the apps' ID token sign-in, and linking.
 *
 * Every step runs inside the school the person chose (by its slug, or the
 * school they work in when linking), so a setting, link or challenge of
 * another school is never read. A sign-in:
 *
 * 1. uses its challenge once (state for the panel, nonce for the apps);
 * 2. verifies the ID token (IdTokenVerifier) against the school's setting;
 * 3. finds the link by the provider's subject at this school; none means no
 *    sign-in (no account is created or linked here);
 * 4. applies every account check of a password sign-in: an active account,
 *    no pending password change, no lockout from wrong passwords, a current
 *    membership here in a role the school allows, a deactivated teacher
 *    refused, and never platform (HQ) staff, who keep their two-factor path;
 * 5. issues the same session or app token a password sign-in would, bound to
 *    this school and link (ProviderSessionScope).
 *
 * Refusals are audited with their reason and no token, code or email.
 */
final class ProviderSignIn
{
    /** The apps a provider sign-in may issue a token for, and the role each needs. */
    public const APPS = ['teacher' => AppTokens::TEACHER, 'student' => AppTokens::STUDENT];

    private const PENDING = 'sign_in_provider_pending';

    public function __construct(
        private readonly ProviderCatalogue $catalogue,
        private readonly ProviderSettings $settings,
        private readonly ProviderChallenges $challenges,
        private readonly IdTokenVerifier $verifier,
        private readonly ProviderCodes $codes,
        private readonly ProviderLinks $links,
        private readonly CurrentSchool $currentSchool,
    ) {}

    // ---- The panel -----------------------------------------------------------

    /**
     * The sign-in buttons for the login page: the school named by ?school=
     * (its slug), else the page's school, and the providers it offers now.
     *
     * @return array{school: ?string, providers: list<array{provider: string, label: string}>}
     */
    public function loginOptions(mixed $slug): array
    {
        $school = is_string($slug) && preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/D', $slug) === 1
            ? School::query()->active()->where('slug', $slug)->first() : null;
        $school ??= $this->currentSchool->school();
        if (! $school instanceof School || ! $school->isActive()) {
            return ['school' => null, 'providers' => []];
        }

        return ['school' => (string) $school->slug, 'providers' => $this->currentSchool->run($school, fn () => $this->settings->offered())];
    }

    /** Starts a panel sign-in at the named school: the provider's address to send the browser to. */
    public function startWeb(Request $request, string $provider, string $schoolSlug): string
    {
        $school = $this->school($schoolSlug);

        return $this->guarded($school, $provider, 'web', function () use ($request, $provider) {
            $setting = $this->usable($provider);

            return $this->authorizeUrl($request, $provider, $setting, SignInProviderChallenge::SIGN_IN, null);
        });
    }

    /** Starts linking the signed-in person's account at their current school, after their password. */
    public function startWebLink(Request $request, User $user, string $provider, string $password): string
    {
        $school = $this->currentSchool->school() ?? throw new ProviderSignInRefused('provider_off');

        return $this->guarded($school, $provider, 'web', function () use ($request, $user, $provider, $password) {
            $this->confirmPassword($user, $password);
            $setting = $this->usable($provider);
            if ($this->settings->allowedRoles($setting, $user) === []) {
                throw new ProviderSignInRefused('role_not_allowed');
            }

            return $this->authorizeUrl($request, $provider, $setting, SignInProviderChallenge::LINK, $user);
        });
    }

    /**
     * The provider sends the browser back here with a code and the state.
     *
     * @return array{purpose: string, provider: string, user: User}
     */
    public function callback(Request $request, string $provider): array
    {
        $state = (string) $request->query('state', '');
        $pending = (array) $request->session()->get(self::PENDING, []);
        $key = hash('sha256', $state);
        $entry = $pending[$key] ?? null;
        unset($pending[$key]);
        $request->session()->put(self::PENDING, $pending);
        if (! is_array($entry) || ($entry['provider'] ?? null) !== $provider || ! is_int($entry['school'] ?? null)) {
            // No school is known for a state this browser did not start: audited without one.
            Audit::record('sign_in.provider_refused', null, [], ['provider' => $provider, 'channel' => 'web', 'reason' => 'state_invalid']);
            throw new ProviderSignInRefused('state_invalid');
        }
        $school = School::query()->active()->find($entry['school']) ?? throw new ProviderSignInRefused('provider_off');

        return $this->guarded($school, $provider, 'web', function () use ($request, $provider, $state) {
            $challenge = $this->challenges->consumeState($provider, $state) ?? throw new ProviderSignInRefused('state_invalid');
            if ($request->query('error') !== null) {
                throw new ProviderSignInRefused('provider_error');
            }
            $code = (string) $request->query('code', '');
            if ($code === '' || strlen($code) > 2048) {
                throw new ProviderSignInRefused('code_rejected');
            }
            $setting = $this->usable($provider);
            $jwt = $this->codes->idToken($provider, $code, (string) $challenge->code_verifier, $this->redirectUri($provider), $this->tenantSegment($provider, $setting));
            $identity = $this->verifier->verify($provider, $jwt, $challenge->nonce_hash, $this->catalogue->audiences($provider, false), $setting);

            if ($challenge->purpose === SignInProviderChallenge::LINK) {
                $user = Auth::guard('web')->user();
                if (! $user instanceof User || (int) $user->id !== (int) $challenge->user_id) {
                    throw new ProviderSignInRefused('wrong_account');
                }
                $this->links->link($user, $setting, $identity, 'panel');

                return ['purpose' => SignInProviderChallenge::LINK, 'provider' => $provider, 'user' => $user];
            }

            if (Auth::guard('web')->check()) {
                throw new ProviderSignInRefused('already_signed_in');
            }
            $link = $this->links->find($identity) ?? throw new ProviderSignInRefused('not_linked');
            $user = $this->admit($link, $setting, null);

            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            // As the password sign-in: AuthenticateSession ends this session when the password changes.
            $request->session()->put('password_hash_web', $user->getAuthPassword());
            $request->session()->put((string) config('tenancy.session_key'), $this->currentSchool->id());
            ProviderSessionScope::bindSession($request, $this->currentSchool->id(), $provider, (int) $link->id);
            $this->signedIn($link, 'web');

            return ['purpose' => SignInProviderChallenge::SIGN_IN, 'provider' => $provider, 'user' => $user];
        });
    }

    // ---- The apps ------------------------------------------------------------

    /**
     * A one-use nonce for the app's native sign-in at the named school (or,
     * for linking, the signed-in person's current school).
     *
     * @return array{nonce: string, expires_at: string, client_id: string}
     */
    public function appNonce(string $provider, string $schoolSlug, string $app): array
    {
        $school = $this->school($schoolSlug);

        return $this->guarded($school, $provider, 'app', function () use ($provider, $app) {
            $this->usable($provider);
            $started = $this->challenges->start($provider, SignInProviderChallenge::SIGN_IN, SignInProviderChallenge::APP, null, $app);

            return $this->nonceAnswer($provider, $started);
        });
    }

    /** @return array{nonce: string, expires_at: string, client_id: string} */
    public function appLinkNonce(User $user, string $provider): array
    {
        $school = $this->currentSchool->school() ?? throw new ProviderSignInRefused('provider_off');

        return $this->guarded($school, $provider, 'app', function () use ($user, $provider) {
            $setting = $this->usable($provider);
            if ($this->settings->allowedRoles($setting, $user) === []) {
                throw new ProviderSignInRefused('role_not_allowed');
            }
            $started = $this->challenges->start($provider, SignInProviderChallenge::LINK, SignInProviderChallenge::APP, $user);

            return $this->nonceAnswer($provider, $started);
        });
    }

    /**
     * Signs an app in with the ID token its native sign-in returned.
     *
     * @param  array{school: string, id_token: string, nonce: string, app: string}  $input
     * @return array{token: ?string, app: string, school: array{id: int, name: string, slug: string}}
     */
    public function appSignIn(Request $request, string $provider, array $input): array
    {
        $school = $this->school($input['school']);

        return $this->guarded($school, $provider, 'app', function () use ($request, $provider, $input, $school) {
            $setting = $this->usable($provider);
            $challenge = $this->challenges->consumeNonce($provider, $input['nonce'], SignInProviderChallenge::SIGN_IN)
                ?? throw new ProviderSignInRefused('nonce_invalid');
            if ($challenge->app !== null && $challenge->app !== $input['app']) {
                throw new ProviderSignInRefused('nonce_invalid');
            }
            $identity = $this->verifier->verify($provider, $input['id_token'], $challenge->nonce_hash, $this->catalogue->audiences($provider, true), $setting);
            $link = $this->links->find($identity) ?? throw new ProviderSignInRefused('not_linked');
            $app = self::APPS[$input['app']];
            $user = $this->admit($link, $setting, $app);

            $schoolId = $this->currentSchool->id();
            $token = PortalSession::signIn($request, $user, $app, ProviderSessionScope::abilities($schoolId, $provider, (int) $link->id));
            if ($token === '') {
                ProviderSessionScope::bindSession($request, $schoolId, $provider, (int) $link->id);
            }
            $this->signedIn($link, 'app');

            return ['token' => $token === '' ? null : $token, 'app' => $input['app'], 'school' => ['id' => (int) $school->id, 'name' => (string) $school->name, 'slug' => (string) $school->slug]];
        });
    }

    /**
     * Links the signed-in person's account at their current school, from an
     * app: the ID token, the link nonce and their password.
     *
     * @param  array{id_token: string, nonce: string, password: string}  $input
     */
    public function appLink(User $user, string $provider, array $input): SignInProviderLink
    {
        $school = $this->currentSchool->school() ?? throw new ProviderSignInRefused('provider_off');

        return $this->guarded($school, $provider, 'app', function () use ($user, $provider, $input) {
            $this->confirmPassword($user, $input['password']);
            $setting = $this->usable($provider);
            $challenge = $this->challenges->consumeNonce($provider, $input['nonce'], SignInProviderChallenge::LINK, $user)
                ?? throw new ProviderSignInRefused('nonce_invalid');
            $identity = $this->verifier->verify($provider, $input['id_token'], $challenge->nonce_hash, $this->catalogue->audiences($provider, true), $setting);

            return $this->links->link($user, $setting, $identity, 'app');
        });
    }

    // ---- Shared --------------------------------------------------------------

    /**
     * Every check a password sign-in makes, for the linked account, at the
     * current school. $app null is the panel.
     */
    private function admit(SignInProviderLink $link, SignInProviderSetting $setting, ?string $app): User
    {
        $user = User::query()->find($link->user_id) ?? throw new ProviderSignInRefused('account_refused');
        if (PlatformStaffIdentity::query()->where('user_id', $user->id)->exists()) {
            // HQ staff never sign in this way: HQ keeps its own password and two-factor path.
            throw new ProviderSignInRefused('platform_staff');
        }
        if (! EnsureAccountActive::isActive($user)) {
            throw new ProviderSignInRefused('account_inactive');
        }
        if ($user->password_change_required) {
            throw new ProviderSignInRefused('password_change_required');
        }
        if (RateLimiter::tooManyAttempts(LoginIdentity::failureKey(['id' => $user->id]), TooManyLoginFailures::MAX_FAILURES)) {
            throw new ProviderSignInRefused('locked');
        }
        $roles = $this->settings->allowedRoles($setting, $user);
        $fits = match ($app) {
            null => array_diff($roles, [SchoolRoles::STUDENT]) !== [],
            AppTokens::TEACHER => in_array(SchoolRoles::TEACHER, $roles, true),
            AppTokens::STUDENT => in_array(SchoolRoles::STUDENT, $roles, true),
            default => false,
        };
        if (! $fits) {
            throw new ProviderSignInRefused('role_not_allowed');
        }
        if (in_array(SchoolRoles::TEACHER, $roles, true) && ! EnsureTeacherActive::isActive($user)) {
            throw new ProviderSignInRefused('account_inactive');
        }

        return $user;
    }

    private function confirmPassword(User $user, string $password): void
    {
        if ($password === '' || strlen($password) > 4096 || ! Hash::check($password, $user->getAuthPassword())) {
            // Counts towards the account's lockout, like any wrong password.
            Event::dispatch(new Failed('web', $user, ['id' => $user->id]));
            throw new ProviderSignInRefused('password_wrong');
        }
        if (RequirePasswordChange::mustChange($user, $password)) {
            throw new ProviderSignInRefused('password_change_required');
        }
    }

    private function usable(string $provider): SignInProviderSetting
    {
        if (! $this->catalogue->configured($provider)) {
            throw new ProviderSignInRefused('provider_off');
        }

        return $this->settings->usable($provider) ?? throw new ProviderSignInRefused('provider_off');
    }

    private function school(string $slug): School
    {
        $school = preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/D', $slug) === 1 ? School::query()->active()->where('slug', $slug)->first() : null;

        return $school ?? throw new ProviderSignInRefused('provider_off');
    }

    /**
     * Runs a step inside the school and audits a refusal there.
     *
     * @template T
     *
     * @param  callable(): T  $step
     * @return T
     */
    private function guarded(School $school, string $provider, string $channel, callable $step): mixed
    {
        abort_unless($this->catalogue->exists($provider), 404);

        return $this->currentSchool->run($school, function () use ($provider, $channel, $step) {
            try {
                return $step();
            } catch (ProviderSignInRefused $refused) {
                Audit::record('sign_in.provider_refused', null, [], ['provider' => $provider, 'channel' => $channel, 'reason' => $refused->reason], $this->currentSchool->id());
                throw $refused;
            }
        });
    }

    private function authorizeUrl(Request $request, string $provider, SignInProviderSetting $setting, string $purpose, ?User $user): string
    {
        $started = $this->challenges->start($provider, $purpose, SignInProviderChallenge::WEB, $user);
        $pending = (array) $request->session()->get(self::PENDING, []);
        // A browser keeps its five newest attempts.
        $pending = array_slice($pending, -4, null, true);
        $pending[hash('sha256', (string) $started['state'])] = ['school' => $this->currentSchool->id(), 'provider' => $provider];
        $request->session()->put(self::PENDING, $pending);

        $query = [
            'client_id' => $this->catalogue->clientId($provider),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri($provider),
            'scope' => (string) $this->catalogue->setting($provider, 'scopes'),
            'state' => $started['state'],
            'nonce' => $started['nonce'],
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', (string) $started['verifier'], true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ];
        if ($provider === ProviderCatalogue::GOOGLE && count($this->settings->domains($setting)) === 1) {
            // A hint only: the hd claim is still checked on the ID token.
            $query['hd'] = $this->settings->domains($setting)[0];
        }
        if ($provider === ProviderCatalogue::MICROSOFT) {
            $query['response_mode'] = 'query';
        }
        $base = str_replace('{tenant}', rawurlencode($this->tenantSegment($provider, $setting)), (string) $this->catalogue->setting($provider, 'authorize_url'));

        return $base.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /** Microsoft: the school's one tenant, or "organizations" for several. Google has none. */
    private function tenantSegment(string $provider, SignInProviderSetting $setting): string
    {
        if ($provider !== ProviderCatalogue::MICROSOFT) {
            return '';
        }
        $tenants = $this->settings->tenants($setting);

        return count($tenants) === 1 ? $tenants[0] : 'organizations';
    }

    public function redirectUri(string $provider): string
    {
        return route('sign-in-provider.callback', ['provider' => $provider]);
    }

    /** @param array{challenge: SignInProviderChallenge, nonce: string} $started */
    private function nonceAnswer(string $provider, array $started): array
    {
        return ['nonce' => $started['nonce'], 'expires_at' => $started['challenge']->expires_at->toIso8601String(), 'client_id' => $this->catalogue->clientId($provider)];
    }

    private function signedIn(SignInProviderLink $link, string $channel): void
    {
        $link->forceFill(['last_used_at' => now()])->save();
        Audit::record('sign_in.provider_signed_in', $link, [], ['provider' => $link->provider, 'channel' => $channel, 'user_id' => (int) $link->user_id, 'link' => $link->fingerprint()]);
    }
}
