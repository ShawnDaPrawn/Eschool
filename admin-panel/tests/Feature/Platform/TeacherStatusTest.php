<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * A deactivated teacher (users.status other than 1) can neither sign in, to the
 * app or the panel, nor use a token or session they already had.
 */
class TeacherStatusTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
    }

    public function test_an_inactive_teacher_gets_no_app_token(): void
    {
        $teacher = $this->person('teacher');
        $teacher->forceFill(['status' => 0])->save();

        $this->postJson('/api/teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJson(['error' => true, 'message' => trans('teacher_account_deactivated')])
            ->assertJsonMissingPath('token');

        $this->assertSame(0, $teacher->tokens()->count());
    }

    public function test_an_active_teacher_signs_in(): void
    {
        $this->postJson('/api/teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJson(['error' => false])
            ->assertJsonStructure(['token']);
    }

    public function test_an_old_token_stops_working_on_every_teacher_route_once_deactivated(): void
    {
        $teacher = $this->person('teacher');
        $token = $teacher->createToken('teacher-app')->plainTextToken;
        $this->withToken($token)->getJson('/api/teacher/get-profile-details')->assertOk()->assertJson(['error' => false]);

        $teacher->forceFill(['status' => 0])->save();

        foreach (['dashboard', 'classes', 'get-profile-details', 'policy-acceptance'] as $route) {
            $this->app['auth']->forgetGuards();
            $this->withToken($token)->getJson('/api/teacher/'.$route)
                ->assertUnauthorized()
                ->assertJson(['error' => true, 'code' => 401, 'message' => trans('teacher_account_deactivated')]);
        }
    }

    public function test_an_inactive_teacher_cannot_sign_in_to_the_panel(): void
    {
        $this->person('teacher')->forceFill(['status' => 0, 'password' => Hash::make('Tom-panel-2026')])->save();

        $this->from('/login')->post('/login', ['email' => 'tom@example.com', 'password' => 'Tom-panel-2026'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', trans('teacher_account_deactivated'));
        $this->assertGuest();
    }

    public function test_a_signed_in_teacher_is_signed_out_of_the_panel_once_deactivated(): void
    {
        $teacher = $this->person('teacher');
        $this->actingAs($teacher)->get('/home')->assertOk();

        $teacher->forceFill(['status' => 0])->save();

        $this->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_the_school_can_deactivate_and_reactivate_a_teacher(): void
    {
        $teacher = $this->person('teacher');
        $teacher->createToken('teacher-app');
        $url = '/teachers/'.$teacher->id.'/status';

        // A teacher can't change a teacher's status (no teacher-edit).
        $this->actingAs($teacher)->postJson($url, ['status' => 0])->assertJson(['error' => true]);
        $this->assertSame(1, (int) $teacher->fresh()->status);

        $this->actingAs($this->superAdmin())->postJson($url, ['status' => 0])->assertJson(['error' => false]);
        $this->assertSame(0, (int) $teacher->fresh()->status);
        $this->assertSame(0, $teacher->tokens()->count(), 'deactivation signs the teacher out of the app');

        $this->actingAs($this->superAdmin())->postJson($url, ['status' => 1])->assertJson(['error' => false]);
        $this->assertSame(1, (int) $teacher->fresh()->status);

        $this->actingAs($this->superAdmin())->postJson($url, ['status' => 7])->assertJson(['error' => true]);
        // Only teachers: the Super Admin has no teachers row.
        $this->actingAs($this->superAdmin())->postJson('/teachers/'.$this->superAdmin()->id.'/status', ['status' => 0])
            ->assertJson(['error' => true]);
        $this->assertSame(1, (int) $this->superAdmin()->status);
    }

    public function test_the_teacher_list_shows_the_status(): void
    {
        $this->person('teacher')->forceFill(['status' => 0])->save();

        $rows = collect($this->actingAs($this->superAdmin())->getJson('/teacher_list?limit=50')->json('rows'));

        $this->assertSame(0, $rows->firstWhere('user_id', $this->people['teacher'])['status']);
        $this->assertStringContainsString('teacher-status', $rows->firstWhere('user_id', $this->people['teacher'])['operate']);
    }
}
