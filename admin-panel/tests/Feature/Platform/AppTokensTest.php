<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Identity\AppTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * App tokens are named after their app. An account can be signed in to the
 * same app on several devices (up to AppTokens::MAX_DEVICES_PER_APP); signing
 * out ends only that device; a password change revokes every other token.
 */
class AppTokensTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
    }

    public function test_signing_in_on_another_device_keeps_the_first_device_signed_in(): void
    {
        foreach ([
            ['student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123'], 'learner', AppTokens::STUDENT, 'student/get-profile-data'],
            ['parent/login', ['email' => 'pam@example.com', 'password' => 'secret123'], 'parent', AppTokens::PARENT, 'parent/get-profile-data'],
            ['teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123'], 'teacher', AppTokens::TEACHER, 'teacher/get-profile-details'],
        ] as [$login, $credentials, $person, $app, $profile]) {
            $this->anotherDevice();
            $first = $this->postJson('/api/'.$login, $credentials)->assertJson(['error' => false])->json('token');
            $this->anotherDevice();
            $second = $this->postJson('/api/'.$login, $credentials)->assertJson(['error' => false])->json('token');

            $tokens = $this->person($person)->tokens()->get();
            $this->assertSame([$app, $app], $tokens->pluck('name')->all(), "$login keeps a token per device, named for the app");

            $this->anotherDevice();
            $this->withToken($first)->getJson('/api/'.$profile)->assertOk()->assertJson(['error' => false]);
            $this->anotherDevice();
            $this->withToken($second)->getJson('/api/'.$profile)->assertOk()->assertJson(['error' => false]);
        }
    }

    public function test_signing_in_to_one_app_leaves_the_accounts_other_apps_signed_in(): void
    {
        // A teacher who is also a parent at the school.
        $teacher = $this->person('teacher');
        $teacher->assignRole('Parent');
        $teacherApp = $teacher->createToken(AppTokens::TEACHER);
        $legacy = $teacher->createToken('Tom');
        $oldParentApp = $teacher->createToken(AppTokens::PARENT);

        AppTokens::issue($teacher, AppTokens::PARENT);

        $names = $teacher->tokens()->pluck('name')->sort()->values()->all();
        $this->assertSame(['Tom', AppTokens::PARENT, AppTokens::TEACHER], $names);
        $this->assertNotNull($teacher->tokens()->find($teacherApp->accessToken->id));
        $this->assertNotNull($teacher->tokens()->find($legacy->accessToken->id), 'an unnamed token may belong to the other app');
        $this->assertNull($teacher->tokens()->find($oldParentApp->accessToken->id));
    }

    public function test_tokens_from_before_app_names_go_when_the_account_has_only_this_app(): void
    {
        $parent = $this->person('parent');
        $parent->createToken('Pam');
        $parent->createToken('test');

        $this->postJson('/api/parent/login', ['email' => 'pam@example.com', 'password' => 'secret123'])->assertJson(['error' => false]);

        $this->assertSame([AppTokens::PARENT], $parent->tokens()->pluck('name')->all());
    }

    public function test_signing_out_ends_only_that_device(): void
    {
        $teacher = $this->person('teacher');
        $teacher->assignRole('Parent');
        $parentToken = $teacher->createToken(AppTokens::PARENT);
        $teacherToken = $teacher->createToken(AppTokens::TEACHER)->plainTextToken;
        $tablet = $teacher->createToken(AppTokens::TEACHER);

        $this->withToken($teacherToken)->postJson('/api/logout')->assertOk()->assertJson(['error' => false]);

        $this->assertSame([AppTokens::PARENT, AppTokens::TEACHER], $teacher->tokens()->orderBy('id')->pluck('name')->all());
        $this->assertNotNull($teacher->tokens()->find($parentToken->accessToken->id));
        $this->assertNotNull($teacher->tokens()->find($tablet->accessToken->id), 'the teacher stays signed in on the other device');
    }

    public function test_signing_in_beyond_the_device_limit_signs_the_oldest_device_out(): void
    {
        $parent = $this->person('parent');
        $old = collect(range(1, AppTokens::MAX_DEVICES_PER_APP))->map(fn () => $parent->createToken(AppTokens::PARENT)->accessToken->id);

        AppTokens::issue($parent, AppTokens::PARENT);

        $ids = $parent->tokens()->where('name', AppTokens::PARENT)->pluck('id');
        $this->assertCount(AppTokens::MAX_DEVICES_PER_APP, $ids);
        $this->assertFalse($ids->contains($old->first()), 'the oldest device is signed out');
        $this->assertTrue($ids->contains($old->last()));
    }

    public function test_a_password_change_revokes_every_other_token(): void
    {
        $teacher = $this->person('teacher');
        $teacher->assignRole('Parent');
        $teacher->createToken(AppTokens::PARENT);
        $teacher->createToken('legacy');
        $current = $teacher->createToken(AppTokens::TEACHER);

        $this->withToken($current->plainTextToken)->postJson('/api/change-password', [
            'current_password' => 'secret123',
            'new_password' => 'Harbour-lights-77',
            'new_confirm_password' => 'Harbour-lights-77',
        ])->assertOk()->assertJson(['error' => false]);

        $this->assertSame([$current->accessToken->id], $teacher->tokens()->pluck('id')->all());
    }

    public function test_the_panel_password_change_revokes_every_token(): void
    {
        $teacher = $this->person('teacher');
        $teacher->createToken(AppTokens::TEACHER);
        $teacher->createToken(AppTokens::PARENT);

        $this->actingAs($teacher)->postJson('/changePassword', [
            'old_password' => 'secret123',
            'new_password' => 'Harbour-lights-77',
            'confirm_password' => 'Harbour-lights-77',
        ])->assertJson(['error' => false]);

        $this->assertSame(0, $teacher->tokens()->count());
    }

    /**
     * Auth::attempt() in a sign-in also signs the test's shared session in;
     * a phone has no such session, only its token.
     */
    private function anotherDevice(): void
    {
        // auth:sanctum switched the default guard for the rest of the test.
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }
}
