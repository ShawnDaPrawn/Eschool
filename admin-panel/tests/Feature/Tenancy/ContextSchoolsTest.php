<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * GET /api/v2/me/context for someone at two schools: the answer is for the
 * school the request works in, lists both schools with the roles there,
 * and is composed by the contributors in config/context.php. Every answer
 * is checked against openapi.yaml.
 */
class ContextSchoolsTest extends TestCase
{
    use RefreshDatabase, ValidatesOpenApiResponses;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        School::query()->forceCreate(['id' => 3, 'name' => 'Marula College', 'slug' => 'marula-college', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(2);
        Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => 'Jacaranda Primary']);

        $user = User::query()->forceCreate([
            'first_name' => 'Pieter', 'last_name' => 'van Wyk', 'email' => 'pieter@example.com',
            'password' => bcrypt('Secret-Pass-2026'), 'gender' => 'Male', 'mobile' => '0820000000', 'status' => 1,
        ]);
        $user->assignRole('Teacher');
        app(CurrentSchool::class)->run(2, function () use ($user) {
            $user->assignRole('Super Admin');
            Settings::query()->create(['type' => 'school_name', 'message' => 'Baobab High School']);
            Settings::query()->create(['type' => 'time_zone', 'message' => 'Africa/Harare']);
        });
        $this->token = $user->createToken('test')->plainTextToken;
    }

    public function test_the_answer_is_for_the_first_school_and_lists_every_school_with_its_roles(): void
    {
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.id', 1)
            ->assertJsonPath('data.current_school.name', 'Jacaranda Primary')
            ->assertJsonPath('data.user.roles', ['Teacher'])
            ->assertJsonPath('data.schools.0.school.id', 1)
            ->assertJsonPath('data.schools.0.roles', ['Teacher'])
            ->assertJsonPath('data.schools.1.school', ['id' => 2, 'name' => 'Baobab High School', 'short_name' => null, 'crest_url' => null])
            ->assertJsonPath('data.schools.1.roles', ['Super Admin'])
            ->assertJsonMissingPath('data.roles');
    }

    public function test_x_school_id_chooses_the_school(): void
    {
        $this->withToken($this->token)->getJson('/api/v2/me/context', ['X-School-Id' => '2'])->assertOk()
            ->assertJsonPath('data.current_school.id', 2)
            ->assertJsonPath('data.current_school.name', 'Baobab High School')
            ->assertJsonPath('data.current_school.timezone', 'Africa/Harare')
            ->assertJsonPath('data.current_school.session_year', null)
            ->assertJsonPath('data.user.roles', ['Super Admin']);

        // Not one of the user's schools.
        $this->withToken($this->token)->getJson('/api/v2/me/context', ['X-School-Id' => '3'])
            ->assertForbidden()->assertJsonPath('error', true);
    }

    public function test_a_contributor_adds_keys_and_fields_without_removing_any(): void
    {
        $before = $this->withToken($this->token)->getJson('/api/v2/me/context')->json('data');
        config(['context.contributors' => [...config('context.contributors'), AddsToContext::class]]);

        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.name', 'Jacaranda Primary')
            ->assertJsonPath('data.current_school.branding.theme_color', '#000001')
            ->assertJsonPath('data.current_school.branding.secondary_color', $before['current_school']['branding']['secondary_color'])
            ->assertJsonPath('data.feature_flags', ['new_switch' => true]);
    }

    public function test_the_calendar_orders_live_terms_and_never_joins_another_schools_term(): void
    {
        $year = SessionYear::create(['name' => 'Context year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);
        $term = ['session_year_id' => $year->id, 'start_date' => '2026-02-01', 'end_date' => '2026-03-31', 'grade_from' => 0, 'grade_to' => 12];
        $second = Term::create(['name' => 'Second by date'] + $term);
        $first = Term::create(['name' => 'First by date', 'start_date' => '2026-01-01'] + $term);
        $third = Term::create(['name' => 'Third by id'] + $term);
        Term::create(['name' => 'Deleted term'] + $term)->delete();
        DB::table('terms')->insert(['school_id' => 2, 'name' => 'Foreign term'] + $term);

        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.session_year.id', $year->id)
            ->assertJsonPath('data.current_school.session_year.terms.0.id', $first->id)
            ->assertJsonPath('data.current_school.session_year.terms.1.id', $second->id)
            ->assertJsonPath('data.current_school.session_year.terms.2.id', $third->id)
            ->assertJsonCount(3, 'data.current_school.session_year.terms')
            ->assertDontSee('Foreign term')->assertDontSee('Deleted term');
    }

    public function test_a_year_without_terms_is_retained_and_a_deleted_year_is_absent(): void
    {
        $year = SessionYear::create(['name' => 'Empty context year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.session_year.id', $year->id)
            ->assertJsonPath('data.current_school.session_year.terms', []);
        $year->delete();
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.session_year', null);
    }

    public function test_a_foreign_year_setting_never_returns_another_schools_calendar(): void
    {
        $year = app(CurrentSchool::class)->run(2, fn () => SessionYear::create([
            'name' => 'Foreign calendar', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]));
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.session_year', null)->assertDontSee('Foreign calendar');
    }
}

/** A later stream's contributor, for the composition test. */
final class AddsToContext implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'current_school' => ['branding' => ['theme_color' => '#000001']],
            'feature_flags' => ['new_switch' => true],
        ];
    }
}
