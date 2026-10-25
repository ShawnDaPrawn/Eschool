<?php

declare(strict_types=1);

namespace Tests\Feature\Facilitators;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\PolicyAcceptance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FacilitatorAccessTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $facilitator;

    private array $jar = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->facilitator = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->facilitator->tokens()->delete();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->facilitator->id)->where('role', 'Teacher')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->facilitator->id, 'role' => 'Facilitator', 'status' => 'active']);
        $this->facilitator->removeRole('Teacher');
        $this->facilitator->assignRole('Facilitator');
        $this->facilitator->forceFill(['password' => Hash::make('SyntheticStaff2026'), 'password_change_required' => false, 'fcm_id' => 'synthetic-teacher-push-registration'])->save();
        ParentPolicyService::acceptCurrent($this->facilitator);
    }

    private function input(array $extra = []): array
    {
        return $extra + ['school' => School::findOrFail(1)->slug, 'email' => $this->facilitator->email, 'password' => 'SyntheticStaff2026'];
    }

    private function login(array $extra = [])
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->postJson('/api/v2/facilitator/sign-in', $this->input($extra));
    }

    private function browser(string $method, string $path, array $data = [])
    {
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->defaultHeaders = [];
        $this->unencryptedCookies = $this->jar;
        $headers = ['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'];
        if ($method !== 'GET' && isset($this->jar['XSRF-TOKEN'])) {
            $headers['X-XSRF-TOKEN'] = $this->jar['XSRF-TOKEN'];
        }
        $response = $this->withHeaders($headers)->withCredentials()->json($method, $path, $data);
        foreach ($response->headers->getCookies() as $cookie) {
            $this->jar[$cookie->getName()] = (string) $cookie->getValue();
        }
        $this->defaultHeaders = [];

        return $response;
    }

    public function test_dedicated_profile_preserves_other_app_tokens_and_push_registration(): void
    {
        $teacher = $this->facilitator->createToken(AppTokens::TEACHER);
        $parent = $this->facilitator->createToken(AppTokens::PARENT);
        $first = $this->login()->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.profile.roles', ['Facilitator'])->json('data.token');
        $this->assertNotEmpty($first);
        $this->assertSame(3, $this->facilitator->tokens()->count());
        $second = $this->login()->assertOk()->json('data.token');
        $this->assertNotSame($first, $second);
        $this->assertSame(3, $this->facilitator->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($second)->getJson('/api/v2/facilitator/session')->assertOk()->assertJsonPath('data.school_id', 1)->assertJsonPath('data.features.counselling', false);
        $this->postJson('/api/v2/facilitator/sign-out')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(2, $this->facilitator->tokens()->count());
        $this->assertSame('synthetic-teacher-push-registration', $this->facilitator->fresh()->fcm_id);
        $this->assertNotNull($teacher->accessToken->fresh());
        $this->assertNotNull($parent->accessToken->fresh());
    }

    public function test_wrong_credentials_wrong_school_and_revoked_roles_issue_no_session(): void
    {
        $this->login(['password' => 'wrong-password'])->assertUnauthorized();
        $this->login(['school' => School::findOrFail(2)->slug])->assertNotFound();
        SchoolMembership::where('user_id', $this->facilitator->id)->where('role', 'Facilitator')->update(['status' => 'ended']);
        $this->login()->assertForbidden();
        $this->assertSame(0, $this->facilitator->tokens()->count());
    }

    public function test_setup_is_atomic_and_issues_nothing_until_password_and_current_policies_are_complete(): void
    {
        PolicyAcceptance::where('user_id', $this->facilitator->id)->delete();
        $this->facilitator->forceFill(['dob' => '1990-02-03', 'password' => Hash::make('03021990'), 'password_change_required' => true])->save();
        $setup = $this->login(['password' => '03021990'])->assertConflict()->assertJsonPath('code', 'FACILITATOR_SETUP_REQUIRED')->assertJsonPath('data.password_change_required', true)->assertJsonPath('data.token', null)->json('data');
        $this->assertNotEmpty($setup['documents']);
        $this->assertSame(0, $this->facilitator->tokens()->count());
        $acceptances = array_column($setup['documents'], 'version', 'document');
        $new = ['password' => '03021990', 'new_password' => 'NewPrivateStaff2026', 'new_password_confirmation' => 'NewPrivateStaff2026', 'acceptances' => ['navigateed' => str_repeat('0', 64)]];
        $this->login($new)->assertConflict();
        $this->assertTrue(Hash::check('03021990', $this->facilitator->fresh()->password));
        $this->assertSame(0, PolicyAcceptance::where('user_id', $this->facilitator->id)->count());
        $this->login(array_replace($new, ['acceptances' => $acceptances]))->assertOk()->assertJsonPath('data.authenticated', true);
        $this->assertTrue(Hash::check('NewPrivateStaff2026', $this->facilitator->fresh()->password));
        $this->assertFalse((bool) $this->facilitator->fresh()->password_change_required);
        $this->assertFalse(app(ParentPolicyService::class)->needsAcceptance($this->facilitator));
    }

    public function test_facilitator_tokens_lose_generic_v2_access_after_fresh_role_or_policy_revocation(): void
    {
        $token = $this->login()->assertOk()->json('data.token');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v2/me/context')->assertOk();
        PolicyAcceptance::where('user_id', $this->facilitator->id)->delete();
        $this->getJson('/api/v2/me/context')->assertForbidden();
        ParentPolicyService::acceptCurrent($this->facilitator);
        SchoolMembership::where('user_id', $this->facilitator->id)->where('role', 'Facilitator')->update(['status' => 'ended']);
        $this->getJson('/api/v2/me/context')->assertForbidden();
        // Sign-out remains possible after the role has been removed.
        $this->postJson('/api/v2/facilitator/sign-out')->assertOk();
        $this->assertSame(0, $this->facilitator->tokens()->count());
    }

    public function test_teacher_profile_cannot_accidentally_sign_out_through_facilitator_endpoint(): void
    {
        $token = $this->facilitator->createToken(AppTokens::TEACHER)->plainTextToken;
        $this->withToken($token)->getJson('/api/v2/facilitator/session')->assertForbidden();
        $this->postJson('/api/v2/facilitator/sign-out')->assertForbidden();
        $this->assertSame(1, $this->facilitator->tokens()->count());
    }

    public function test_parent_only_membership_cannot_use_facilitator_sign_in(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->forceFill(['password' => Hash::make('SyntheticParent2026')])->save();
        $this->login(['email' => $parent->email, 'password' => 'SyntheticParent2026'])->assertForbidden();
        $this->assertSame(0, $parent->tokens()->where('name', AppTokens::FACILITATOR)->count());
    }

    public function test_cookie_profile_sign_in_has_no_bearer_and_web_module_revocation_protects_direct_group_api(): void
    {
        config(['sanctum.stateful' => ['localhost']]);
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        $this->browser('POST', '/api/v2/facilitator/sign-in', $this->input())->assertOk()->assertJsonPath('data.token', null);
        $this->assertAuthenticatedAs($this->facilitator, 'web');
        $this->assertSame(0, $this->facilitator->tokens()->count());
        $this->browser('GET', '/api/v2/activities/groups')->assertOk();
        app(ModuleGate::class)->change('web_facilitator', false, $this->superAdmin());
        $this->browser('GET', '/api/v2/activities/groups')->assertNotFound();
        $this->browser('GET', '/api/v2/facilitator/session')->assertNotFound();
        $this->browser('POST', '/api/v2/facilitator/sign-out')->assertOk();
        $this->assertGuest('web');
    }

    public function test_refused_cookie_setup_leaves_no_authenticated_session_or_token(): void
    {
        config(['sanctum.stateful' => ['localhost']]);
        PolicyAcceptance::where('user_id', $this->facilitator->id)->delete();
        $this->browser('POST', '/api/v2/facilitator/sign-in', $this->input())->assertConflict();
        $this->assertGuest();
        $this->assertSame(0, $this->facilitator->tokens()->count());
    }

    public function test_web_facilitator_switch_does_not_disable_authorized_native_app_sign_in(): void
    {
        app(ModuleGate::class)->change('web_facilitator', false, $this->superAdmin());
        $token = $this->login()->assertOk()->json('data.token');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v2/facilitator/session')->assertOk();
    }

    public function test_counsellor_profile_needs_no_teacher_record_and_does_not_expose_group_menu(): void
    {
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        SchoolMembership::where('user_id', $this->facilitator->id)->where('role', 'Facilitator')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->facilitator->id, 'role' => 'Counsellor', 'status' => 'active']);
        $this->facilitator->syncRoles(['Counsellor']);
        Teacher::where('user_id', $this->facilitator->id)->delete();
        $this->login()->assertOk()->assertJsonPath('data.profile.roles.0', 'Counsellor')->assertJsonPath('data.profile.features.groups', false);
    }

    public function test_stale_logout_cannot_revoke_a_replacement_token_and_expected_account_is_not_authority(): void
    {
        $old = $this->login()->assertOk()->json('data.token');
        $replacement = $this->facilitator->createToken(AppTokens::FACILITATOR)->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($old)->withHeader('X-Account-Id', '999999')->postJson('/api/v2/facilitator/sign-out')->assertForbidden();
        $this->assertSame(2, $this->facilitator->tokens()->count());
        $this->withHeader('X-Account-Id', (string) $this->facilitator->id)->postJson('/api/v2/facilitator/sign-out')->assertOk();
        $this->assertSame(1, $this->facilitator->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($replacement)->getJson('/api/v2/facilitator/session')->assertOk();
    }
}
