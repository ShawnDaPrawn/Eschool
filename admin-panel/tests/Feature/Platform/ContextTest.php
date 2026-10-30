<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesDemoSchool;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * GET /api/v2/me/context: what the apps read on launch. Every profile gets its
 * own user and roles, the school's branding, timezone, year and terms; a
 * parent gets their own children and nobody else's.
 */
class ContextTest extends TestCase
{
    use UsesDemoSchool;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
    }

    public function test_it_needs_a_token(): void
    {
        $this->getJson('/api/v2/me/context')
            ->assertStatus(401)
            ->assertExactJson(['error' => true, 'message' => trans('api_unauthenticated'), 'data' => null, 'code' => 'unauthenticated']);
    }

    public function test_a_learner_gets_their_user_their_school_and_no_children(): void
    {
        $response = $this->context('asha')->assertOk();

        $response->assertJsonPath('error', false)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.user.id', $this->school->ids['asha_user'])
            ->assertJsonPath('data.user.first_name', 'Asha')
            ->assertJsonPath('data.user.name', 'Asha Demo')
            ->assertJsonPath('data.user.email', 'GR-ASHA')
            ->assertJsonPath('data.user.image', null)
            ->assertJsonPath('data.user.roles', ['Student'])
            ->assertJsonPath('data.schools', [[
                'school' => ['id' => 1, 'name' => schoolSetting('school_name'), 'short_name' => null, 'crest_url' => null],
                'roles' => ['Student'],
            ]])
            ->assertJsonPath('data.current_school.id', 1)
            ->assertJsonPath('data.current_school.timezone', schoolSetting('time_zone'))
            ->assertJsonPath('data.current_school.session_year.id', $this->school->ids['session_year'])
            ->assertJsonPath('data.current_school.session_year.name', 'Current year')
            ->assertJsonPath('data.current_school.session_year.start_date', $this->school->today(-120)->toDateString())
            ->assertJsonPath('data.current_school.session_year.terms', [[
                'id' => $this->school->ids['term'], 'name' => 'Term 1',
                'start_date' => $this->school->today(-120)->toDateString(), 'end_date' => $this->school->today(60)->toDateString(),
                'grade_from' => 0, 'grade_to' => 12, 'marks_lock_on' => null, 'reports_on' => null, 'current' => true,
            ]])
            ->assertJsonPath('data.modules.0.key', 'school')
            ->assertJsonPath('data.notification_rules.0.type', 'account.verify_email')
            ->assertJsonPath('data.sign_in_methods', ['password'])
            ->assertJsonPath('data.children', []);

        // An object, not a list, even when there are no flags.
        $response->assertJsonPath('data.feature_flags.module_switches', true)
            ->assertJsonPath('data.feature_flags.academic_content', true)
            ->assertJsonPath('data.feature_flags.communication_messages', true);
    }

    public function test_a_parent_gets_their_own_children_each_with_their_school(): void
    {
        $children = $this->context('farid')->assertOk()->json('data.children');

        $this->assertSame([$this->school->ids['asha'], $this->school->ids['cara']], array_column($children, 'id'));
        $this->assertSame([
            'id' => $this->school->ids['asha'],
            'user_id' => $this->school->ids['asha_user'],
            'first_name' => 'Asha',
            'last_name' => 'Demo',
            'name' => 'Asha Demo',
            'image' => null,
            'student_number' => null,
            'approved' => false,
            'homeroom' => 'Grade 5 A',
            'school' => ['id' => 1, 'name' => schoolSetting('school_name'), 'short_name' => null, 'crest_url' => null],
        ], $children[0]);
    }

    public function test_a_parent_never_gets_another_familys_child(): void
    {
        // Gina is Ben's guardian only; Mona is Asha's mother only.
        $this->assertSame([$this->school->ids['ben']], array_column($this->context('gina')->json('data.children'), 'id'));
        $this->assertSame([$this->school->ids['asha']], array_column($this->context('mona')->json('data.children'), 'id'));
    }

    public function test_teachers_and_staff_get_their_context_without_children(): void
    {
        $this->context('tara')->assertOk()
            ->assertJsonPath('data.user.roles', ['Teacher'])
            ->assertJsonPath('data.children', []);

        $this->forgetUser();
        $this->withToken($this->superAdmin()->createToken('test')->plainTextToken)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.user.roles', ['Super Admin'])
            ->assertJsonPath('data.schools.0.roles', ['Super Admin']);

        $this->forgetUser();
        Role::create(['name' => 'Bursar', 'guard_name' => 'web']);
        $this->withToken($this->userWithRole('Bursar')->createToken('test')->plainTextToken)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.user.roles', ['Bursar']);
    }

    public function test_an_account_with_no_role_is_refused(): void
    {
        $this->withToken($this->userWithRole(null)->createToken('test')->plainTextToken)->getJson('/api/v2/me/context')
            ->assertStatus(403)
            ->assertJsonPath('error', true)
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('data', null);
    }

    public function test_branding_is_passed_on_as_the_school_stored_it(): void
    {
        foreach (['school_short_name' => 'NHS', 'theme_color' => '#22577A', 'secondary_color' => '#3bcc97', 'logo1' => 'logos/crest.png', 'school_tagline' => 'Learn and grow'] as $type => $message) {
            Settings::query()->updateOrCreate(['type' => $type], ['message' => $message]);
        }

        $this->context('asha')->assertOk()
            ->assertJsonPath('data.current_school.short_name', 'NHS')
            ->assertJsonPath('data.current_school.tagline', 'Learn and grow')
            ->assertJsonPath('data.current_school.crest_url', url('/storage/logos/crest.png'))
            ->assertJsonPath('data.current_school.branding', ['theme_color' => '#22577A', 'secondary_color' => '#3bcc97']);
    }

    public function test_a_school_without_a_current_year_still_answers(): void
    {
        Settings::query()->where('type', 'session_year')->update(['message' => '']);

        $this->context('tara')->assertOk()->assertJsonPath('data.current_school.session_year', null);
    }

    /**
     * Budget: 150 ms at p95. The work is a fixed handful of queries (the
     * token, its user and its last-used time, the user's memberships with
     * their schools (Phase 1, one query), roles, settings, year, terms, and
     * for a parent their record, children and the children's users),
     * whatever the number of children.
     */
    public function test_it_takes_a_fixed_handful_of_queries(): void
    {
        $twoChildren = $this->queriesFor('farid');
        $oneChild = $this->queriesFor('gina');
        $teacher = $this->queriesFor('tara');

        $this->assertSame($oneChild, $twoChildren, 'The number of queries grows with the number of children');
        // One additional fixed query loads profile metadata, without values,
        // and one more the platform feature flags with this school's switches (SA.14).
        $this->assertLessThanOrEqual(15, $twoChildren, "A parent's context takes $twoChildren queries, more than budgeted");
        $this->assertLessThanOrEqual(12, $teacher, "A teacher's context takes $teacher queries, more than budgeted");
    }

    private function queriesFor(string $person): int
    {
        $this->forgetUser();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->context($person)->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    private function context(string $person): TestResponse
    {
        $this->forgetUser();

        return $this->withToken($this->school->tokens[$person])->getJson('/api/v2/me/context');
    }

    private function forgetUser(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function userWithRole(?string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Pat', 'last_name' => 'Office', 'email' => 'pat'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }
}
