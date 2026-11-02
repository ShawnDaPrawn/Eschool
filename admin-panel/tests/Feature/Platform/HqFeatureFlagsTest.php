<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\FeatureFlags;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Feature flags and staged rollout (Action Plan SA.14): HQ rolls a feature out
 * to beta schools, then everyone, switches single schools, and kills it; each
 * school's app context carries the result. engineering/specs/platform/feature-flags.md.
 */
class HqFeatureFlagsTest extends TestCase
{
    use RefreshDatabase, ValidatesOpenApiResponses;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(2);
        Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => 'Jacaranda Primary']);
        // Pieter teaches at School 1 and runs School 2: one person, two schools.
        $user = User::query()->forceCreate([
            'first_name' => 'Pieter', 'last_name' => 'van Wyk', 'email' => 'pieter@example.com',
            'password' => bcrypt('Secret-Pass-2026'), 'gender' => 'Male', 'mobile' => '0820000000', 'status' => 1,
        ]);
        $user->assignRole('Teacher');
        app(CurrentSchool::class)->run(2, function () use ($user) {
            $user->assignRole('Super Admin');
            Settings::query()->create(['type' => 'school_name', 'message' => 'Baobab High School']);
        });
        $this->token = $user->createToken('test')->plainTextToken;
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-flags-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** @return array<string, bool> */
    private function flags(int $school): array
    {
        // The app's view: Pieter's token at that school. HQ's signed-in session is restored afterwards.
        $this->app['auth']->forgetGuards();
        $flags = $this->withToken($this->token)->getJson('/api/v2/me/context', ['X-School-Id' => (string) $school])->assertOk()->json('data.feature_flags');
        $this->withoutToken();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        if ($this->operator !== null) {
            $this->actingAs($this->operator, 'web');
        }

        return $flags;
    }

    private ?User $operator = null;

    private function as(string $role): void
    {
        $this->operator = $this->staff($role);
        $this->actingAs($this->operator);
    }

    private function flagId(string $key): int
    {
        return (int) DB::table('platform_feature_flags')->where('key', $key)->value('id');
    }

    private function revisionOf(int $id): int
    {
        return (int) DB::table('platform_feature_flags')->where('id', $id)->value('revision');
    }

    private function save(string $key, array $changes): void
    {
        $id = $this->flagId($key);
        $this->put('/saas/flags/'.$id, $changes + ['description' => 'Parent home across schools', 'rollout' => 'off', 'killed' => 0,
            'revision' => (int) DB::table('platform_feature_flags')->where('id', $id)->value('revision')])->assertRedirect('/saas/flags')->assertSessionHasNoErrors();
    }

    public function test_a_flag_rolls_out_from_beta_schools_to_everyone_and_is_killed_everywhere(): void
    {
        $this->as('operations');
        $this->post('/saas/flags', ['key' => 'parent_home', 'description' => 'Parent home across schools'])->assertRedirect('/saas/flags');
        $this->get('/saas/flags')->assertOk()->assertSee('parent_home')->assertSee('On at 0 of 2 schools.');
        $this->assertFalse($this->flags(1)['parent_home']);

        // Beta: only School 2, switched on by name.
        $this->save('parent_home', ['rollout' => 'beta']);
        $this->post('/saas/flags/'.$this->flagId('parent_home').'/schools', ['school_id' => 2, 'switch' => 'on'])->assertRedirect();
        $this->assertSame([false, true], [$this->flags(1)['parent_home'], $this->flags(2)['parent_home']]);

        // Everyone, except School 2 switched off by name.
        $this->save('parent_home', ['rollout' => 'everyone']);
        $this->post('/saas/flags/'.$this->flagId('parent_home').'/schools', ['school_id' => 2, 'switch' => 'off'])->assertRedirect();
        $this->assertSame([true, false], [$this->flags(1)['parent_home'], $this->flags(2)['parent_home']]);
        $this->post('/saas/flags/'.$this->flagId('parent_home').'/schools', ['school_id' => 2, 'switch' => 'clear'])->assertRedirect();
        $this->assertTrue($this->flags(2)['parent_home']);

        // The kill switch wins over every school switch, and needs a reason.
        $this->post('/saas/flags/'.$this->flagId('parent_home').'/schools', ['school_id' => 1, 'switch' => 'on'])->assertRedirect();
        $id = $this->flagId('parent_home');
        $this->put('/saas/flags/'.$id, ['description' => 'Parent home across schools', 'rollout' => 'everyone', 'killed' => 1,
            'revision' => (int) DB::table('platform_feature_flags')->where('id', $id)->value('revision')])->assertSessionHasErrors('kill_reason');
        $this->save('parent_home', ['rollout' => 'everyone', 'killed' => 1, 'kill_reason' => 'Crashes the parent app on Android 9']);
        $this->assertSame([false, false], [$this->flags(1)['parent_home'], $this->flags(2)['parent_home']]);
        $this->get('/saas/flags')->assertSee('Killed because: Crashes the parent app on Android 9');

        $actions = AuditLog::query()->where('action', 'like', 'platform_feature_flag%')->pluck('action')->unique()->sort()->values()->all();
        $this->assertSame(['platform_feature_flag_changed', 'platform_feature_flag_created', 'platform_feature_flag_killed', 'platform_feature_flag_school_switched'], $actions);
        $this->assertSame(3, AuditLog::query()->where('action', 'platform_feature_flag_school_switched')->where('school_id', 2)->count(), 'each switch is audited against its school');
    }

    public function test_a_platform_flag_can_switch_a_built_in_feature_off_but_never_on(): void
    {
        $built = $this->flags(1);
        $this->assertTrue($built['academic_content']);
        $this->assertFalse($built['teaching_assistant'], 'not available to this teacher, by the app\'s own rule');

        $this->as('owner');
        foreach (['academic_content', 'teaching_assistant'] as $key) {
            $this->post('/saas/flags', ['key' => $key, 'description' => 'Narrow a shipped feature'])->assertRedirect();
        }
        $this->save('academic_content', ['rollout' => 'everyone']);
        $this->save('teaching_assistant', ['rollout' => 'everyone']);
        $this->post('/saas/flags/'.$this->flagId('academic_content').'/schools', ['school_id' => 1, 'switch' => 'off'])->assertRedirect();

        $flags = $this->flags(1);
        $this->assertFalse($flags['academic_content'], 'switched off at School 1');
        $this->assertFalse($flags['teaching_assistant'], 'a platform flag never turns on what the app does not offer');
        $this->assertTrue($this->flags(2)['academic_content'], 'School 2 is untouched');
        $this->assertSame(array_keys($built), array_keys($flags), 'no key added or lost');
    }

    public function test_flag_values_follow_the_rules(): void
    {
        foreach ([
            ['off', false, null, false], ['off', false, true, false], ['beta', false, null, false], ['beta', false, true, true],
            ['everyone', false, null, true], ['everyone', false, false, false], ['everyone', true, true, false], ['beta', true, true, false],
        ] as [$rollout, $killed, $school, $expected]) {
            $this->assertSame($expected, FeatureFlags::value($rollout, $killed, $school), json_encode([$rollout, $killed, $school]));
        }
        $this->assertFalse(app(FeatureFlags::class)->enabled('unknown_flag', 1));
    }

    public function test_bad_input_and_stale_forms_are_refused(): void
    {
        $this->actingAs($this->staff('owner'));
        foreach (['Parent-Home', 'ab', 'x'.str_repeat('y', 64), '1flag'] as $key) {
            $this->post('/saas/flags', ['key' => $key, 'description' => 'A bad key'])->assertSessionHasErrors('key');
        }
        $this->post('/saas/flags', ['key' => 'parent_home', 'description' => 'Parent home across schools'])->assertRedirect();
        $this->post('/saas/flags', ['key' => 'parent_home', 'description' => 'The same key twice'])->assertSessionHasErrors('key');
        $id = $this->flagId('parent_home');
        $this->put('/saas/flags/'.$id, ['description' => 'Parent home across schools', 'rollout' => 'everyone', 'killed' => 0, 'revision' => 7])->assertStatus(409);
        // Killing needs a reason however "true" is written, JSON included.
        foreach ([true, 'true', 1, '1', 'on', 'yes'] as $killed) {
            $this->putJson('/saas/flags/'.$id, ['description' => 'Parent home across schools', 'rollout' => 'everyone', 'killed' => $killed, 'kill_reason' => null, 'revision' => $this->revisionOf($id)])
                ->assertStatus(422)->assertJsonValidationErrors('kill_reason');
            $this->putJson('/saas/flags/'.$id, ['description' => 'Parent home across schools', 'rollout' => 'everyone', 'killed' => $killed, 'revision' => $this->revisionOf($id)])
                ->assertStatus(422)->assertJsonValidationErrors('kill_reason');
        }
        $this->assertFalse((bool) DB::table('platform_feature_flags')->where('id', $id)->value('killed'));
        $this->post('/saas/flags/'.$id.'/schools', ['school_id' => 999, 'switch' => 'on'])->assertSessionHasErrors('school_id');
        $this->put('/saas/flags/999', ['description' => 'Unknown flag', 'rollout' => 'everyone', 'killed' => 0, 'revision' => 1])->assertNotFound();
        $this->assertSame(['off', 0], [DB::table('platform_feature_flags')->where('id', $id)->value('rollout'), DB::table('platform_feature_flag_schools')->count()]);
    }

    public function test_only_owner_and_operations_manage_flags_and_no_school_account_reaches_them(): void
    {
        $this->actingAs($this->staff('owner'));
        $this->post('/saas/flags', ['key' => 'parent_home', 'description' => 'Parent home across schools'])->assertRedirect();
        $id = $this->flagId('parent_home');
        $before = [DB::table('platform_feature_flags')->value('revision'), DB::table('platform_feature_flags')->value('rollout'), DB::table('platform_feature_flag_schools')->count(), DB::table('platform_feature_flags')->count()];

        foreach (['support', 'finance', 'content', 'sales'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get('/saas')->assertOk()->assertDontSee('Feature flags');
            $this->get('/saas/flags')->assertForbidden();
            $this->post('/saas/flags', ['key' => 'sneaky_flag', 'description' => 'Should never be created'])->assertForbidden();
            $this->put('/saas/flags/'.$id, ['description' => 'Changed', 'rollout' => 'everyone', 'killed' => 0, 'revision' => 1])->assertForbidden();
            $this->post('/saas/flags/'.$id.'/schools', ['school_id' => 1, 'switch' => 'on'])->assertForbidden();
        }
        // A school's own Super Admin (Pieter at School 2) never reaches HQ.
        $this->actingAs(User::query()->where('email', 'pieter@example.com')->firstOrFail());
        $this->get('/saas/flags')->assertForbidden();
        $this->put('/saas/flags/'.$id, ['description' => 'Changed', 'rollout' => 'everyone', 'killed' => 0, 'revision' => 1])->assertForbidden();
        $this->post('/saas/flags/'.$id.'/schools', ['school_id' => 2, 'switch' => 'on'])->assertForbidden();

        $this->assertSame($before, [DB::table('platform_feature_flags')->value('revision'), DB::table('platform_feature_flags')->value('rollout'), DB::table('platform_feature_flag_schools')->count(), DB::table('platform_feature_flags')->count()]);
    }
}
