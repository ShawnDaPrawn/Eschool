<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Mediums;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The panel's school switcher: people with memberships at several schools
 * choose the school in the header, and everything follows it.
 */
class PanelSchoolSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        School::query()->whereKey(1)->update(['name' => 'Jacaranda Primary']);
        app(SchoolRoles::class)->seed(2);
        Mediums::query()->forceCreate(['name' => 'English at Jacaranda']);
        app(CurrentSchool::class)->run(2, function () {
            Mediums::query()->forceCreate(['name' => 'Afrikaans at Baobab']);
            // A school needs a current year (the platform admin's school wizard sets one).
            $year = SessionYear::query()->forceCreate(['name' => '2026', 'start_date' => '2026-01-14', 'end_date' => '2026-12-09', 'default' => 1]);
            Settings::query()->create(['type' => 'session_year', 'message' => (string) $year->id]);
        });
    }

    public function test_a_person_at_one_school_sees_no_switcher(): void
    {
        $this->actingAs($this->superAdmin())->get('/home')->assertOk()->assertDontSee('js-school-switcher', false);
    }

    public function test_an_admin_at_two_schools_switches_and_everything_follows(): void
    {
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, fn () => $admin->assignRole('Super Admin'));

        $this->actingAs($admin)->get('/home')->assertOk()
            ->assertSee('js-school-switcher', false)
            ->assertSee('Jacaranda Primary')->assertSee('Baobab High');
        $this->actingAs($admin)->getJson('/medium_list?limit=50&offset=0')->assertOk()
            ->assertSee('English at Jacaranda')->assertDontSee('Afrikaans at Baobab');

        $this->actingAs($admin)->withSession(['session_year' => 1])
            ->post('/school/switch', ['school_id' => 2])
            ->assertRedirect(route('home'))
            ->assertSessionHas('school_id', 2)
            ->assertSessionMissing('session_year');

        $this->actingAs($admin)->getJson('/medium_list?limit=50&offset=0')->assertOk()
            ->assertSee('Afrikaans at Baobab')->assertDontSee('English at Jacaranda');
        $this->actingAs($admin)->get('/home')->assertOk()->assertSee('data-selected="2"', false);
    }

    public function test_what_the_admin_creates_belongs_to_the_chosen_school(): void
    {
        Mail::fake();
        Queue::fake();
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, fn () => $admin->assignRole('Super Admin'));

        $this->actingAs($admin)->withSession(['school_id' => 2])->postJson('/teachers', [
            'first_name' => 'Themba', 'last_name' => 'Ngwenya', 'gender' => 'Male', 'email' => 'themba@example.com',
            'mobile' => '0821234567', 'dob' => '1985-02-01', 'qualification' => 'B.Ed', 'current_address' => '3 Baobab Avenue',
            'permanent_address' => '3 Baobab Avenue',
        ])->assertOk()->assertJsonPath('error', false);

        $teacher = User::acrossSchools()->where('email', 'themba@example.com')->sole();
        $this->assertSame([2], app(MembershipService::class)->active($teacher)->pluck('school_id')->all());
        $this->assertTrue(app(CurrentSchool::class)->run(2, fn () => $teacher->fresh()->hasRole('Teacher')));
        $this->assertSame(2, (int) Teacher::acrossSchools()->where('user_id', $teacher->id)->value('school_id'));
        $this->assertNull(User::query()->find($teacher->id), "School 1 does not see School 2's new teacher");
    }

    public function test_a_school_the_person_is_not_at_cannot_be_chosen(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->from('/home')->post('/school/switch', ['school_id' => 2])
            ->assertRedirect('/home')->assertSessionHasErrors('school_id')->assertSessionMissing('school_id');

        // A stale choice in the session falls back to the person's own school.
        $this->actingAs($admin)->withSession(['school_id' => 2])->getJson('/medium_list?limit=50&offset=0')->assertOk()
            ->assertSee('English at Jacaranda')->assertDontSee('Afrikaans at Baobab');
    }
}
