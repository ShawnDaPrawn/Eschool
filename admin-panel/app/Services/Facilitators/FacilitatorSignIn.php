<?php

declare(strict_types=1);

namespace App\Services\Facilitators;

use App\Http\Middleware\RequirePasswordChange;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FacilitatorSignIn
{
    public function __construct(private FacilitatorAccess $access, private ParentPolicyService $policies) {}

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
    public function signIn(Request $request, array $input): array
    {
        $school = School::active()->where('slug', $input['school'])->first();
        $identity = app(StudentNumbers::class)->resolveLogin($input['email']);
        abort_unless($school !== null && $identity !== null && $identity->status === 1 && Hash::check($input['password'], $identity->getAuthPassword()), 401, 'Invalid sign-in details.');

        return app(CurrentSchool::class)->run($school, function () use ($identity, $request, $input) {
            return DB::transaction(function () use ($identity, $request, $input) {
                $user = User::whereKey($identity->id)->lockForUpdate()->firstOrFail();
                // Recheck after the lock; a concurrent password reset cannot sign in.
                abort_unless(Hash::check($input['password'], $user->getAuthPassword()), 401);
                $user = $this->access->actor($user);
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
                $token = PortalSession::signIn($request, $user, AppTokens::FACILITATOR);

                return ['authenticated' => true, 'token' => $token === '' ? null : $token, 'profile' => $this->access->profile($user), 'password_change_required' => false, 'documents' => []];
            }, 3);
        });
    }
}
