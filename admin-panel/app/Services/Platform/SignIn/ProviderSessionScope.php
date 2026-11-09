<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use App\Http\Middleware\EnsureAccountActive;
use App\Models\Platform\School;
use App\Models\SignIn\SignInProviderLink;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * A provider sign-in opens the one school whose settings admitted it
 * (Action Plan 5.9). Its app token carries the binding as an ability (next
 * to "*", so it is the same app token as any other) and its panel session
 * carries it in the session. SetSchoolContext asks this class on every
 * signed-in request:
 *
 * - the bound school is the only school the session works in (another
 *   school, by header or by the school switcher, is refused);
 * - the session ends as soon as the link is removed, the school switches the
 *   provider off or stops allowing the person's role, or their current
 *   membership there ends.
 *
 * Password sign-ins carry no binding and are unaffected.
 */
final class ProviderSessionScope
{
    public const SESSION_KEY = 'sign_in_provider';

    private const ABILITY = 'sign-in-provider:';

    /** @return list<string> the token abilities of a provider sign-in: every ability, plus the binding */
    public static function abilities(int $schoolId, string $provider, int $linkId): array
    {
        return ['*', self::ABILITY.$schoolId.':'.$provider.':'.$linkId];
    }

    public static function bindSession(Request $request, int $schoolId, string $provider, int $linkId): void
    {
        $request->session()->put(self::SESSION_KEY, ['school' => $schoolId, 'provider' => $provider, 'link' => $linkId]);
    }

    /** @return array{school: int, provider: string, link: int}|null */
    public function binding(Request $request, User $user): ?array
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            foreach ((array) $token->abilities as $ability) {
                if (is_string($ability) && preg_match('/^'.preg_quote(self::ABILITY, '/').'([1-9][0-9]{0,17}):([a-z]{1,20}):([1-9][0-9]{0,17})$/D', $ability, $m) === 1) {
                    return ['school' => (int) $m[1], 'provider' => $m[2], 'link' => (int) $m[3]];
                }
            }

            return null;
        }
        $bound = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        if (is_array($bound) && is_int($bound['school'] ?? null) && is_string($bound['provider'] ?? null) && is_int($bound['link'] ?? null)) {
            return ['school' => $bound['school'], 'provider' => $bound['provider'], 'link' => $bound['link']];
        }

        return null;
    }

    /**
     * The bound school when the sign-in still holds there, else null (the
     * caller refuses and ends it).
     *
     * @param  array{school: int, provider: string, link: int}  $binding
     */
    public function school(User $user, array $binding): ?School
    {
        $membership = app(MembershipService::class)->active($user)->firstWhere('school_id', $binding['school']);
        if ($membership === null || ! EnsureAccountActive::isActive($user)) {
            return null;
        }

        return app(CurrentSchool::class)->run($binding['school'], function () use ($user, $binding, $membership) {
            $setting = app(ProviderSettings::class)->usable($binding['provider']);
            $linked = SignInProviderLink::query()->whereKey($binding['link'])->where('user_id', $user->id)->where('provider', $binding['provider'])->exists();

            return $setting !== null && $linked && app(ProviderSettings::class)->allowedRoles($setting, $user) !== [] ? $membership->school : null;
        });
    }
}
