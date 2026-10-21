<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireParentPolicyAcceptance;
use App\Models\Settings;
use App\Models\User;
use App\Services\ParentPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Parents accept NavigateEd, then the school's privacy notice and terms.
 */
class ParentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_a_parent_accepts_navigateed_before_the_school(): void
    {
        $parent = $this->parent();
        $token = $parent->createToken('phone')->plainTextToken;

        $status = $this->withToken($token)->getJson('/api/parent/policy-acceptance');
        $status->assertOk()->assertJsonPath('error', false)->assertJsonPath('data.navigateed.accepted', false);
        $status->assertJsonPath('data.school.required', false);
        $version = $status->json('data.navigateed.version');

        $this->withToken($token)->postJson('/api/parent/policy-acceptance', [
            'document' => 'school',
            'version' => 'nope',
        ])->assertOk()->assertJsonPath('error', true);

        $this->withToken($token)->postJson('/api/parent/policy-acceptance', [
            'document' => 'navigateed',
            'version' => 'stale',
        ])->assertOk()->assertJsonPath('error', true);

        $this->withToken($token)->postJson('/api/parent/policy-acceptance', [
            'document' => 'navigateed',
            'version' => $version,
        ])->assertOk()->assertJsonPath('error', false)->assertJsonPath('data.navigateed.accepted', true);

        $this->assertFalse(app(ParentPolicyService::class)->needsAcceptance($parent->fresh()));
    }

    public function test_changing_the_school_terms_asks_the_parent_again(): void
    {
        $parent = $this->parent();
        ParentPolicyService::acceptCurrent($parent);

        Settings::create(['type' => 'privacy_policy', 'message' => '<p>We keep attendance and photos.</p>']);
        Settings::create(['type' => 'terms_condition', 'message' => '<p>Fees are due on the first.</p>']);

        $token = $parent->createToken('phone')->plainTextToken;
        $status = $this->withToken($token)->getJson('/api/parent/policy-acceptance');
        $status->assertJsonPath('data.navigateed.accepted', true);
        $status->assertJsonPath('data.school.required', true);
        $status->assertJsonPath('data.school.accepted', false);
        $this->assertStringContainsString('photos', $status->json('data.school.privacy_policy'));

        $this->assertTrue(app(ParentPolicyService::class)->needsAcceptance($parent->fresh()));

        $this->withToken($token)->postJson('/api/parent/policy-acceptance', [
            'document' => 'school',
            'version' => $status->json('data.school.version'),
        ])->assertJsonPath('data.school.accepted', true);

        Settings::where('type', 'terms_condition')->update(['message' => '<p>Fees are due on the seventh.</p>']);

        $this->withToken($token)->getJson('/api/parent/policy-acceptance')
            ->assertJsonPath('data.school.accepted', false);
    }

    public function test_other_parent_routes_wait_until_the_terms_are_accepted(): void
    {
        $parent = $this->parent();
        $request = Request::create('/api/parent/announcements', 'GET');
        $request->setUserResolver(fn () => $parent);

        $blocked = app(RequireParentPolicyAcceptance::class)->handle($request, fn () => response('next'));
        $this->assertSame(403, $blocked->getStatusCode());
        $this->assertSame('policy_acceptance_required', $blocked->getData()->code);

        ParentPolicyService::acceptCurrent($parent->fresh());

        $open = app(RequireParentPolicyAcceptance::class)->handle($request, fn () => response('next'));
        $this->assertSame('next', $open->getContent());
    }

    public function test_a_blank_school_page_is_cleared(): void
    {
        Settings::create(['type' => 'privacy_policy', 'message' => '<p>Old notice</p>']);

        $this->actingAs($this->superAdmin())->postJson('/setting-update', [
            'type' => 'privacy_policy',
            'message' => '',
        ])->assertJsonPath('error', false);

        $this->assertSame('', Settings::where('type', 'privacy_policy')->value('message'));
        $this->assertFalse(app(ParentPolicyService::class)->status($this->parent())['school']['required']);
    }

    private function parent(): User
    {
        $user = User::create([
            'first_name' => 'Pat',
            'last_name' => 'Parent',
            'email' => 'pat.parent@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Female',
            'mobile' => '0000000000',
        ]);
        $user->assignRole('Parent');

        return $user;
    }
}
